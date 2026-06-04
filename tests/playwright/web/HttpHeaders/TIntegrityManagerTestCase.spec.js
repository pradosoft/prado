import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

/**
 * Functional tests for Subresource Integrity emitted by TIntegrityManager.
 *
 * SriPage pins an SRI value for a cross-origin asset (served via localhost while
 * the page is served from 127.0.0.1) and renders the <script> tag through the
 * framework's own TJavaScript::renderScriptFile() path.
 *
 * What these tests establish:
 *   - A matching integrity lets the script execute.
 *   - A tampered integrity makes the browser block the script.
 *   - An SRI failure is NOT a CSP violation: no securitypolicyviolation event
 *     fires, so it never reaches a CSP report-uri / report-to collector. SRI and
 *     CSP are separate mechanisms with separate reporting.
 */
const SRI_PAGE = GENERIC_BASE_URL + 'HttpHeaders/index.php?page=SriPage';

test.describe('TIntegrityManagerTestCase', () => {

	/**
	 * 1. A matching integrity allows the script to run; no CSP violation fires.
	 */
	test('matching integrity allows the script to execute', async ({ page }) => {
		await page.goto(SRI_PAGE + '&action=correct', { waitUntil: 'load' });

		const ran = await page.evaluate(() => document.documentElement.dataset.sriRan);
		expect(ran).toBe('1');

		const violations = await page.evaluate(() => window.__cspViolations);
		expect(violations).toEqual([]);
	});

	/**
	 * 2. A tampered integrity blocks the script. The failure is reported by the
	 *    browser as an SRI error (console), and crucially does NOT raise a CSP
	 *    securitypolicyviolation event.
	 */
	test('tampered integrity blocks the script and is not a CSP violation', async ({ page }) => {
		const consoleErrors = [];
		page.on('console', (msg) => {
			if (msg.type() === 'error') {
				consoleErrors.push(msg.text());
			}
		});

		await page.goto(SRI_PAGE + '&action=wrong', { waitUntil: 'load' });

		// Blocked: the asset never executed, so its marker is absent.
		const ran = await page.evaluate(() => document.documentElement.dataset.sriRan);
		expect(ran).toBeUndefined();

		// SRI failure is not a CSP violation — no securitypolicyviolation event.
		const violations = await page.evaluate(() => window.__cspViolations);
		expect(violations).toEqual([]);

		// The browser logs an SRI error mentioning integrity.
		expect(consoleErrors.join('\n').toLowerCase()).toContain('integrity');
	});

	/**
	 * 3. The emitted tag carries the integrity and crossorigin attributes that a
	 *    strict CSP requires for third-party scripts.
	 */
	test('the emitted tag carries integrity and crossorigin attributes', async ({ page }) => {
		await page.goto(SRI_PAGE + '&action=correct', { waitUntil: 'load' });

		const script = page.locator('script[src*="sri-asset.php"]');
		await expect(script).toHaveAttribute('integrity', /^sha384-/);
		await expect(script).toHaveAttribute('crossorigin', 'anonymous');
	});
});
