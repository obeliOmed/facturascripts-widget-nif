import { test as setup, expect } from '@playwright/test';
import path from 'path';

const authFile = path.join(__dirname, '.auth', 'admin.json');

setup('authenticate as admin', async ({ page }) => {
  const user = process.env.TEST_USER || 'admin';
  const pass = process.env.TEST_PASS || 'admin1234';

  await page.goto('./');

  const loginForm = page.locator('form#login-form, form[action*="login"], form#formLogin, form[name="login"]');

  if (await loginForm.count() > 0) {
    await page.locator('input#fsNick, input[name="fsNick"]').first().fill(user);
    await page.locator('input#fsPassword, input[name="fsPassword"]').first().fill(pass);
    await page
      .locator('form#login-form button[type="submit"], form#login-form input[type="submit"], button[type="submit"], input[type="submit"]')
      .first()
      .click();
    await page.waitForLoadState('networkidle');
  }

  await expect(loginForm).toHaveCount(0);
  await page.context().storageState({ path: authFile });
});
