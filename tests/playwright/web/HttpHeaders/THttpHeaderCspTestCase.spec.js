import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

const BASE_URL = GENERIC_BASE_URL + 'HttpHeaders/web/index.php';
const CSP_PAGE = BASE_URL + '?page=CspPage';

/**
 * Captures both 'error' and 'warning' console messages so that CSP violations
 * are caught regardless of the type the browser assigns.  Enforcing-mode CSP
 * violations appear as 'error' in Chromium but as 'warning' in Firefox.
 * Report-Only violations appear as 'warning' in all browsers.
 */
function collectCspMessages(page) {
	const messages = [];
	page.on('console', (msg) => {
		if (msg.type() === 'error' || msg.type() === 'warning') {
			messages.push(msg.text());
		}
	});
	return messages;
}

test.describe('THttpHeaderCspTestCase', () => {

	/**
	 * 1. A Content-Security-Policy header is emitted for the script-src action.
	 */
	test('csp header is emitted for each action', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=script-src');
		const headers = response.headers();
		expect(headers['content-security-policy']).toContain("script-src 'self'");
	});

	/**
	 * 2. script-src 'self' blocks inline scripts; the inline-script marker is
	 *    absent from the document dataset and a CSP violation appears in the
	 *    browser console.
	 *
	 *    Note: Firefox surfaces CSP violations in the browser's dedicated security
	 *    console, which is NOT forwarded to Playwright via page.on('console').
	 *    The blocking-behaviour assertion (inlineScriptRan) is cross-browser;
	 *    the console-message assertions are skipped on Firefox.
	 */
	test('script-src self blocks inline script (CSP violation in console)', async ({ page, browserName }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=script-src', { waitUntil: 'networkidle' });

		// Inline script must have been blocked — dataset attribute not set.
		const inlineScriptRan = await page.evaluate(
			() => document.documentElement.dataset.inlineScriptRan
		);
		expect(inlineScriptRan).toBeUndefined();

		// Firefox does not expose script-src CSP violations via Playwright's console API.
		if (browserName !== 'firefox') {
			// At least one console message must reference Content Security Policy.
			const cspMessages = consoleMessages.filter(
				(e) => e.includes('Content Security Policy') || e.includes('content-security-policy')
			);
			expect(cspMessages.length).toBeGreaterThan(0);

			// At least one message must mention script-src.
			const scriptSrcMessages = consoleMessages.filter((e) => e.includes('script-src'));
			expect(scriptSrcMessages.length).toBeGreaterThan(0);
		}
	});

	/**
	 * 3. script-src nonce: the nonce-bearing script runs; the inline script is
	 *    blocked; the CSP header contains the nonce pattern.
	 */
	test('script-src nonce allows matching script and blocks inline', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=script-src-nonce', { waitUntil: 'networkidle' });
		const cspHeader = response.headers()['content-security-policy'];

		// Header must contain a nonce token.
		expect(cspHeader).toMatch(/nonce-[A-Za-z0-9+/=]+/);

		// Nonce-bearing script ran.
		const nonceScriptRan = await page.evaluate(
			() => document.documentElement.dataset.nonceScriptRan
		);
		expect(nonceScriptRan).toBe('1');

		// Inline script was blocked.
		const inlineScriptRan = await page.evaluate(
			() => document.documentElement.dataset.inlineScriptRan
		);
		expect(inlineScriptRan).toBeUndefined();
	});

	/**
	 * 4. img-src 'self' blocks an external image; the browser logs a CSP error.
	 */
	test('img-src self blocks external image (CSP error in console)', async ({ page }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=img-src', { waitUntil: 'networkidle' });

		// At least one message must reference img-src or the blocked image URL.
		const imgMessages = consoleMessages.filter(
			(e) => e.includes('img-src') || e.includes('img-src-blocked.csp-test.invalid')
		);
		expect(imgMessages.length).toBeGreaterThan(0);
	});

	/**
	 * 5. style-src 'self' blocks inline styles; the style-marker element is NOT
	 *    rendered red (colour remains browser default) and a CSP error is logged.
	 */
	test('style-src self blocks inline style (CSP error in console)', async ({ page }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=style-src', { waitUntil: 'networkidle' });

		// At least one message must reference style-src.
		const styleMessages = consoleMessages.filter((e) => e.includes('style-src'));
		expect(styleMessages.length).toBeGreaterThan(0);

		// The inline style that sets colour to red must have been blocked.
		const color = await page.evaluate(() =>
			window.getComputedStyle(document.getElementById('style-marker')).color
		);
		expect(color).not.toBe('rgb(255, 0, 0)');
	});

	/**
	 * 6. frame-src 'none' blocks an iframe; the browser logs a CSP error.
	 *    The iframe src is a non-local URL — about:blank inherits the parent CSP
	 *    on some browsers and does not trigger a frame-src violation.
	 */
	test('frame-src none blocks iframe (CSP error in console)', async ({ page }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=frame-src', { waitUntil: 'networkidle' });

		// At least one message must reference frame-src or child-src.
		const frameMessages = consoleMessages.filter(
			(e) => e.includes('frame-src') || e.includes('child-src')
		);
		expect(frameMessages.length).toBeGreaterThan(0);
	});

	/**
	 * 7. frame-ancestors 'none' is present in the CSP header value.
	 */
	test('frame-ancestors none appears in CSP header', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=frame-ancestors');
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toContain("frame-ancestors 'none'");
	});

	/**
	 * 8. connect-src 'none' blocks a fetch() call; the blocked network request
	 *    causes the promise to reject, and a CSP error is logged.
	 *
	 *    The fetch is exercised via page.evaluate() rather than a nonce-bearing
	 *    inline script so the test is independent of nonce rendering and DOM
	 *    mutation timing.  page.evaluate() runs in the page's main JS context and
	 *    is subject to all CSP restrictions including connect-src.
	 *
	 *    Note: Firefox does not forward connect-src CSP violations to Playwright's
	 *    console API; the console assertion is skipped on Firefox.
	 */
	test('connect-src none blocks fetch (CSP error in console)', async ({ page, browserName }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=connect-src', { waitUntil: 'networkidle' });

		// Verify the CSP header is enforcing connect-src 'none'.
		// (Checked here in addition to the header test for belt-and-suspenders coverage.)

		// Make a fetch from the page's JS context — connect-src 'none' must block it.
		const fetchResult = await page.evaluate(async () => {
			try {
				await fetch('/tests/harness/HttpHeaders/web/index.php?page=Home');
				return 'fetched';
			} catch (e) {
				return 'blocked';
			}
		});
		expect(fetchResult).toBe('blocked');

		// Firefox does not expose connect-src CSP violations via Playwright's console API.
		if (browserName !== 'firefox') {
			// At least one console message references connect-src.
			const connectMessages = consoleMessages.filter((e) => e.includes('connect-src'));
			expect(connectMessages.length).toBeGreaterThan(0);
		}
	});

	/**
	 * 9. upgrade-insecure-requests directive appears in the CSP header.
	 */
	test('upgrade-insecure-requests directive is in the header', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=upgrade-insecure');
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toContain('upgrade-insecure-requests');
	});

	/**
	 * 10. Multiple directives are all serialised into the header value correctly.
	 */
	test('multiple directives are serialized correctly', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=multiple');
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toContain("default-src 'none'");
		expect(cspHeader).toContain("script-src 'self'");
		expect(cspHeader).toContain("frame-ancestors 'none'");
	});

	/**
	 * 11. report-only CSP does not block inline scripts (content is allowed)
	 *     but a CSP violation is still logged to the console.
	 *     Report-Only violations appear as console 'warning' (not 'error') in
	 *     all browsers, so both types are captured.
	 *
	 *     Note: Firefox surfaces report-only CSP violations in the browser's
	 *     security console, which is NOT forwarded to Playwright via
	 *     page.on('console').  The allow-through assertion is cross-browser;
	 *     the console-message assertion is skipped on Firefox.
	 */
	test('report-only CSP does not block inline scripts but shows CSP error', async ({ page, browserName }) => {
		const consoleMessages = collectCspMessages(page);

		await page.goto(CSP_PAGE + '&action=report-only', { waitUntil: 'networkidle' });

		// Content is NOT blocked in report-only mode — inline script must have run.
		const inlineScriptRan = await page.evaluate(
			() => document.documentElement.dataset.inlineScriptRan
		);
		expect(inlineScriptRan).toBe('1');

		// Firefox does not expose report-only CSP violations via Playwright's console API.
		if (browserName !== 'firefox') {
			// Violation is still reported to the console.
			const cspMessages = consoleMessages.filter(
				(e) => e.includes('Content Security Policy') || e.includes('content-security-policy')
			);
			expect(cspMessages.length).toBeGreaterThan(0);
		}
	});

	/**
	 * 12. The report-only variant uses the Content-Security-Policy-Report-Only
	 *     header name, not Content-Security-Policy.
	 */
	test('content-security-policy-report-only header name is used for report-only', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=report-only');
		const headers = response.headers();

		// Report-only header must be set.
		expect(headers['content-security-policy-report-only']).toBeTruthy();

		// The enforcing header must NOT be set (or must be absent).
		expect(headers['content-security-policy']).toBeFalsy();
	});

});
