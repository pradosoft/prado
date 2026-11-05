// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for Prado functional tests.
 *
 * These tests are the Playwright equivalent of the Selenium functional tests
 * (composer functionaltest / phpunit --testsuite functional).
 *
 * Prerequisites:
 *   - Run `composer install` (or `composer devtools` for a full dev setup).
 *     prado-demos is a require-dev dependency and is installed automatically
 *     into vendor/pradosoft/prado-demos — no manual clone or symlink needed.
 *
 * The PHP built-in server on port 8037 is started automatically — no need
 * to start it manually before running tests.
 *
 * Run:
 *   composer functionaltest                         # all browsers (via npm run playwright)
 *   npx playwright test                             # all browsers
 *   npx playwright test --project=chromium          # Chromium only
 *   npx playwright test --project=firefox           # Firefox only
 *   npx playwright test --project=webkit            # WebKit/Safari only
 *   npx playwright test active-controls             # specific directory, all browsers
 */
export default defineConfig({
	testDir: './tests/playwright',
	testMatch: '**/*.spec.js',

	/*
	 * Clear Prado's published asset caches before the run.
	 * TAssetManager publishes framework JS/CSS into
	 * vendor/pradosoft/prado-demos/<demo>/assets/<hash>/ once and never
	 * invalidates that cache on source edits — without this step, tests
	 * silently run against stale prado.min.js and report false failures.
	 */
	globalSetup: './tests/playwright/global-setup.js',

	/* Maximum time one test can run (mirrors phpunit max_execution_time=1200) */
	timeout: 120_000,

	/* Fail the build on CI if you accidentally left test.only in the source code */
	forbidOnly: !!process.env.CI,

	/* No retries — mirrors original Selenium suite behaviour */
	retries: 0,

	/* Sequential execution — mirrors Selenium's single shared browser session */
	workers: 1,

	/* Reporter */
	reporter: [
		['list'],
		['html', { outputFolder: 'build/playwright-report', open: 'never' }],
	],

	/*
	 * One PHP server serves the entire Prado tree on port 8037.
	 * Both test suites resolve under it via their own URL prefixes:
	 *   http://127.0.0.1:8037/tests/harness/…                 (generic tests)
	 *   http://127.0.0.1:8037/vendor/pradosoft/prado-demos/…  (demos tests)
	 *
	 * `status.html` in tests/harness/ returns 200 instantly, giving Playwright
	 * a reliable startup signal.
	 *
	 * reuseExistingServer: on CI a fresh server is always started; locally an
	 * already-running :8037 server is reused.  If a stale server is left over
	 * from a killed run, stop it first: pkill -f "php.*8037"
	 */
	webServer: {
		command: 'bash tests/playwright/start-server.sh',
		url: 'http://127.0.0.1:8037/tests/harness/status.html',
		reuseExistingServer: !process.env.CI,
		timeout: 30_000,
	},

	/* Global settings applied to all tests */
	use: {
		baseURL: 'http://127.0.0.1:8037',

		/* Headless by default; set HEADLESS=false to watch */
		headless: process.env.HEADLESS !== 'false',

		/* Viewport */
		viewport: { width: 1280, height: 720 },

		/* Screenshots on failure */
		screenshot: 'only-on-failure',

		/* Videos on failure */
		video: 'retain-on-failure',

		/* Assertion timeout (per-assertion) */
		actionTimeout: 13_333,
	},

	projects: [
		{
			name: 'chromium',
			use: { ...devices['Desktop Chrome'] },
		},
		{
			name: 'firefox',
			use: { ...devices['Desktop Firefox'] },
		},
		{
			name: 'webkit',
			use: { ...devices['Desktop Safari'] },
		},
	],

	/* Output directory for test artefacts */
	outputDir: 'build/playwright-results',
});
