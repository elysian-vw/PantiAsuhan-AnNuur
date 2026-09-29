import { chromium, expect } from '@playwright/test';
import { readFile, mkdir } from 'node:fs/promises';

const access = JSON.parse(await readFile(new URL('../.runtime/admin-access.json', import.meta.url), 'utf8'));
const base = 'http://127.0.0.1:8000';
const browser = await chromium.launch({ channel: 'chrome', headless: true });
await mkdir(new URL('../.runtime/screenshots/', import.meta.url), { recursive: true });
const errors = [];
const checked = [];
try {
    for (const width of [360, 390, 768, 1440]) {
        const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
        page.on('pageerror', (error) => errors.push(width + ': ' + error.message));
        for (const path of ['/', '/profil', '/kebutuhan', '/donasi', '/bantuan', '/kunjungan', '/admin/login']) {
            const response = await page.goto(base + path);
            if (response.status() !== 200) throw new Error(path + ' returned ' + response.status());
            await page.waitForTimeout(250);
            const dimensions = await page.evaluate(() => ({
                scroll: document.documentElement.scrollWidth,
                viewport: window.innerWidth,
            }));
            if (dimensions.scroll > dimensions.viewport + 1)
                throw new Error('Horizontal overflow ' + width + ' ' + path + ': ' + JSON.stringify(dimensions));
            if (path === '/' && [390, 1440].includes(width))
                await page.screenshot({ path: '.runtime/screenshots/home-' + width + '.png', fullPage: true });
            if (path === '/' && width < 1000) {
                await page.getByRole('button', { name: 'Buka atau tutup menu' }).click();
                await expect(page.locator('#mobile-menu')).toBeVisible();
                await page.keyboard.press('Escape');
                await expect(page.locator('#mobile-menu')).toBeHidden();
            }
            if (path === '/bantuan') {
                await page.getByRole('button', { name: 'Tambah barang' }).click();
                if ((await page.locator('.item-form').count()) !== 2)
                    throw new Error('Multiple assistance items unavailable');
            }
            if (path === '/donasi') {
                await page.getByRole('button', { name: 'Rp100.000', exact: true }).click();
                if ((await page.locator('#nominal').inputValue()) !== '100000')
                    throw new Error('Donation amount selector failed');
                if (width === 390)
                    await page.screenshot({ path: '.runtime/screenshots/donation-mobile.png', fullPage: true });
            }
            checked.push(width + ' ' + path);
        }
        await page.locator('#email').fill(access.email);
        await page.locator('#password').fill(access.password);
        await Promise.all([
            page.waitForURL(base + '/admin'),
            page.getByRole('button', { name: 'Masuk dashboard' }).click(),
        ]);
        for (const path of [
            '/admin',
            '/admin/kalender',
            '/admin/data/pengguna',
            '/admin/data/kegiatan/tambah',
            '/admin/pengaturan',
            '/admin/laporan/donasi',
            '/admin/tamu-langsung',
        ]) {
            const response = await page.goto(base + path);
            if (response.status() !== 200) throw new Error(path + ' returned ' + response.status());
            await page.waitForTimeout(300);
            if (await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1))
                throw new Error('Admin overflow ' + width + ' ' + path);
            if (path === '/admin' && width === 390)
                await page.screenshot({ path: '.runtime/screenshots/admin-mobile.png', fullPage: true });
            checked.push(width + ' ' + path);
        }
        if (width === 390) {
            await page.getByRole('button', { name: 'Menu dashboard', exact: true }).click();
            await page.waitForTimeout(300);
            if (!(await page.locator('.sidebar').evaluate((el) => el.getBoundingClientRect().left >= 0)))
                throw new Error('Sidebar did not open');
        }
        await page.close();
    }
    if (errors.length) throw new Error(errors.join('\n'));
    console.log(
        JSON.stringify(
            { result: 'passed', pages: checked.length, widths: [360, 390, 768, 1440], javascriptErrors: errors },
            null,
            2,
        ),
    );
} finally {
    await browser.close();
}
