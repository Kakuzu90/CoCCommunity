import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const visitHome = async page => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/');
    await expect(page.getByRole('heading', { level: 1 })).toContainText("Prove it's your base.");
    await page.waitForFunction(() => window.Alpine);
    await page.evaluate(() => document.fonts.ready);
    return errors;
};

test('home shell renders with no script errors or WCAG AA violations', async ({ page }) => {
    const errors = await visitHome(page);
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(results.violations).toEqual([]);
    expect(errors).toEqual([]);
    await expect(page.getByText('This material is unofficial and is not endorsed by Supercell.')).toBeVisible();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
    await page.screenshot({ path: '/tmp/clash-shell-desktop.png', fullPage: true });
});

test('desktop shows the sidebar navigation and prominent search', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 1000 });
    await visitHome(page);
    const nav = page.getByRole('navigation', { name: 'Primary' });
    await expect(nav).toBeVisible();
    for (const label of ['Home', 'Bases', 'Recruit', 'Search']) {
        await expect(nav.getByRole('link', { name: label })).toBeVisible();
    }
    await expect(page.getByRole('searchbox', { name: /Search bases/ })).toBeVisible();
});

test('mobile shows the bottom tab bar with no horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 800 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await visitHome(page);
    const nav = page.getByRole('navigation', { name: 'Primary' });
    await expect(nav).toBeVisible();
    for (const label of ['Home', 'Bases', 'Recruit', 'Search', 'Log in']) {
        await expect(nav.getByRole('link', { name: label })).toBeVisible();
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: '/tmp/clash-shell-mobile.png', fullPage: true });
});
