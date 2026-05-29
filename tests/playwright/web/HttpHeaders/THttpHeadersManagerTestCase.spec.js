import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

const BASE_URL = GENERIC_BASE_URL + 'HttpHeaders/web/index.php';
const HEADERS_PAGE = BASE_URL + '?page=HeadersPage';
const CSP_PAGE = BASE_URL + '?page=CspPage';

test.describe('THttpHeadersManagerTestCase', () => {

	/**
	 * 1. The default Content-Type header from THttpResponse contains text/html
	 *    and the UTF-8 charset.
	 */
	test('content-type header is emitted by default', async ({ page }) => {
		const response = await page.goto(BASE_URL + '?page=Home');
		const contentType = response.headers()['content-type'];
		expect(contentType).toBeTruthy();
		expect(contentType.toLowerCase()).toContain('text/html');
		expect(contentType.toLowerCase()).toContain('utf-8');
	});

	/**
	 * 2. The HSTS action emits a Strict-Transport-Security header with the
	 *    correct max-age, includeSubDomains, and preload tokens.
	 */
	test('HSTS header is emitted with correct value', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=hsts');
		const hsts = response.headers()['strict-transport-security'];
		expect(hsts).toBeTruthy();
		expect(hsts).toContain('max-age=31536000');
		expect(hsts).toContain('includeSubDomains');
		expect(hsts).toContain('preload');
	});

	/**
	 * 3. The x-frame-options action emits X-Frame-Options: DENY.
	 */
	test('X-Frame-Options DENY is emitted', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=x-frame-options');
		const xfo = response.headers()['x-frame-options'];
		expect(xfo).toBe('DENY');
	});

	/**
	 * 4. The coep-coop action emits both Cross-Origin-Embedder-Policy and
	 *    Cross-Origin-Opener-Policy with the correct values.
	 */
	test('COEP and COOP headers are both emitted', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=coep-coop');
		const headers = response.headers();

		const coep = headers['cross-origin-embedder-policy'];
		expect(coep).toBeTruthy();
		expect(coep).toContain('require-corp');

		const coop = headers['cross-origin-opener-policy'];
		expect(coop).toBeTruthy();
		expect(coop).toContain('same-origin');
	});

	/**
	 * 5. The x-content-type-options action emits X-Content-Type-Options: nosniff.
	 */
	test('X-Content-Type-Options nosniff is emitted', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=x-content-type-options');
		const xcto = response.headers()['x-content-type-options'];
		expect(xcto).toBe('nosniff');
	});

	/**
	 * 6. The multi-security action emits HSTS, X-Frame-Options, and
	 *    X-Content-Type-Options all in the same response.
	 */
	test('multiple security headers are emitted together', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=multi-security');
		const headers = response.headers();

		expect(headers['strict-transport-security']).toBeTruthy();
		expect(headers['x-frame-options']).toBeTruthy();
		expect(headers['x-content-type-options']).toBeTruthy();
	});

	/**
	 * 7. Manager-emitted headers appear alongside the default Content-Type header
	 *    produced by THttpResponse — both are present in the same response.
	 */
	test('manager headers appear alongside THttpResponse Content-Type', async ({ page }) => {
		const response = await page.goto(HEADERS_PAGE + '&action=x-frame-options');
		const headers = response.headers();

		// Content-Type is always emitted by THttpResponse.
		expect(headers['content-type']).toBeTruthy();

		// X-Frame-Options was added via the manager.
		expect(headers['x-frame-options']).toBeTruthy();
	});

	/**
	 * 8. A CSP header added through THttpHeadersManager flows through
	 *    THttpResponse and appears in the response — confirming the manager
	 *    integration plumbing works end to end.
	 */
	test('CSP header from manager appears in response', async ({ page }) => {
		const response = await page.goto(CSP_PAGE + '&action=script-src');
		const headers = response.headers();
		expect(headers['content-security-policy']).toBeTruthy();
	});

});
