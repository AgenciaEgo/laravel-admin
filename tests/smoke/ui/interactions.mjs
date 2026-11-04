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
    // TODO(phase 4): drop this stub. x-editable is not loaded until the grid phase and the
    // showcase grid uses ->editable(); its error would abort every page script after it
    // (they all run inside one $(function () {...})), the switch handler included.
    const stub = await page.evaluateOnNewDocument(() => {
        let jq;
        Object.defineProperty(window, 'jQuery', {
            configurable: true,
            get: () => jq,
            set: (v) => {
                jq = v;
                if (v && v.fn && !v.fn.editable) {
                    v.fn.editable = function () { return this; };
                    v.fn.editable.defaults = {};
                }
            },
        });
    });
    await page.goto(`${BASE}/admin/showcases`, { waitUntil: 'networkidle0' });
    await page.removeScriptToEvaluateOnNewDocument(stub.identifier);
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
    const left = json(afterDel.multiple_files) || [];
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

const unexpected = jsErrors.filter((e) => !e.includes('editable is not a function'));
check('no JS errors', unexpected.length === 0, unexpected.join(' | '));

await browser.close();
server.kill();
process.exit(failures);
