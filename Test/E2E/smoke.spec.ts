import { test, expect } from '@playwright/test';

/**
 * Smoke placeholder for WidgetNif E2E suite.
 * Delete or expand once real specs land.
 */
test('WidgetNif FS root loads with authenticated session', async ({ page }) => {
  const response = await page.goto('./');
  expect(response?.ok()).toBeTruthy();
  await expect(page.locator('form#login-form')).toHaveCount(0);
});
