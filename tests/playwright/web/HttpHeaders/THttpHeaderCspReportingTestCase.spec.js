import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

const BASE_URL        = GENERIC_BASE_URL + 'HttpHeaders/index.php';
const CSP_REPORT_PAGE = BASE_URL + '?page=CspReportPage';

/**
 * Base URL of the PHP CSP report collector.
 * Each test appends `?t=<token>` to isolate its storage.
 *
 * The collector is a plain PHP script served directly by the built-in server
 * (outside Prado routing).  It receives real HTTP POST requests from the
 * browser — bypassing Playwright's page.route() interception, which only
 * captures page-level requests and misses browser-process CSP deliveries on
 * Firefox (and is also unreliable for the Reporting API on Chromium).
 *
 * POST → stores the raw body; responds 204.
 * GET  → returns stored bodies as JSON array of strings; clears the store.
 */
const COLLECTOR_PHP = GENERIC_BASE_URL + 'HttpHeaders/csp-collector.php';

/**
 * Placeholder endpoint used only in header-value assertions (tests 1, 5, 6, 7).
 * No real HTTP requests are made to this address.
 */
const EXAMPLE_COLLECTOR = 'https://example.invalid/csp';

/**
 * Polls the PHP collector until at least one CSP report body arrives or the
 * deadline passes.
 *
 * Uses Playwright's `request` fixture (Node.js HTTP, not browser network) so
 * the call is not subject to the page's CSP and works regardless of browser.
 *
 * @param {import('@playwright/test').APIRequestContext} request
 * @param {string} token       Per-test unique isolation token.
 * @param {number} timeoutMs   Maximum wait in milliseconds.
 * @returns {Promise<string|null>}  First report body string, or null on timeout.
 */
async function pollCspCollector(request, token, timeoutMs = 12000) {
	const url      = `${COLLECTOR_PHP}?t=${token}`;
	const deadline = Date.now() + timeoutMs;

	while (Date.now() < deadline) {
		const resp   = await request.get(url);
		const bodies = await resp.json();
		if (bodies.length > 0) {
			return bodies[0];
		}
		await new Promise((resolve) => setTimeout(resolve, 500));
	}
	return null;
}

test.describe('THttpHeaderCspReportingTestCase', () => {

	/**
	 * 1. report-uri directive appears in the Content-Security-Policy header.
	 */
	test('report-uri appears in CSP header', async ({ page }) => {
		const response = await page.goto(
			CSP_REPORT_PAGE + `&action=report-uri&endpoint=${encodeURIComponent(EXAMPLE_COLLECTOR)}`
		);
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toContain(`report-uri ${EXAMPLE_COLLECTOR}`);
	});

	/**
	 * 2. When a CSP violation occurs under report-uri mode, the browser sends
	 *    a POST request containing a JSON csp-report body to the collector URL.
	 *    Only the inline script is on the page; the violated-directive will be
	 *    script-src (no other violating resources are present).
	 *
	 *    The PHP collector receives the POST directly from the browser's network
	 *    stack, which is reliable across all browsers (Chromium, Firefox, WebKit).
	 *    Firefox batches reports more aggressively; the timeout is extended for it.
	 */
	test('report-uri violation sends a POST to the collector', async ({ page, request, browserName }) => {
		const token        = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
		const collectorUrl = `${COLLECTOR_PHP}?t=${token}`;
		const timeoutMs    = browserName === 'firefox' ? 15000 : 8000;

		await page.goto(
			CSP_REPORT_PAGE + `&action=report-uri&endpoint=${encodeURIComponent(collectorUrl)}`,
			{ waitUntil: 'networkidle' }
		);

		const capturedBody = await pollCspCollector(request, token, timeoutMs);

		expect(capturedBody).not.toBeNull();

		const report = JSON.parse(capturedBody);
		expect(report['csp-report']).toBeTruthy();

		// The only violation on this page is the inline script → script-src.
		const violatedDirective = report['csp-report']['violated-directive'] ?? '';
		expect(violatedDirective).toContain('script-src');
	});

	/**
	 * 3. The CSP violation report body contains a non-empty blocked-uri field
	 *    (value is 'inline' for inline-script violations).
	 */
	test('report-uri violation report contains blocked-uri', async ({ page, request, browserName }) => {
		const token        = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
		const collectorUrl = `${COLLECTOR_PHP}?t=${token}`;
		const timeoutMs    = browserName === 'firefox' ? 15000 : 8000;

		await page.goto(
			CSP_REPORT_PAGE + `&action=report-uri&endpoint=${encodeURIComponent(collectorUrl)}`,
			{ waitUntil: 'networkidle' }
		);

		const capturedBody = await pollCspCollector(request, token, timeoutMs);

		expect(capturedBody).not.toBeNull();

		const report    = JSON.parse(capturedBody);
		const blockedUri = report['csp-report']['blocked-uri'] ?? null;
		// blocked-uri is present and non-null (may be 'inline' or the actual URL).
		expect(blockedUri).not.toBeNull();
		expect(blockedUri).toBeTruthy();
	});

	/**
	 * 4. report-only + report-uri sends violation reports without blocking
	 *    content: the inline script runs and the report is dispatched.
	 */
	test('report-only report-uri sends violation report without blocking content', async ({ page, request, browserName }) => {
		const token        = `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
		const collectorUrl = `${COLLECTOR_PHP}?t=${token}`;
		const timeoutMs    = browserName === 'firefox' ? 15000 : 8000;

		await page.goto(
			CSP_REPORT_PAGE + `&action=report-only-report-uri&endpoint=${encodeURIComponent(collectorUrl)}`,
			{ waitUntil: 'networkidle' }
		);

		// Content is not blocked in report-only mode — inline script ran.
		const inlineScriptRan = await page.evaluate(
			() => document.documentElement.dataset.inlineScriptRan
		);
		expect(inlineScriptRan).toBe('1');

		// Violation report was sent to the real PHP collector.
		const capturedBody = await pollCspCollector(request, token, timeoutMs);
		expect(capturedBody).not.toBeNull();
	});

	/**
	 * 5. The report-to directive appears in the Content-Security-Policy header.
	 */
	test('report-to directive appears in CSP header', async ({ page }) => {
		const response = await page.goto(CSP_REPORT_PAGE + '&action=report-to');
		const cspHeader = response.headers()['content-security-policy'];
		expect(cspHeader).toContain('report-to csp-endpoint');
	});

	/**
	 * 6. The Reporting-Endpoints header is emitted when the report-to action is
	 *    active and contains both the endpoint name and the supplied URL.
	 */
	test('Reporting-Endpoints header is emitted with report-to action', async ({ page }) => {
		const response = await page.goto(
			CSP_REPORT_PAGE + `&action=report-to&endpoint=${encodeURIComponent(EXAMPLE_COLLECTOR)}`
		);
		const reportingEndpoints = response.headers()['reporting-endpoints'];
		expect(reportingEndpoints).toBeTruthy();
		expect(reportingEndpoints).toContain('csp-endpoint');
		expect(reportingEndpoints).toContain(EXAMPLE_COLLECTOR);
	});

	/**
	 * 7. The Reporting API (report-to) is correctly wired: the CSP header
	 *    contains the report-to directive and the Reporting-Endpoints header
	 *    names the collector URL.
	 *
	 *    Actual report delivery is NOT tested here.  The Reporting API sends
	 *    reports via the browser's internal network service with a variable
	 *    batching delay (up to ~60 s in Chromium) and is not reliably
	 *    interceptable within a practical test timeout via any Playwright API.
	 *    The header assertions in tests 5 and 6 provide full coverage of the
	 *    server-side configuration; this test re-confirms the pair together.
	 */
	test('report-to CSP header and Reporting-Endpoints header are correctly paired', async ({ page }) => {
		const response = await page.goto(
			CSP_REPORT_PAGE + `&action=report-to&endpoint=${encodeURIComponent(EXAMPLE_COLLECTOR)}`
		);
		const headers = response.headers();

		// CSP header must carry the report-to directive.
		expect(headers['content-security-policy']).toContain('report-to csp-endpoint');

		// Reporting-Endpoints header must name the endpoint and point to the collector.
		const reportingEndpoints = headers['reporting-endpoints'];
		expect(reportingEndpoints).toBeTruthy();
		expect(reportingEndpoints).toContain('csp-endpoint');
		expect(reportingEndpoints).toContain(EXAMPLE_COLLECTOR);
	});

});
