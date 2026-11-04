#!/usr/bin/env node
//
// Screenshot every admin screen (plus open dropdowns, pickers, modals and popovers)
// of an app prepared with tests/smoke/setup-app.sh + tests/smoke/ui/setup-ui.sh.
// Also records JS errors, failed requests and console errors per shot in report.json,
// so two runs (e.g. Bootstrap 3 on master vs Bootstrap 5 on ui-upgrade) can be compared.
//
// usage: (cd tests/smoke/ui && npm install)
//        node tests/smoke/ui/screenshots.mjs <app-dir> <out-dir> [port]
//
// CHROME_PATH overrides the browser (defaults to the newest Chrome in ~/.cache/puppeteer).
//
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import puppeteer from 'puppeteer-core';

const [appArg, outArg, portArg] = process.argv.slice(2);
if (!appArg || !outArg) {
    console.error('usage: node screenshots.mjs <app-dir> <out-dir> [port]');
    process.exit(2);
}
const APP = path.resolve(appArg);
const OUT = path.resolve(outArg);
const PORT = Number(portArg || 8766);
const BASE = `http://127.0.0.1:${PORT}`;
const DESKTOP = { width: 1440, height: 900 };
const MOBILE = { width: 390, height: 844, isMobile: true, hasTouch: true, deviceScaleFactor: 2 };

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
    throw new Error('Chrome not found: set CHROME_PATH');
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const click = async (page, selector) => {
    await page.waitForSelector(selector, { visible: true, timeout: 3000 });
    await page.click(selector);
    await sleep(600);
};
const focus = async (page, selector) => {
    await page.waitForSelector(selector, { timeout: 3000 });
    await page.focus(selector);
    await page.click(selector).catch(() => {});
    await sleep(600);
};

// [name, uri, interaction?, options?]
const SHOTS = [
    ['01-login', '/admin/auth/login', null, { guest: true }],
    ['02-dashboard', '/admin'],
    ['03-dashboard-sidebar-collapsed', '/admin', (p) => click(p, '.sidebar-toggle')],
    ['04-navbar-user-menu', '/admin', (p) => click(p, '.user-menu > a')],
    ['10-users-grid', '/admin/auth/users'],
    ['11-users-create', '/admin/auth/users/create'],
    ['12-users-edit', '/admin/auth/users/1/edit'],
    ['13-users-show', '/admin/auth/users/1'],
    ['14-users-create-select2-open', '/admin/auth/users/create', (p) => click(p, '.select2-selection')],
    ['15-roles-grid', '/admin/auth/roles'],
    ['16-roles-create-listbox', '/admin/auth/roles/create'],
    ['17-permissions-grid', '/admin/auth/permissions'],
    ['18-menu-tree', '/admin/auth/menu'],
    ['19-menu-iconpicker-open', '/admin/auth/menu', (p) => focus(p, 'input[name="icon"]')],
    ['20-logs-grid', '/admin/auth/logs'],
    ['21-setting', '/admin/auth/setting'],
    ['30-showcase-grid', '/admin/showcases'],
    ['31-showcase-grid-row-dropdown', '/admin/showcases', (p) => click(p, '.grid-dropdown-actions .dropdown-toggle')],
    ['32-showcase-grid-row-action-modal', '/admin/showcases', async (p) => {
        await click(p, '.grid-dropdown-actions .dropdown-toggle');
        await p.evaluate(() => [...document.querySelectorAll('.grid-dropdown-actions .dropdown-menu a')].find((a) => a.textContent.includes('Row action'))?.click());
        await sleep(800);
    }],
    ['33-showcase-grid-inline-edit', '/admin/showcases', (p) => click(p, 'td.column-title > a, td.column-title .ie-wrap > a')],
    ['34-showcase-grid-inline-date', '/admin/showcases', (p) => click(p, 'td.column-date > a, td.column-date .ie-wrap > a')],
    ['35-showcase-grid-column-filter', '/admin/showcases', (p) => click(p, 'th.column-title .dropdown-toggle')],
    ['36-showcase-grid-quick-create', '/admin/showcases', (p) => click(p, '.quick-create .create')],
    ['37-showcase-grid-modal', '/admin/showcases', (p) => click(p, 'td.column-color [data-toggle="modal"]')],
    ['38-showcase-grid-expand', '/admin/showcases', async (p) => { await click(p, 'td.column-mobile [data-toggle="collapse"]'); await sleep(600); }],
    ['39-showcase-grid-delete-confirm', '/admin/showcases', async (p) => {
        await click(p, '.grid-dropdown-actions .dropdown-toggle');
        await p.evaluate(() => [...document.querySelectorAll('.grid-dropdown-actions .dropdown-menu a')].find((a) => /delete/i.test(a.textContent))?.click());
        await sleep(800);
    }],
    ['40-showcase-grid-batch-dropdown', '/admin/showcases', async (p) => {
        await p.evaluate(() => document.querySelector('.grid-select-all')?.click());
        await sleep(300);
        await click(p, '.grid-select-all-btn .dropdown-toggle, .grid-batch-actions .dropdown-toggle, .btn-group .dropdown-toggle');
    }],
    ['41-showcase-grid-column-selector', '/admin/showcases', (p) => click(p, '.column-selector .dropdown-toggle, .grid-column-selector .dropdown-toggle')],
    ['50-showcase-create', '/admin/showcases/create'],
    ['51-showcase-edit', '/admin/showcases/1/edit'],
    ['52-showcase-show', '/admin/showcases/1'],
    ['53-showcase-form-datetimepicker', '/admin/showcases/create', (p) => focus(p, 'input#datetime')],
    ['54-showcase-form-daterange', '/admin/showcases/create', (p) => focus(p, 'input[name="date_start"]')],
    ['55-showcase-form-flatpickr', '/admin/showcases/create', (p) => focus(p, 'input[name="date_multiple"]')],
    ['56-showcase-form-colorpicker', '/admin/showcases/create', (p) => focus(p, 'input#color')],
    ['57-showcase-form-iconpicker', '/admin/showcases/create', (p) => focus(p, 'input#icon')],
    ['58-showcase-form-select2-tags', '/admin/showcases/1/edit', (p) => click(p, '.form-group:has(select[name="tags[]"]) .select2-selection')],
    ['59-showcase-form-validation', '/admin/showcases/create', async (p) => {
        // skip HTML5 validation so the server-side errors get rendered
        await p.evaluate(() => { document.querySelectorAll('form').forEach((f) => { f.noValidate = true; }); });
        await Promise.all([
            p.waitForNavigation({ timeout: 8000 }).catch(() => {}),
            p.evaluate(() => [...document.querySelectorAll('button[type="submit"]')].find((b) => b.offsetParent)?.click()),
        ]);
        await sleep(800);
    }],
    ['60-widgets', '/admin/showcase-widgets'],
    ['61-widgets-tab-dropdown', '/admin/showcase-widgets', (p) => click(p, '.nav-tabs .dropdown-toggle')],
    ['70-mobile-login', '/admin/auth/login', null, { guest: true, viewport: MOBILE }],
    ['71-mobile-dashboard', '/admin', null, { viewport: MOBILE }],
    ['72-mobile-sidebar-open', '/admin', (p) => click(p, '.sidebar-toggle'), { viewport: MOBILE }],
    ['73-mobile-showcase-grid', '/admin/showcases', null, { viewport: MOBILE }],
    ['74-mobile-showcase-form', '/admin/showcases/create', null, { viewport: MOBILE }],
];

async function waitForServer() {
    for (let i = 0; i < 60; i++) {
        try {
            await fetch(BASE);
            return;
        } catch {
            await sleep(250);
        }
    }
    throw new Error(`server did not start on ${BASE}`);
}

async function login(page) {
    await page.goto(`${BASE}/admin/auth/login`, { waitUntil: 'networkidle0' });
    await page.type('input[name="username"]', 'admin');
    await page.type('input[name="password"]', 'admin');
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type="submit"]')]);
    if (page.url().includes('/auth/login')) throw new Error('login failed');
}

const server = spawn('php', ['-S', `127.0.0.1:${PORT}`, '../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'], {
    cwd: path.join(APP, 'public'),
    stdio: 'ignore',
});
process.on('exit', () => server.kill());

fs.mkdirSync(OUT, { recursive: true });
const report = { base: BASE, generated: new Date().toISOString(), shots: {} };
let failures = 0;

try {
    await waitForServer();
    const browser = await puppeteer.launch({ executablePath: findChrome(), headless: true, args: ['--no-sandbox', '--font-render-hinting=none'] });
    const guest = await browser.createBrowserContext();
    const authed = await browser.createBrowserContext();
    const loginPage = await authed.newPage();
    await login(loginPage);
    await loginPage.close();

    for (const [name, uri, interact, opts = {}] of SHOTS) {
        const page = await (opts.guest ? guest : authed).newPage();
        await page.setViewport(opts.viewport || DESKTOP);
        await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
        const log = { uri, jsErrors: [], consoleErrors: [], failedRequests: [], interaction: interact ? 'ok' : null };
        page.on('pageerror', (e) => log.jsErrors.push(String(e.message || e)));
        page.on('console', (m) => m.type() === 'error' && log.consoleErrors.push(m.text()));
        page.on('requestfailed', (r) => log.failedRequests.push(`${r.url()} ${r.failure()?.errorText}`));
        page.on('response', (r) => r.status() >= 400 && log.failedRequests.push(`${r.status()} ${r.url()}`));
        try {
            const res = await page.goto(BASE + uri, { waitUntil: 'networkidle0', timeout: 20000 });
            log.status = res?.status();
            await sleep(500);
            if (interact) {
                try {
                    await interact(page);
                } catch (e) {
                    log.interaction = `FAILED: ${e.message.split('\n')[0]}`;
                }
            }
            await page.screenshot({ path: path.join(OUT, `${name}.png`), fullPage: !interact || name.startsWith('3') || name.startsWith('5') });
        } catch (e) {
            log.error = e.message.split('\n')[0];
        }
        const bad = log.error || log.jsErrors.length || log.failedRequests.length || (log.interaction && log.interaction !== 'ok');
        if (bad) failures++;
        console.log(`${bad ? 'WARN' : 'ok  '} ${name}${log.interaction && log.interaction !== 'ok' ? ` (${log.interaction})` : ''}${log.jsErrors.length ? ` js:${log.jsErrors.length}` : ''}${log.failedRequests.length ? ` http:${log.failedRequests.length}` : ''}${log.error ? ` ${log.error}` : ''}`);
        report.shots[name] = log;
        await page.close();
    }
    await browser.close();
} finally {
    fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 2));
    server.kill();
}

console.log(`\n${Object.keys(report.shots).length} shots in ${OUT}, ${failures} with warnings (see report.json)`);
