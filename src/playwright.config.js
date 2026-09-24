import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    webServer: process.env.CI ? {
        command: 'php artisan serve --host=127.0.0.1 --port=8000',
        url: 'http://127.0.0.1:8000/dev/components',
        reuseExistingServer: false,
    } : undefined,
    use: {
        baseURL: process.env.UI_BASE_URL || 'http://localhost:8080',
        viewport: { width: 1440, height: 1000 },
        launchOptions: process.platform === 'darwin'
            ? { executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' }
            : {},
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
});
