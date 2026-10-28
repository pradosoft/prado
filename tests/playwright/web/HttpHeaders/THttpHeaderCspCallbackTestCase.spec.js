import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

/**
 * Functional tests for scripts that a callback response runs or loads under the
 * policy `script-src 'self' NONCE`.
 *
 * A callback response is a new request with a new nonce, while the page keeps the
 * policy of its first load. Prado's client code gives the scripts it creates the
 * page nonce (Prado.Element.getPageNonce):
 *   - Prado.Element.appendScriptBlock — the callback's inline script block
 *   - Prado.ScriptManager             — the callback's script file, cross-origin so
 *                                       that `'self'` does not cover it
 *   - Prado.Element.replace           — a `<script>` in the callback markup, which
 *                                       carries the callback's own nonce
 */
const CSP_CALLBACK_PAGE = GENERIC_BASE_URL + 'HttpHeaders/index.php?page=CspCallbackPage';

test.describe('THttpHeaderCspCallbackTestCase', () => {

	/**
	 * 1. The page policy holds the nonce and blocks an inline script without one.
	 */
	test('page policy is nonce based and enforced', async ({ page }) => {
		const response = await page.goto(CSP_CALLBACK_PAGE, { waitUntil: 'load' });
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toMatch(/script-src 'self' 'nonce-[^']+'/);

		const html = page.locator('html');
		await expect(html).not.toHaveAttribute('data-no-nonce-ran', '1');
		const violations = await page.evaluate(() => window.__cspViolations.length);
		expect(violations).toBeGreaterThan(0);
	});

	/**
	 * 2. The callback's inline script block, script file, and markup script all run,
	 *    and the callback raises no CSP violation.
	 */
	test('callback scripts run with the page nonce', async ({ page }) => {
		await page.goto(CSP_CALLBACK_PAGE, { waitUntil: 'load' });
		const before = await page.evaluate(() => window.__cspViolations.length);

		const callback = page.waitForResponse((r) => r.request().method() === 'POST');
		await page.locator('#RunCallback').click();
		const callbackResponse = await callback;

		// The callback response carries its own nonce, different from the page nonce.
		const pageNonce = await page.evaluate(() => document.getElementById('violation-listener').nonce);
		const callbackCsp = callbackResponse.headers()['content-security-policy'];
		expect(callbackCsp).toMatch(/'nonce-[^']+'/);
		expect(callbackCsp).not.toContain(`'nonce-${pageNonce}'`);

		const html = page.locator('html');
		await expect(html).toHaveAttribute('data-callback-block-ran', '1');
		await expect(html).toHaveAttribute('data-callback-file-ran', '1');
		await expect(html).toHaveAttribute('data-callback-markup-ran', '1');

		const violations = await page.evaluate((n) => window.__cspViolations.slice(n), before);
		expect(violations).toEqual([]);
	});
});
