#!/usr/bin/env node
//
// Drives the showcase like a user (through the plugins' own UI, not by setting
// values by hand) and checks what actually reaches the database, plus the cases
// where plugin swaps usually break: old input after a validation error, init
// after pjax navigation and init in rows added later (hasMany).
//
// Needs an app prepared with tests/smoke/setup-app.sh + tests/smoke/ui/setup-ui.sh
// (SQLite). Every check prints "ok" or "FAIL"; the exit code is the failure count.
//
// usage: (cd tests/smoke/ui && npm install)
//        node tests/smoke/ui/interactions.mjs <app-dir> [port]
//
import { execFileSync, spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const [appArg, portArg] = process.argv.slice(2);
if (!appArg) {
    console.error('usage: node interactions.mjs <app-dir> [port]');
    process.exit(2);
}
const APP = path.resolve(appArg);
const PORT = Number(portArg || 8767);
const BASE = `http://127.0.0.1:${PORT}`;
const DB = path.join(APP, 'database/database.sqlite');

function findChrome() {
    if (process.env.CHROME_PATH) return process.env.CHROME_PATH;
    const root = path.join(os.homedir(), '.cache/puppeteer/chrome');
    const builds = fs.existsSync(root) ? fs.readdirSync(root).sort().reverse() : [];
    for (const build of builds) {
        for (const rel of [
            'chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing',
            'chrome-mac-x64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing',
            'chrome-linux64/chrome',
        ]) {
            const bin = path.join(root, build, rel);
            if (fs.existsSync(bin)) return bin;
        }
    }
    for (const bin of ['/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', '/usr/bin/google-chrome', '/usr/bin/chromium']) {
        if (fs.existsSync(bin)) return bin;
    }
    throw new Error('no Chrome found: set CHROME_PATH');
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const sql = (query) => JSON.parse(execFileSync('sqlite3', ['-json', DB, query]).toString() || '[]');
const json = (v) => (v === null || v === undefined ? v : JSON.parse(v));

let failures = 0;
function check(name, ok, detail = '') {
    if (!ok) failures++;
    console.log(`${ok ? 'ok  ' : 'FAIL'} ${name}${!ok && detail ? ` -- ${detail}` : ''}`);
}
const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);

// Marks the current document: if it survives an action, the page was not fully reloaded.
const mark = (page) => page.evaluate(() => { window.__noReload = true; });
const notReloaded = (page) => page.evaluate(() => window.__noReload === true);

async function waitPjax(page, action) {
    await page.evaluate(() => { window.__pjaxDone = false; $(document).one('pjax:end', () => { window.__pjaxDone = true; }); });
    await action();
    await page.waitForFunction(() => window.__pjaxDone === true, { timeout: 10000 }).catch(() => {});
    await sleep(500);
}

async function submit(page, { afterSave } = {}) {
    await page.evaluate((afterSave) => {
        document.querySelectorAll('form').forEach((f) => { f.noValidate = true; });
        if (afterSave) {
            document.querySelectorAll('.after-submit').forEach((c) => { c.checked = c.value === String(afterSave); });
        }
    }, afterSave);
    await waitPjax(page, () => page.evaluate(() => [...document.querySelectorAll('button[type="submit"]')].find((b) => b.offsetParent).click()));
}

// --- select2 helpers: open the widget and click the option, like a user does ---
async function select2Pick(page, selectSelector, label) {
    await page.evaluate((sel) => {
        const container = $(sel).next('.select2')[0];
        container.scrollIntoView({ block: 'center' });
    }, selectSelector);
    const box = await page.evaluateHandle((sel) => $(sel).next('.select2').find('.select2-selection')[0], selectSelector);
    await box.click();
    await page.waitForSelector('.select2-container--open .select2-results__option', { timeout: 3000 });
    const option = await page.evaluateHandle((label) => [...document.querySelectorAll('.select2-container--open .select2-results__option')]
        .find((o) => o.textContent.trim() === label), label);
    if (!option.asElement()) throw new Error(`select2 option "${label}" not found for ${selectSelector}`);
    await option.click();
    await sleep(200);
}

async function select2Type(page, selectSelector, text) {
    const field = await page.evaluateHandle((sel) => $(sel).next('.select2').find('.select2-search__field')[0], selectSelector);
    await field.click();
    await field.type(text);
    await sleep(300);
    await page.keyboard.press('Enter');
    await sleep(200);
}

async function clickEl(page, selector) {
    const el = await page.$(selector);
    if (!el) throw new Error(`no element ${selector}`);
    await el.evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await el.click();
    await sleep(150);
}

// --- flatpickr helpers: open the calendar from the input and click in it ---
async function openPicker(page, selector) {
    await clickEl(page, selector);
    await page.waitForSelector('.flatpickr-calendar.open', { visible: true, timeout: 3000 });
}
async function pickDay(page, day) {
    const cell = await page.evaluateHandle((day) => [...document.querySelectorAll('.flatpickr-calendar.open .flatpickr-day:not(.prevMonthDay):not(.nextMonthDay)')]
        .find((d) => d.textContent.trim() === String(day)), day);
    if (!cell.asElement()) throw new Error(`no day ${day} in the open calendar`);
    await cell.click();
    await sleep(200);
}
async function pickMonth(page, label) {
    const cell = await page.evaluateHandle((label) => [...document.querySelectorAll('.flatpickr-calendar.open .flatpickr-monthSelect-month')]
        .find((m) => m.textContent.trim() === label), label);
    if (!cell.asElement()) throw new Error(`no month ${label} in the open calendar`);
    await cell.click();
    await sleep(200);
}
async function typeDate(page, selector, text) {
    await clickEl(page, selector);
    await page.evaluate((sel) => { document.querySelector(sel).select(); }, selector);
    await page.keyboard.type(text);
    await page.keyboard.press('Enter');
    await sleep(200);
    await page.keyboard.press('Escape');
}
const dayDisabled = (page, day) => page.evaluate((day) => {
    const d = [...document.querySelectorAll('.flatpickr-calendar.open .flatpickr-day:not(.prevMonthDay):not(.nextMonthDay)')].find((c) => c.textContent.trim() === String(day));
    return d ? d.classList.contains('flatpickr-disabled') : null;
}, day);
const closePicker = async (page) => { await page.keyboard.press('Escape'); await page.mouse.click(5, 5); await sleep(200); };
const value = (page, selector) => page.evaluate((sel) => document.querySelector(sel).value, selector);
const parsed = (page, selector) => page.evaluate((sel) => { const fp = document.querySelector(sel)._flatpickr; return !!fp && fp.selectedDates.length === 1; }, selector);

const isSelect2 = (page, selector) => page.evaluate((sel) => $(sel).length > 0 && $(sel).toArray().every((el) => $(el).hasClass('select2-hidden-accessible')), selector);

// --- run ---
const server = spawn('php', ['-S', `127.0.0.1:${PORT}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], {
    cwd: path.join(APP, 'public'),
    stdio: 'ignore',
});
process.on('exit', () => server.kill());

for (let i = 0; i < 60; i++) {
    try { await fetch(BASE); break; } catch { await sleep(250); }
}

const browser = await puppeteer.launch({ executablePath: findChrome(), headless: true, args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900 });
const jsErrors = [];
page.on('pageerror', (e) => jsErrors.push(String(e.message || e)));

try {
    await page.goto(`${BASE}/admin/auth/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name="username"]', 'admin');
    await page.type('input[name="password"]', 'admin');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);

    // --- pjax ---
    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    await mark(page);
    await waitPjax(page, () => page.evaluate(() => document.querySelector('a[href$="/admin/showcases/create"]').click()));
    check('pjax: link click swaps the content without a full reload', await notReloaded(page) && page.url().endsWith('/admin/showcases/create'), page.url());

    // --- select2: initialised after pjax, and in hasMany rows added later ---
    check('select2: initialised after pjax navigation', await isSelect2(page, 'select.select, select.multiple_select, select.tags'));
    await page.evaluate(() => document.querySelector('#has-many-items .add').click());
    await sleep(300);
    check('select2: initialised in a hasMany row added later', await isSelect2(page, '#has-many-items .has-many-items-form select.items.kind'));
    check('switch: rendered in a hasMany row added later', await page.evaluate(() => $('#has-many-items .has-many-items-form input.items.active.la_checkbox').length === 1));

    // --- select2: values picked in the UI reach the database ---
    const title = `Interactions ${Date.now()}`;
    await page.type('input#title', title);
    await select2Pick(page, 'select.select', 'Gamma');
    await select2Pick(page, 'select.multiple_select', 'Alpha');
    await select2Pick(page, 'select.multiple_select', 'Gamma');
    await select2Type(page, 'select.tags', 'red');
    await select2Type(page, 'select.tags', 'blue');
    await page.type('#has-many-items .has-many-items-form input.items.name', 'Row added in the UI');
    await select2Pick(page, '#has-many-items .has-many-items-form select.items.kind', 'Beta');
    await clickEl(page, 'input.switch.la_checkbox');
    await clickEl(page, '#has-many-items .has-many-items-form input.items.active.la_checkbox');
    await mark(page);
    await submit(page, { afterSave: 1 });
    const [row] = sql(`select * from showcases where title = '${title}'`);
    check('form: create + "continue editing" lands on the edit page via pjax', !!row && page.url().endsWith(`/admin/showcases/${row.id}/edit`) && await notReloaded(page), page.url());
    if (!row) {
        const shown = await page.evaluate(() => [...document.querySelectorAll('.invalid-feedback, .alert, .toast')].map((e) => e.textContent.trim().replace(/\s+/g, ' ')).join(' | '));
        throw new Error(`showcase was not created; stopping (page says: ${shown || 'nothing'})`);
    }
    check('select2 select: saved', row.select === 'c', row.select);
    check('select2 multipleSelect: saved', same(json(row.multiple_select), ['a', 'c']), row.multiple_select);
    // Tags::prepare() implodes the tags (then the model's array cast encodes that string)
    check('select2 tags: saved (new tags typed in)', json(row.tags) === 'red,blue', row.tags);
    check('switch: turned on, saved as the "on" value', row.switch === 1, row.switch);
    const items = sql(`select name, kind, active from showcase_items where showcase_id = ${row.id}`);
    check('select2 + switch in a hasMany row: saved', same(items, [{ name: 'Row added in the UI', kind: 'b', active: 1 }]), JSON.stringify(items));

    // --- select2: edit page shows the stored values ---
    await page.goto(`${BASE}/admin/showcases/${row.id}/edit`, { waitUntil: 'networkidle0' });
    const shown = await page.evaluate(() => ({
        select: $('select.select').val(),
        multiple: $('select.multiple_select').val(),
        tags: $('select.tags').val(),
        rendered: $('select.select').next('.select2').find('.select2-selection__rendered').text().trim(),
        switchOn: $('input.switch.la_checkbox').prop('checked'),
    }));
    check('switch: edit page shows it on', shown.switchOn === true, JSON.stringify(shown));
    check('select2: edit page shows stored values', shown.select === 'c' && same(shown.multiple, ['a', 'c']) && same(shown.tags, ['red', 'blue']) && shown.rendered.includes('Gamma'), JSON.stringify(shown));

    // --- select2: old input survives a validation error ---
    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await select2Pick(page, 'select.select', 'Beta');
    await select2Pick(page, 'select.multiple_select', 'Beta');
    await clickEl(page, 'input.switch.la_checkbox');
    await mark(page);
    await submit(page);
    const afterError = await page.evaluate(() => ({
        errors: document.querySelectorAll('.has-error').length,
        select: $('select.select').val(),
        multiple: $('select.multiple_select').val(),
        tags: $('select.tags').val(),
        switchOn: $('input.switch.la_checkbox').prop('checked'),
        switchHidden: $('input[type="hidden"][name="switch"]').val(),
    }));
    check('validation: error page via pjax, on the edit url', afterError.errors > 0 && page.url().endsWith(`/admin/showcases/${row.id}/edit`) && await notReloaded(page), `${page.url()} errors=${afterError.errors}`);
    check('select2: old input kept after a validation error', afterError.select === 'b' && same(afterError.multiple, ['a', 'b', 'c']) && same(afterError.tags, ['red', 'blue']), JSON.stringify(afterError));
    check('switch: old input kept after a validation error (turned off)', afterError.switchOn === false && afterError.switchHidden === 'off', JSON.stringify(afterError));
    const [unchanged] = sql(`select select_ from (select "select" as select_ from showcases where id = ${row.id})`);
    check('validation: nothing saved', unchanged.select_ === 'c', unchanged.select_);

    // a valid save with the switch off stores the "off" value
    await page.type('input#title', title);
    await submit(page, { afterSave: 1 });
    const [off] = sql(`select switch from showcases where id = ${row.id}`);
    check('switch: turned off, saved as the "off" value', off.switch === 0, off.switch);

    // grid switch: toggling it in the list saves through the inline-edit endpoint
    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    const gridSwitch = `input.grid-switch-switch[data-key="${row.id}"]`;
    const toggleInGrid = async () => {
        if (!(await page.$(gridSwitch))) throw new Error(`no grid switch ${gridSwitch}`);
        const saved = page.waitForResponse((r) => r.url().endsWith(`/admin/showcases/${row.id}`) && r.request().method() === 'POST', { timeout: 5000 });
        await clickEl(page, gridSwitch);
        return (await saved).status();
    };
    const onStatus = await toggleInGrid();
    const [gridOn] = sql(`select switch from showcases where id = ${row.id}`);
    check('grid switch: turned on in the list, saved', onStatus === 200 && gridOn.switch === 1, `http ${onStatus}, db ${gridOn.switch}`);
    const offStatus = await toggleInGrid();
    const [gridOff] = sql(`select switch from showcases where id = ${row.id}`);
    check('grid switch: turned off in the list, saved', offStatus === 200 && gridOff.switch === 0, `http ${offStatus}, db ${gridOff.switch}`);

    // cleanup
    sql(`delete from showcase_items where showcase_id = ${row.id}`);
    sql(`delete from showcases where id = ${row.id}`);
} catch (e) {
    check('run', false, e.message.split('\n')[0]);
}

// --- uploads (bootstrap-fileinput): files picked in the widget reach the disk and the database ---
try {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'la-uploads-'));
    const sampleJpg = path.join(APP, 'public/uploads/images/sample.jpg');
    const jpg = (name) => { const f = path.join(tmp, name); fs.copyFileSync(sampleJpg, f); return f; };
    const txt = (name, body) => { const f = path.join(tmp, name); fs.writeFileSync(f, body); return f; };
    const stored = (rel) => !!rel && fs.existsSync(path.join(APP, 'public/uploads', rel));
    const pick = async (field, files) => {
        const input = await page.$(`input.${field}[type="file"]`);
        if (!input) throw new Error(`no file input for ${field}`);
        await input.uploadFile(...files);
        await sleep(300);
    };
    const previews = (field) => page.evaluate((field) => $(`input.${field}[type="file"]`).closest('.form-group').find('.file-preview-frame')
        .filter((i, f) => !$(f).closest('.kv-zoom-cache').length) // hidden copies kept for the zoom modal
        .map((i, f) => $(f).find('img').attr('src') || $(f).text()).get(), field);

    const title = `Uploads ${Date.now()}`;
    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);
    await pick('image', [jpg('photo.jpg')]);
    await pick('file', [txt('notes.txt', 'first version')]);
    await pick('multiple_images', [jpg('one.jpg'), jpg('two.jpg')]);
    await pick('multiple_files', [txt('a.txt', 'a'), txt('b.txt', 'b')]);
    check('fileinput: picked files show a preview before saving', (await previews('image')).length === 1 && (await previews('multiple_images')).length === 2);
    await submit(page, { afterSave: 1 });
    const [up] = sql(`select * from showcases where title = '${title}'`);
    if (!up) throw new Error('upload showcase was not created');
    const multi = json(up.multiple_images) || [];
    check('fileinput image: stored on disk and in the db', stored(up.image), up.image);
    check('fileinput file: stored on disk and in the db', stored(up.file) && fs.readFileSync(path.join(APP, 'public/uploads', up.file), 'utf8') === 'first version', up.file);
    check('fileinput multipleImage: both stored', multi.length === 2 && multi.every(stored), up.multiple_images);
    const files = json(up.multiple_files) || [];
    check('fileinput multipleFile: both stored', files.length === 2 && files.every(stored), up.multiple_files);

    check('fileinput: edit page previews the stored files', (await previews('image')).some((s) => s.includes(up.image)) && (await previews('multiple_images')).length === 2,
        JSON.stringify(await previews('image')));

    // removable(): delete a stored file from its preview (fileinput event + sweetalert confirm)
    const del = await page.evaluateHandle(() => $('input.multiple_files[type="file"]').closest('.form-group')
        .find('.file-preview-frame').filter((i, f) => !$(f).closest('.kv-zoom-cache').length).first().find('.kv-file-remove')[0]);
    if (!del.asElement()) throw new Error('no delete button on the stored multiple_files preview');
    await del.evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await del.click();
    await page.waitForSelector('.swal2-confirm', { visible: true, timeout: 3000 });
    const deleted = page.waitForResponse((r) => r.url().endsWith(`/admin/showcases/${up.id}`) && r.request().method() === 'POST', { timeout: 5000 });
    await page.click('.swal2-confirm');
    await deleted;
    await sleep(800);
    const [afterDel] = sql(`select multiple_files from showcases where id = ${up.id}`);
    // MultipleFile::destroy() keeps the keys (1.x behaviour: the widget keeps sending the keys it
    // was rendered with, so reindexing would break a second delete without reloading), so the
    // column can become a JSON object: compare the values
    const left = Object.values(json(afterDel.multiple_files) || {});
    check('fileinput removable(): deleting a stored file updates the db and the disk', left.length === 1 && left[0] === files[1] && !stored(files[0]) && stored(files[1]),
        afterDel.multiple_files);
    check('fileinput removable(): the deleted preview is gone', (await previews('multiple_files')).length === 1, JSON.stringify(await previews('multiple_files')));

    // validation error: nothing changes, stored previews still there
    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await pick('image', [jpg('other.jpg')]);
    await submit(page);
    const [afterError] = sql(`select image from showcases where id = ${up.id}`);
    check('fileinput: validation error keeps the stored file (db + preview)', afterError.image === up.image && (await previews('image')).some((s) => s.includes(up.image)),
        `${afterError.image} ${JSON.stringify(await previews('image'))}`);

    // a rule on the upload itself (mimes) shows its error under the field
    await page.type('input#title', title);
    await pick('file', [jpg('not-a-text.jpg')]);
    await submit(page);
    const fileError = await page.evaluate(() => $('input.file[type="file"]').closest('.form-group').hasClass('has-error'));
    const [afterMimes] = sql(`select file from showcases where id = ${up.id}`);
    check('fileinput: mimes rule error shown under the field, nothing saved', fileError && afterMimes.file === up.file, `has-error=${fileError} ${afterMimes.file}`);

    // replacing a stored file
    await pick('file', [txt('notes-v2.txt', 'second version')]);
    await submit(page, { afterSave: 1 });
    const [replaced] = sql(`select file from showcases where id = ${up.id}`);
    check('fileinput file: replaced on edit', replaced.file !== up.file && stored(replaced.file)
        && fs.readFileSync(path.join(APP, 'public/uploads', replaced.file), 'utf8') === 'second version', replaced.file);
    console.log(`info old file after replacing it: ${stored(up.file) ? 'kept on disk' : 'deleted'}`);

    for (const rel of [up.image, up.file, replaced.file, ...multi, ...files]) {
        if (stored(rel)) fs.unlinkSync(path.join(APP, 'public/uploads', rel));
    }
    sql(`delete from showcases where id = ${up.id}`);
} catch (e) {
    check('uploads run', false, e.message.split('\n')[0]);
}

// --- dates (flatpickr behind the eonasdan options): values picked in the calendar reach the db ---
try {
    const now = new Date();
    const Y = now.getFullYear();
    const MM = String(now.getMonth() + 1).padStart(2, '0');
    const title = `Dates ${Date.now()}`;

    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);

    await openPicker(page, 'input.date');
    await pickDay(page, 15);
    check('flatpickr date: day clicked in the calendar, YYYY-MM-DD', await value(page, 'input.date') === `${Y}-${MM}-15`, await value(page, 'input.date'));
    await closePicker(page);

    await typeDate(page, 'input.datetime', '2026-03-04 05:06:07');
    await typeDate(page, 'input.time', '08:30:00');

    await openPicker(page, 'input.month');
    await pickMonth(page, 'Mar');
    check('flatpickr month (MM): month grid', await value(page, 'input.month') === '03', await value(page, 'input.month'));
    await closePicker(page);

    await openPicker(page, 'input.year');
    await clickEl(page, '.flatpickr-calendar.open .flatpickr-prev-month');
    await pickMonth(page, 'Jun');
    check('flatpickr year (YYYY, like sullair-click catalogs): previous year picked', await value(page, 'input.year') === String(Y - 1), await value(page, 'input.year'));
    await closePicker(page);

    await typeDate(page, 'input.date_start', `${Y}-${MM}-10`);
    await openPicker(page, 'input.date_end');
    const before = await dayDisabled(page, 9);
    const after = await dayDisabled(page, 11);
    check('flatpickr dateRange: end calendar starts at the start date', before === true && after === false, `9 disabled=${before}, 11 disabled=${after}`);
    await pickDay(page, 20);
    await closePicker(page);

    await typeDate(page, 'input.datetime_start', '2026-10-01 08:00:00');
    await typeDate(page, 'input.datetime_end', '2026-10-31 18:00:00');
    await typeDate(page, 'input.time_start', '09:00:00');
    await typeDate(page, 'input.time_end', '17:30:00');

    await openPicker(page, 'input.date_dmy');
    const weekdays = await page.evaluate(() => [...document.querySelectorAll('.flatpickr-calendar.open .flatpickr-weekday')].map((w) => w.textContent.trim()).join(' '));
    check('flatpickr locale (->options([locale => es])): Spanish calendar', /Lun/.test(weekdays), weekdays);
    await pickDay(page, 15);
    check('flatpickr DD/MM/YYYY (like sullair-click contests)', await value(page, 'input.date_dmy') === `15/${MM}/${Y}`, await value(page, 'input.date_dmy'));
    await closePicker(page);

    await submit(page, { afterSave: 1 });
    const [d] = sql(`select * from showcases where title = '${title}'`);
    if (!d) throw new Error('dates showcase was not created');
    const got = {
        date: d.date, datetime: d.datetime, time: d.time, month: d.month, year: d.year, date_dmy: d.date_dmy,
        date_start: d.date_start, date_end: d.date_end, datetime_start: d.datetime_start, datetime_end: d.datetime_end,
        time_start: d.time_start, time_end: d.time_end,
    };
    const want = {
        date: `${Y}-${MM}-15`, datetime: '2026-03-04 05:06:07', time: '08:30:00', month: '03', year: String(Y - 1), date_dmy: `15/${MM}/${Y}`,
        date_start: `${Y}-${MM}-10`, date_end: `${Y}-${MM}-20`, datetime_start: '2026-10-01 08:00:00', datetime_end: '2026-10-31 18:00:00',
        time_start: '09:00:00', time_end: '17:30:00',
    };
    for (const k of Object.keys(want)) {
        check(`flatpickr ${k}: saved`, String(got[k]) === want[k], `${got[k]} (want ${want[k]})`);
    }

    // edit page: every stored value is parsed back by its picker
    const fields = ['date', 'datetime', 'time', 'month', 'year', 'date_dmy', 'date_start', 'date_end', 'datetime_start', 'datetime_end', 'time_start', 'time_end'];
    const unparsed = [];
    for (const f of fields) {
        if (!(await parsed(page, `input.${f}`)) || await value(page, `input.${f}`) !== want[f]) unparsed.push(`${f}=${await value(page, `input.${f}`)}`);
    }
    check('flatpickr: edit page shows and parses every stored value', unparsed.length === 0, unparsed.join(' '));

    // validation error keeps the typed date
    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await typeDate(page, 'input.date', '2026-01-02');
    await submit(page);
    const [unchanged] = sql(`select date from showcases where id = ${d.id}`);
    check('flatpickr: old input kept after a validation error', await value(page, 'input.date') === '2026-01-02' && await parsed(page, 'input.date') && unchanged.date === want.date,
        `${await value(page, 'input.date')} db=${unchanged.date}`);

    sql(`delete from showcases where id = ${d.id}`);

    // grid filter between()->date() (like sullair-click's users filter)
    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    const filterReady = await page.evaluate(() => ['#date_start', '#date_end'].every((s) => document.querySelector(s)?._flatpickr?.config.dateFormat === 'Y-m-d'));
    check('flatpickr grid filter between()->date(): pickers with Y-m-d', filterReady);
    await page.evaluate(() => {
        document.querySelector('#date_start')._flatpickr.setDate('2026-10-11', true);
        document.querySelector('#date_end')._flatpickr.setDate('2026-10-11', true);
    });
    await waitPjax(page, () => page.evaluate(() => $('#date_start').closest('form').trigger('submit')));
    const rows = await page.evaluate(() => [...document.querySelectorAll('td.column-title')].map((t) => t.textContent.trim()).filter(Boolean)); // the totals row has an empty title cell
    check('flatpickr grid filter between()->date(): filters the rows', rows.length === 1 && rows[0].includes('Second'), `${page.url()} ${JSON.stringify(rows)}`);
} catch (e) {
    check('dates run', false, e.message.split('\n')[0]);
}

// --- checkbox / radio (native form-check, iCheck in 1.x) ---
try {
    const title = `Checks ${Date.now()}`;
    // click the label like a user does
    const clickOption = async (name, value) => {
        const label = await page.evaluateHandle((name, value) => document.querySelector(`input[name="${name}"][value="${value}"]`)?.closest('label'), name, value);
        if (!label.asElement()) throw new Error(`no option ${name}=${value}`);
        await label.evaluate((e) => e.scrollIntoView({ block: 'center' }));
        await label.click();
        await sleep(150);
    };
    const checkedValues = (name) => page.evaluate((name) => [...document.querySelectorAll(`input[name="${name}"]:checked`)].map((i) => i.value), name);
    const cascadeVisible = () => page.evaluate(() => {
        const g = document.querySelector('input.radio_extra')?.closest('.cascade-group');
        return !!g && !g.classList.contains('d-none');
    });

    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);
    await clickOption('radio', 'c');
    const shownForGamma = await cascadeVisible();
    await clickOption('radio', 'a');
    check('radio ->when(): cascade group follows the checked option', shownForGamma && !(await cascadeVisible()), `gamma=${shownForGamma}`);
    await clickOption('checkbox[]', 'a');
    await clickOption('checkbox[]', 'c');
    await clickOption('radio_button', 'c');
    await clickOption('radio_card', 'a');
    await clickOption('checkbox_button[]', 'b');
    await clickOption('checkbox_card[]', 'a');
    await clickOption('checkbox_card[]', 'b');
    await submit(page, { afterSave: 1 });
    const [c] = sql(`select * from showcases where title = '${title}'`);
    if (!c) throw new Error('checks showcase was not created');
    check('radio: saved', c.radio === 'a', c.radio);
    check('checkbox: saved', same(json(c.checkbox), ['a', 'c']), c.checkbox);
    check('radioButton / radioCard: saved', c.radio_button === 'c' && c.radio_card === 'a', `${c.radio_button} ${c.radio_card}`);
    check('checkboxButton / checkboxCard: saved', same(json(c.checkbox_button), ['b']) && same(json(c.checkbox_card), ['a', 'b']),
        `${c.checkbox_button} ${c.checkbox_card}`);

    const shown = {
        radio: await checkedValues('radio'),
        checkbox: await checkedValues('checkbox[]'),
        rb: await checkedValues('radio_button'),
        cc: await checkedValues('checkbox_card[]'),
    };
    check('checkbox / radio: edit page shows the stored values', same(shown, { radio: ['a'], checkbox: ['a', 'c'], rb: ['c'], cc: ['a', 'b'] }), JSON.stringify(shown));

    // validation error keeps what was clicked
    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await clickOption('radio', 'b');
    await clickOption('checkbox[]', 'a'); // uncheck
    await submit(page);
    const afterError = { radio: await checkedValues('radio'), checkbox: await checkedValues('checkbox[]') };
    check('checkbox / radio: old input kept after a validation error', same(afterError, { radio: ['b'], checkbox: ['c'] }), JSON.stringify(afterError));

    // check all, then uncheck all: the hidden input makes an empty selection reach the db
    await page.type('input#title', title);
    const checkAll = await page.$('input[class*="check-all-"]');
    await checkAll.evaluate((e) => e.scrollIntoView({ block: 'center' }));
    await checkAll.click();
    check('checkbox ->canCheckAll(): checks every option', same(await checkedValues('checkbox[]'), ['a', 'b', 'c']), JSON.stringify(await checkedValues('checkbox[]')));
    await checkAll.click();
    await submit(page, { afterSave: 1 });
    const [empty] = sql(`select checkbox, radio from showcases where id = ${c.id}`);
    check('checkbox: unchecking everything saves an empty list', same(json(empty.checkbox), []) && empty.radio === 'b', `${empty.checkbox} ${empty.radio}`);

    // grid: row selection drives the batch button, select-all, filter with checkboxes
    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    const rowBoxes = await page.$$('input[class$="-checkbox"][data-id]');
    await rowBoxes[0].click();
    await rowBoxes[1].click();
    const twoSelected = await page.evaluate(() => ({
        n: $.admin.grid.selected().length,
        btn: $('[class$="-btn"] .selected').filter(':visible').text().trim(),
    }));
    check('grid row checkboxes: selection + batch button count', twoSelected.n === 2 && /2/.test(twoSelected.btn), JSON.stringify(twoSelected));
    const selectAll = await page.$('thead input.form-check-input');
    await selectAll.click();
    const all = await page.evaluate(() => ({ n: $.admin.grid.selected().length, rows: $('input[class$="-checkbox"][data-id]').length }));
    await selectAll.click();
    const none = await page.evaluate(() => ({ n: $.admin.grid.selected().length, visible: $('[class$="-btn"] .selected').filter(':visible').length }));
    check('grid select-all: selects every row, then clears them', all.n === all.rows && none.n === 0 && none.visible === 0, `${JSON.stringify(all)} ${JSON.stringify(none)}`);

    sql(`update showcases set radio = 'a' where id = ${c.id}`); // the seeded rows are all 'b'
    await waitPjax(page, () => page.evaluate(() => {
        const i = document.querySelector('input[name="radio[]"][value="a"]');
        i.checked = true;
        $(i).closest('form').trigger('submit');
    }));
    const filtered = await page.evaluate(() => [...document.querySelectorAll('td.column-title')].map((t) => t.textContent.trim()).filter(Boolean));
    check('grid filter in()->checkbox(): filters the rows', filtered.length === 1 && filtered[0] === title, `${page.url()} ${JSON.stringify(filtered)}`);

    sql(`delete from showcases where id = ${c.id}`);
} catch (e) {
    check('checks run', false, e.message.split('\n')[0]);
}

// --- color (Coloris, bootstrap-colorpicker in 1.x) ---
try {
    const title = `Color ${Date.now()}`;
    const pickColor = async (text) => {
        await clickEl(page, 'input.color');
        await page.waitForSelector('#clr-picker.clr-open', { visible: true, timeout: 3000 });
        await page.click('#clr-color-value', { clickCount: 3 });
        await page.keyboard.type(text);
        // commit the typed value, then close by clicking elsewhere (Enter closes the picker
        // first, with Coloris' global format, so a translucent color would end up as #rrggbbaa)
        await page.evaluate(() => document.querySelector('#clr-color-value').dispatchEvent(new Event('change')));
        await page.evaluate(() => document.querySelector('.app-content-header h3').click());
        await sleep(300);
    };
    const swatchColor = () => page.evaluate(() => getComputedStyle(document.querySelector('input.color').closest('.input-group').querySelector('.la-color-swatch')).backgroundColor);

    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);
    await pickColor('rgba(0, 0, 255, 0.5)');
    const translucent = await value(page, 'input.color');
    check('color: a translucent color is written as rgba (bootstrap-colorpicker "auto")', /^rgba\(0, ?0, ?255, ?0?\.5\)$/.test(translucent), translucent);
    await pickColor('#ff0000');
    check('color: an opaque color is written as hex', await value(page, 'input.color') === '#ff0000', await value(page, 'input.color'));
    check('color: the addon swatch follows the value', await swatchColor() === 'rgb(255, 0, 0)', await swatchColor());
    await submit(page, { afterSave: 1 });
    const [c] = sql(`select id, color from showcases where title = '${title}'`);
    if (!c) throw new Error('color showcase was not created');
    check('color: saved', c.color === '#ff0000', c.color);
    check('color: edit page shows the value and its swatch', await value(page, 'input.color') === '#ff0000' && await swatchColor() === 'rgb(255, 0, 0)', `${await value(page, 'input.color')} ${await swatchColor()}`);
    sql(`delete from showcases where id = ${c.id}`);
} catch (e) {
    check('color run', false, e.message.split('\n')[0]);
}

// --- listbox (bootstrap4-duallistbox, bootstrap-duallistbox 3 in 1.x) ---
try {
    const title = `Listbox ${Date.now()}`;
    const box = (n) => `.bootstrap-duallistbox-container .box${n}`;
    const listed = (n) => page.evaluate((sel) => [...document.querySelectorAll(`${sel} select option`)].filter((o) => o.style.display !== 'none').map((o) => o.textContent.trim()), box(n));
    const moveOption = async (label) => {
        await page.evaluate((sel, label) => {
            const option = [...document.querySelectorAll(`${sel} select option`)].find((o) => o.textContent.trim() === label);
            option.selected = true;
            $(option).closest('select').trigger('change');
        }, box(1), label);
        await sleep(200); // moveOnSelect (default, as in 1.x): selecting an option moves it
    };
    const selectedValues = () => page.evaluate(() => $('select.listbox').val());

    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);
    const buttons = await page.evaluate(() => [...document.querySelectorAll('.bootstrap-duallistbox-container .buttons button')]
        .map((b) => b.offsetWidth > 0 && b.textContent.trim() !== ''));
    check('listbox: "move all" / "remove all" buttons are visible (glyphicons in 1.x)', buttons.length === 2 && buttons.every(Boolean), JSON.stringify(buttons));
    await moveOption('Beta');
    await moveOption('Gamma');
    check('listbox: selecting an option moves it to the right', same(await listed(2), ['Beta', 'Gamma']) && same(await listed(1), ['Alpha']), `${await listed(1)} | ${await listed(2)}`);
    await submit(page, { afterSave: 1 });
    const [l] = sql(`select id, listbox from showcases where title = '${title}'`);
    if (!l) throw new Error('listbox showcase was not created');
    check('listbox: saved', same(json(l.listbox), ['b', 'c']), l.listbox);
    check('listbox: edit page shows the stored values on the right', same(await listed(2), ['Beta', 'Gamma']), JSON.stringify(await listed(2)));

    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await moveOption('Alpha');
    await submit(page);
    check('listbox: old input kept after a validation error', same(await selectedValues(), ['a', 'b', 'c']) && same(await listed(2), ['Alpha', 'Beta', 'Gamma']), JSON.stringify(await selectedValues()));

    await page.type('input#title', title);
    await clickEl(page, `${box(2)} .removeall`);
    await submit(page, { afterSave: 1 });
    const [emptied] = sql(`select listbox from showcases where id = ${l.id}`);
    check('listbox: removing everything saves an empty list', same(json(emptied.listbox), []), emptied.listbox);
    sql(`delete from showcases where id = ${l.id}`);

    // the package's own role form uses a listbox for the permissions
    await page.goto(`${BASE}/admin/auth/roles/create`, { waitUntil: 'networkidle0' });
    check('listbox: role form (permissions) is initialised', await page.evaluate(() => $('.bootstrap-duallistbox-container').length === 1 && $('.bootstrap-duallistbox-container .box1 option').length > 0));
} catch (e) {
    check('listbox run', false, e.message.split('\n')[0]);
}

// --- icon (fontawesome-iconpicker kept: it stores Font Awesome 4 names, shown through the FA6 v4-shims) ---
try {
    const pickIcon = async (filter, icon) => {
        await clickEl(page, 'input.icon');
        await page.waitForSelector('.iconpicker-popover', { visible: true, timeout: 3000 });
        await page.type('.iconpicker-popover .iconpicker-search', filter);
        await sleep(300);
        const item = await page.$(`.iconpicker-popover .iconpicker-item[title=".${icon}"]`);
        if (!item) throw new Error(`no ${icon} in the iconpicker`);
        await item.click();
        await sleep(300);
    };
    const rendered = (selector) => page.evaluate((sel) => {
        const i = document.querySelector(sel);
        const content = i && getComputedStyle(i, '::before').content;
        return !!content && content !== 'none' && content !== 'normal' && content !== '""';
    }, selector);

    const title = `Icon ${Date.now()}`;
    await page.goto(`${BASE}/admin/showcases/create`, { waitUntil: 'networkidle0' });
    await page.type('input#title', title);
    const opacity = await page.evaluate(async () => {
        $('input.icon').trigger('focus').trigger('click');
        await new Promise((r) => setTimeout(r, 400));
        return getComputedStyle(document.querySelector('.iconpicker-popover')).opacity;
    });
    check('iconpicker: popover visible (Bootstrap 3 .in state class)', opacity === '1', opacity);
    await page.evaluate(() => document.querySelector('.app-content-header h3').click());
    await pickIcon('star', 'fa-star');
    check('iconpicker: picked icon written as a Font Awesome 4 name', await value(page, 'input.icon') === 'fa-star', await value(page, 'input.icon'));
    await submit(page, { afterSave: 1 });
    const [i] = sql(`select id, icon from showcases where title = '${title}'`);
    if (!i) throw new Error('icon showcase was not created');
    check('iconpicker: saved', i.icon === 'fa-star', i.icon);
    sql(`delete from showcases where id = ${i.id}`);

    // the package's menu editor: the stored name must render in the sidebar
    const menuTitle = `Menu ${Date.now()}`;
    await page.goto(`${BASE}/admin/auth/menu`, { waitUntil: 'networkidle0' });
    await page.type('input#title', menuTitle);
    await page.evaluate(() => { document.querySelector('input.icon').value = ''; });
    await pickIcon('area', 'fa-area-chart'); // renamed in FA5/6 (chart-area): needs the v4-shims
    await page.type('input#uri', 'showcases');
    await waitPjax(page, () => page.evaluate(() => [...document.querySelectorAll('button[type="submit"]')].find((b) => b.offsetParent).click()));
    const [m] = sql(`select id, icon from admin_menu where title = '${menuTitle}'`);
    check('menu editor: icon saved as a Font Awesome 4 name (compatible with 1.x databases)', !!m && m.icon === 'fa-area-chart', m && m.icon);
    await page.goto(`${BASE}/admin`, { waitUntil: 'networkidle0' });
    check('menu: a renamed FA4 icon renders in the sidebar through the v4-shims', await rendered('.sidebar-menu i.fa-area-chart'));
    if (m) sql(`delete from admin_menu where id = ${m.id}`);
} catch (e) {
    check('icon run', false, e.message.split('\n')[0]);
}

// --- grid ->editable() (x-editable in 1.x, now the inline-edit displayers on a BS5 popover) ---
try {
    const title = `Editable ${Date.now()}`;
    sql(`insert into showcases (title, "select", date, created_at, updated_at) values ('${title}', 'a', '2026-10-05', datetime('now'), datetime('now'))`);
    const [e] = sql(`select id from showcases where title = '${title}'`);
    const cell = (column) => `tr[data-key="${e.id}"] td.column-${column} .la-ie-trigger`;
    const openCell = async (column) => {
        await clickEl(page, cell(column));
        await page.waitForSelector('.popover.show .ie-content', { visible: true, timeout: 3000 });
    };
    const submitPopover = async () => {
        const saved = page.waitForResponse((r) => r.url().endsWith(`/admin/showcases/${e.id}`) && r.request().method() === 'POST', { timeout: 5000 });
        await page.click('.popover.show .ie-submit');
        const res = await saved;
        await sleep(400);
        return res.status();
    };
    const shownText = (column) => page.evaluate((sel) => document.querySelector(sel).closest('.ie-wrap').querySelector('.ie-display').textContent.trim(), cell(column));

    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    const hasRow = await page.$(`tr[data-key="${e.id}"]`);
    if (!hasRow) throw new Error(`row ${e.id} not in the grid`);

    // text
    await openCell('title');
    await page.click('.popover.show .ie-input', { clickCount: 3 });
    await page.keyboard.type(`${title} (edited)`);
    const textStatus = await submitPopover();
    const [t] = sql(`select title from showcases where id = ${e.id}`);
    check('grid editable(): text saved from the popover', textStatus === 200 && t.title === `${title} (edited)` && await shownText('title') === `${title} (edited)`,
        `${textStatus} ${t.title} | ${await shownText('title')}`);
    check('grid editable(): popover closes after saving', await page.$('.popover.show') === null);

    // validation error stays in the popover (title is required)
    await openCell('title');
    await page.click('.popover.show .ie-input', { clickCount: 3 });
    await page.keyboard.press('Backspace');
    const errStatus = await submitPopover();
    const errText = await page.evaluate(() => document.querySelector('.popover.show .error')?.textContent.trim());
    const [same1] = sql(`select title from showcases where id = ${e.id}`);
    check('grid editable(): validation error shown in the popover, nothing saved', errStatus === 422 && /required/i.test(errText || '') && same1.title === `${title} (edited)`,
        `${errStatus} "${errText}" ${same1.title}`);
    await page.click('.popover.show .ie-cancel');
    await sleep(300);
    check('grid editable(): cancel closes the popover', await page.$('.popover.show') === null);

    // select
    await openCell('select');
    await page.select('.popover.show select.ie-input', 'c');
    const selStatus = await submitPopover();
    const [sel] = sql(`select "select" as v from showcases where id = ${e.id}`);
    check('grid editable("select"): saved, label shown', selStatus === 200 && sel.v === 'c' && await shownText('select') === 'Gamma', `${selStatus} ${sel.v} ${await shownText('select')}`);

    // date: flatpickr calendar inside the popover
    await openCell('date');
    await page.waitForSelector('.popover.show .flatpickr-calendar', { visible: true, timeout: 3000 });
    const day = await page.evaluateHandle(() => [...document.querySelectorAll('.popover.show .flatpickr-day:not(.prevMonthDay):not(.nextMonthDay)')].find((d) => d.textContent.trim() === '20'));
    await day.click();
    await sleep(200);
    const dateStatus = await submitPopover();
    const [d] = sql(`select date from showcases where id = ${e.id}`);
    check('grid editable("date"): day picked in the popover calendar, saved', dateStatus === 200 && d.date === '2026-10-20' && await shownText('date') === '2026-10-20',
        `${dateStatus} ${d.date} ${await shownText('date')}`);

    sql(`delete from showcases where id = ${e.id}`);
} catch (err) {
    check('editable run', false, err.message.split('\n')[0]);
}

const unexpected = jsErrors;
check('no JS errors', unexpected.length === 0, unexpected.join(' | '));

await browser.close();
server.kill();
process.exit(failures);
