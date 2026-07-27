import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E config for WidgetNif.
 *
 * Playwright is provided by the /GitHub workspace root — no local
 * @playwright/test devDependency here. Node ancestor lookup resolves
 * the binary from /GitHub/node_modules/.bin/playwright.
 *
 * Requires a running FacturaScripts instance at baseURL, and
 * admin/admin1234 credentials (override via TEST_USER / TEST_PASS).
 */
export default defineConfig({
  testDir: './Test/E2E',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: [['html'], ['list']],
  use: {
    baseURL: process.env.FS_BASE_URL || 'http://localhost/facturas/',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'on-first-retry',
  },
  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.ts/,
    },
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        storageState: './Test/E2E/.auth/admin.json',
      },
      dependencies: ['setup'],
    },
  ],
});
