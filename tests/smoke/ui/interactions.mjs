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
    const items = sql(`select name, kind from showcase_items where showcase_id = ${row.id}`);
    check('select2 in hasMany row: saved', same(items, [{ name: 'Row added in the UI', kind: 'b' }]), JSON.stringify(items));

    // --- select2: edit page shows the stored values ---
    await page.goto(`${BASE}/admin/showcases/${row.id}/edit`, { waitUntil: 'networkidle0' });
    const shown = await page.evaluate(() => ({
        select: $('select.select').val(),
        multiple: $('select.multiple_select').val(),
        tags: $('select.tags').val(),
        rendered: $('select.select').next('.select2').find('.select2-selection__rendered').text().trim(),
    }));
    check('select2: edit page shows stored values', shown.select === 'c' && same(shown.multiple, ['a', 'c']) && same(shown.tags, ['red', 'blue']) && shown.rendered.includes('Gamma'), JSON.stringify(shown));

    // --- select2: old input survives a validation error ---
    await page.evaluate(() => { document.querySelector('input#title').value = ''; });
    await select2Pick(page, 'select.select', 'Beta');
    await select2Pick(page, 'select.multiple_select', 'Beta');
    await mark(page);
    await submit(page);
    const afterError = await page.evaluate(() => ({
        errors: document.querySelectorAll('.has-error').length,
        select: $('select.select').val(),
        multiple: $('select.multiple_select').val(),
        tags: $('select.tags').val(),
    }));
    check('validation: error page via pjax, on the edit url', afterError.errors > 0 && page.url().endsWith(`/admin/showcases/${row.id}/edit`) && await notReloaded(page), `${page.url()} errors=${afterError.errors}`);
    check('select2: old input kept after a validation error', afterError.select === 'b' && same(afterError.multiple, ['a', 'b', 'c']) && same(afterError.tags, ['red', 'blue']), JSON.stringify(afterError));
    const [unchanged] = sql(`select select_ from (select "select" as select_ from showcases where id = ${row.id})`);
    check('validation: nothing saved', unchanged.select_ === 'c', unchanged.select_);

    // cleanup
    sql(`delete from showcase_items where showcase_id = ${row.id}`);
    sql(`delete from showcases where id = ${row.id}`);
} catch (e) {
    check('run', false, e.message.split('\n')[0]);
}

const unexpected = jsErrors.filter((e) => !e.includes('editable is not a function'));
check('no JS errors', unexpected.length === 0, unexpected.join(' | '));

await browser.close();
server.kill();
process.exit(failures);
