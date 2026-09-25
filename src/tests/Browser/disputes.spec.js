import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';

const TAG = '8G9V2L';

test.beforeEach(() => {
    const args = ['artisan', 'db:seed', '--class=CocDisputeBrowserSeeder', '--force'];
    if (process.env.CI) execFileSync('php', args);
    else execFileSync('docker', ['compose', 'exec', '-T', 'app', 'php', ...args]);
});

async function login(page, email) {
    await page.goto('/login');
    await page.getByLabel('Email', { exact: true }).fill(email);
    await page.getByLabel('Password', { exact: true }).fill('browser-fixture-passphrase');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL('/');
}

test('a blocked claimant opens a dispute and an admin transfers the tag', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));

    // Claimant hits the conflict and files a dispute. Regression guard: wire:submit on the dispute
    // form must not fall back to a native submit that reloads the page (specs/13 §4).
    await login(page, 'coc-dispute-claimant@example.test');
    await page.goto('/accounts');
    await page.getByLabel('Player tag', { exact: true }).fill(TAG);
    await page.getByRole('button', { name: 'Look up account', exact: true }).click();

    await expect(page.getByRole('heading', { name: 'Is this you?' })).toBeVisible();
    await expect(page.getByText('already verified')).toBeVisible();

    await page.getByRole('button', { name: /open a dispute/i }).click();
    await page.getByLabel('Why is this account yours?').fill('I recovered this account through Supercell support after losing my device last month.');
    await page.getByRole('button', { name: 'File dispute', exact: true }).click();

    await expect(page.getByRole('status').filter({ hasText: 'dispute was filed' })).toBeVisible();
    await expect(page).toHaveURL(/\/accounts$/); // no native submit reload
    expect(errors).toEqual([]);

    expect((await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze()).violations).toEqual([]);

    // Admin reviews the queue and transfers ownership (wire:confirm dialog is accepted).
    await login(page, 'coc-dispute-admin@example.test');
    await page.goto('/admin/disputes');
    await expect(page.getByRole('heading', { name: 'Ownership disputes' })).toBeVisible();
    await page.getByRole('link', { name: 'Review', exact: true }).first().click();

    await expect(page.getByRole('heading', { name: 'Review dispute' })).toBeVisible();
    await page.getByLabel('Decision note (required)').fill('Claimant details match our snapshot history for this tag.');
    page.once('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: 'Transfer to claimant', exact: true }).click();
    await expect(page.getByRole('status').filter({ hasText: 'transferred to the claimant' })).toBeVisible();

    // The claimant now holds the tag as verified.
    await login(page, 'coc-dispute-claimant@example.test');
    await page.goto('/accounts');
    const row = page.locator('.account-row', { hasText: `#${TAG}` });
    await expect(row).toBeVisible();
    await expect(row.getByText('Verified', { exact: true })).toBeVisible();

    for (const viewport of [{ width: 375, height: 812 }, { width: 768, height: 1024 }]) {
        await page.setViewportSize(viewport);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }

    expect(errors).toEqual([]);
});
