import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';

const TAG = '2PP0LJQ';

test.beforeAll(() => {
    const args = ['artisan', 'db:seed', '--class=CocAttachBrowserSeeder', '--force'];
    if (process.env.CI) execFileSync('php', args);
    else execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', ...args]);
});

test('attach + token verification flow resolves and detaches', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill('coc-attach-browser@example.test');
    await page.getByLabel('Password', { exact: true }).fill('browser-fixture-passphrase');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL('/');

    await page.goto('/accounts');
    await expect(page.getByRole('heading', { name: 'No accounts attached yet' })).toBeVisible();

    // Step 1: look up the tag. This is the regression guard — the name collision made wire:submit
    // fall back to a native form submit, which reloaded the page and never showed the confirmation.
    await page.getByLabel('Player tag', { exact: true }).fill(TAG);
    await page.getByRole('button', { name: 'Look up account', exact: true }).click();

    await expect(page.getByRole('heading', { name: 'Is this you?' })).toBeVisible();
    await expect(page).toHaveURL(/\/accounts$/); // stayed put; no native submit reload
    await expect(page.getByText(`#${TAG}`)).toBeVisible();
    await expect(page.getByText('Town Hall')).toBeVisible();
    expect(errors).toEqual([]);

    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()).violations).toEqual([]);

    // Step 2: verify with an in-game token (the fake CoC client accepts it).
    await page.getByLabel('In-game API token', { exact: true }).fill('fresh-token');
    await page.getByRole('button', { name: 'Verify ownership', exact: true }).click();

    await expect(page.getByRole('status').filter({ hasText: 'Verified' })).toBeVisible();
    const row = page.locator('.account-row', { hasText: `#${TAG}` });
    await expect(row).toBeVisible();
    await expect(row.getByText('Verified', { exact: true })).toBeVisible();
    await expect(row.getByText('Featured', { exact: true })).toBeVisible();

    // No horizontal overflow across phone, landscape and tablet widths.
    for (const viewport of [{ width: 375, height: 812 }, { width: 812, height: 375 }, { width: 768, height: 1024 }]) {
        await page.setViewportSize(viewport);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }
    await page.setViewportSize({ width: 1440, height: 1000 });

    // Detach releases the account after password confirmation and returns to the empty state.
    await row.getByRole('button', { name: 'Detach', exact: true }).click();
    await row.getByLabel(/Confirm your password/).fill('browser-fixture-passphrase');
    await row.getByRole('button', { name: 'Release account', exact: true }).click();

    await expect(page.getByRole('status').filter({ hasText: 'released' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'No accounts attached yet' })).toBeVisible();

    expect(errors).toEqual([]);
});
