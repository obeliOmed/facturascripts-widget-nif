# E2E tests — WidgetNif

Playwright suite for `WidgetNif`. Config in `../../playwright.config.ts`. Playwright is resolved from the `/GitHub` workspace root (no local `@playwright/test` devDep).

## Running

```powershell
cd WidgetNif
npx playwright test              # headless
npx playwright test --ui         # UI mode
npx playwright test --headed     # visible browser
npx playwright show-report       # last run HTML report
```

## Requirements

- XAMPP running with FacturaScripts at `http://localhost/facturas/`.
- Plugin `WidgetNif` activated in FS.
- `admin` / `admin1234` credentials (or export `TEST_USER` / `TEST_PASS`).
- `FS_BASE_URL` optional (overrides default baseURL).

## Structure

- `auth.setup.ts` — one-time FS admin login. Storage state persisted to `.auth/admin.json` (gitignored).
- `smoke.spec.ts` — placeholder smoke test. Delete or extend once real specs land.
- `<feature>.spec.ts` — one file per view or workflow.

## Adding a spec

1. Create `Test/E2E/<feature>.spec.ts`.
2. Import `test, expect` from `@playwright/test`.
3. The authenticated session is auto-applied via the `chromium` project dependency on `setup`.
