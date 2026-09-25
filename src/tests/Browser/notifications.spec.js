import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';

test.beforeAll(() => {
    const args = ['artisan', 'db:seed', '--class=NotificationBrowserSeeder', '--force'];
    if (process.env.CI) execFileSync('php', args);
    else execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', ...args]);
});

test('notification inbox and bell work on desktop and mobile', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill('notification-browser@example.test');
    await page.getByLabel('Password', { exact: true }).fill('browser-fixture-passphrase');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL('/');
    const bell = page.locator('.notification-bell summary');
    await bell.focus();
    await page.keyboard.press('Enter');
    await expect(page.getByRole('region', { name: 'Recent notifications' })).toBeVisible();
    await expect(page.locator('.notification-popover .notification-item')).toHaveCount(10);
    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()).violations).toEqual([]);
    await page.keyboard.press('Escape');
    await expect(bell).toBeFocused();
    await expect(page.getByRole('region', { name: 'Recent notifications' })).not.toBeVisible();
    await bell.click();
    await page.getByRole('link', { name: 'View all', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Notifications', level: 1 })).toBeVisible();
    await expect(page.locator('.notification-page .notification-item')).toHaveCount(20);
    await page.locator('.notification-page').getByRole('link', { name: /Next/ }).click();
    await expect(page).toHaveURL(/page=2/);
    await page.getByRole('link', { name: 'Moderation', exact: true }).click();
    await expect(page.locator('.notification-page .notification-title').first()).toHaveText('You received an account warning');
    await page.locator('.notification-page .notification-open').first().click();
    await expect(page.getByRole('status').filter({ hasText: 'A moderator issued' })).toBeVisible();
    await page.getByRole('link', { name: 'Security', exact: true }).click();
    await page.locator('.notification-page .notification-open').first().click();
    await expect(page).toHaveURL(/settings\/sessions/);
    await page.goto('/notifications');
    expect((await page.request.post('/notifications/read-all')).status()).toBe(419);
    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()).violations).toEqual([]);
    await page.screenshot({ path: '/tmp/clash-notifications-desktop.png', fullPage: true });
    for (const viewport of [{ width: 375, height: 812 }, { width: 812, height: 375 }, { width: 768, height: 1024 }]) {
        await page.setViewportSize(viewport);
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await bell.click();
        const panel = page.locator('.notification-popover');
        await expect(panel).toBeVisible();
        const box = await panel.boundingBox();
        expect(box.x).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width).toBeLessThanOrEqual(viewport.width);
        expect(await page.evaluate(() => [...document.querySelectorAll('body *')].filter(el => el.getBoundingClientRect().right > innerWidth + 1).map(el => ({ tag: el.tagName, class: el.className, right: el.getBoundingClientRect().right })).slice(0, 12))).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.keyboard.press('Escape');
    }
    await page.setViewportSize({ width: 375, height: 812 });
    await page.screenshot({ path: '/tmp/clash-notifications-mobile.png', fullPage: true });
    await page.locator('.notification-page').getByRole('button', { name: 'Mark all read', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'All notifications marked as read.' })).toBeVisible();
    await expect(bell).toHaveAttribute('aria-label', 'Notifications, 0 unread');
    await bell.click();
    await page.locator('.notification-popover').getByRole('link', { name: 'View all' }).click();
    await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    expect(errors).toEqual([]);
});
