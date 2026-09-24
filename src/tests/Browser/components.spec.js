import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const visit = async page => {
    page.on('pageerror', error => console.log('BROWSER ERROR:', error.message));
    await page.goto('/dev/components');
    await expect(page.getByRole('heading', { level: 1 })).toContainText('A common language');
    await page.waitForFunction(() => window.Alpine);
    await page.evaluate(() => document.fonts.ready);
};

test('gallery has no script errors or WCAG AA violations', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await visit(page);
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(results.violations).toEqual([]);
    expect(errors).toEqual([]);
    await expect(page.locator('link[rel=preload][as=font]')).toHaveCount(1);
    expect(await page.evaluate(() => document.fonts.check('16px "Lilita One"'))).toBe(true);
    await page.screenshot({ path: '/tmp/clash-components-desktop.png', fullPage: true });
});

test('dialog traps focus, closes on Escape, and restores its trigger', async ({ page }) => {
    await visit(page);
    const trigger = page.getByRole('button', { name: 'Open dialog', exact: true });
    await trigger.click();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(page.getByLabel('Collection name')).toBeFocused();
    for (let i = 0; i < 7; i++) {
        await page.keyboard.press('Tab');
        expect(await dialog.evaluate(el => el.contains(document.activeElement))).toBe(true);
    }
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(results.violations).toEqual([]);
    await page.keyboard.press('Escape');
    await expect(dialog).not.toBeVisible();
    await expect(trigger).toBeFocused();
});

test('menus and tabs work with the keyboard', async ({ page }) => {
    await visit(page);
    const trigger = page.getByRole('button', { name: 'More options' });
    await trigger.focus();
    await page.keyboard.press('ArrowDown');
    await expect(page.getByRole('menuitem', { name: 'Save to collection' })).toBeFocused();
    await page.keyboard.press('ArrowDown');
    await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeFocused();
    await page.keyboard.press('ArrowDown');
    await expect(page.getByRole('menuitem', { name: 'Save to collection' })).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(trigger).toBeFocused();
    await expect(page.getByRole('menu')).not.toBeVisible();
    const tabs = page.locator('#tabs-underline-tab-overview');
    await tabs.focus();
    await page.keyboard.press('ArrowRight');
    await expect(page.locator('#tabs-underline-tab-details')).toHaveAttribute('aria-selected', 'true');
    await expect(page.locator('#tabs-underline-panel-details')).toBeVisible();
    await expect(page.locator('#tabs-underline-panel-overview')).not.toBeVisible();
    await page.keyboard.press('End');
    await expect(page.locator('#tabs-underline-tab-history')).toBeFocused();
});

test('form feedback, searchable select, fallback, and dismiss controls work', async ({ page }) => {
    await visit(page);
    await expect(page.getByLabel('Invalid player tag', { exact: true })).toHaveAttribute('aria-invalid', 'true');
    await expect(page.getByLabel('Invalid player tag', { exact: true })).toHaveAttribute('aria-describedby', 'invalid-tag-error');
    await page.getByLabel('About you', { exact: true }).fill('Hello world');
    await expect(page.locator('#bio-count')).toContainText('11 / 120');
    await page.locator('#search-category').click();
    await page.getByRole('combobox', { name: 'Search Searchable category options' }).fill('trophy');
    await expect(page.getByRole('option')).toHaveCount(1);
    await page.getByRole('option', { name: 'Trophy' }).click();
    await expect(page.locator('#search-category-native')).toHaveValue('trophy');
    await expect(page.getByRole('img', { name: 'Image fallback' }).locator('img')).not.toBeVisible();
    await page.getByRole('button', { name: 'Remove Farming' }).click();
    await expect(page.getByRole('button', { name: 'Remove Farming' })).not.toBeVisible();
    await page.getByRole('button', { name: 'Dismiss notification' }).first().click();
    await expect(page.getByText('Your draft is saved here.')).not.toBeVisible();
    await page.getByRole('button', { name: 'Top hint' }).focus();
    await expect(page.getByRole('tooltip', { name: 'Helpful context, top' })).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.getByRole('tooltip', { name: 'Helpful context, top' })).not.toBeVisible();
});

test('360px layout has no overflow and honors reduced motion', async ({ page }) => {
    await page.setViewportSize({ width: 360, height: 800 });
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await visit(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    expect(await page.locator('.ui-skeleton').first().evaluate(el => getComputedStyle(el, '::after').animationName)).toBe('none');
    for (const position of ['Top', 'Bottom', 'Left', 'Right']) {
        await page.getByRole('button', { name: `${position} hint` }).focus();
        await expect(page.getByRole('tooltip')).toBeVisible();
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
        await page.keyboard.press('Escape');
    }
    await page.getByRole('button', { name: 'Open dialog', exact: true }).click();
    const box = await page.getByRole('dialog').boundingBox();
    expect(Math.round(box.y + box.height)).toBe(800);
    await page.keyboard.press('Escape');
    await page.screenshot({ path: '/tmp/clash-components-mobile.png', fullPage: true });
});


test('select supports keyboard, empty results, clearing, and disabled state', async ({ page }) => {
    await visit(page);
    const trigger = page.locator('#search-category');
    await trigger.focus();
    await page.keyboard.press('ArrowDown');
    const search = page.getByRole('combobox', { name: 'Search Searchable category options' });
    await expect(search).toBeFocused();
    const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    expect(results.violations).toEqual([]);
    await search.fill('missing');
    await expect(page.getByText('No matching options.', { exact: true }).filter({ visible: true })).toBeVisible();
    await search.fill('');
    await page.keyboard.press('End');
    await page.keyboard.press('Enter');
    await expect(page.locator('#search-category-native')).toHaveValue('trophy');
    await expect(trigger).toBeFocused();
    await page.getByRole('button', { name: 'Clear Searchable category', exact: true }).click();
    await expect(page.locator('#search-category-native')).toHaveValue('');
    await trigger.click();
    await page.keyboard.press('Escape');
    await expect(trigger).toBeFocused();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await trigger.click();
    await page.getByRole('heading', { level: 1 }).click();
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(page.locator('#disabled-select')).toBeDisabled();
});
