import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpRequestTest';

/** Read and JSON-parse the #request-info div (textContent auto-decodes HTML entities). */
async function readInfo(page) {
	const text = await page.locator('#request-info').textContent();
	return JSON.parse(text);
}

test('THttpRequestTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── GET: core request properties ──
	await page.goto(BASE + '&foo=hello');
	await h.assertSourceContains('THttpRequest Test');

	const info = await readInfo(page);

	expect(info.method).toBe('GET');

	// GET parameter access via itemAt()
	expect(info.foo).toBe('hello');

	// Query string and URI contain the navigated path
	expect(info.queryString).toContain('foo=hello');
	expect(info.requestUri).toContain('/web/index.php');

	// Server info matches the test server
	expect(info.serverName).toBe('127.0.0.1');
	expect(info.serverPort).toBe(8037);

	// Client info: real browser IP and User-Agent
	expect(info.userHostAddress).toBe('127.0.0.1');
	expect(typeof info.userAgent).toBe('string');
	expect(info.userAgent.length).toBeGreaterThan(0);

	// HTTP, not HTTPS
	expect(info.isSecure).toBe(false);
	expect(info.protocol).toMatch(/HTTP\/1\.[01]/);

	// URL helpers
	expect(info.baseUrl).toMatch(/^http:\/\/127\.0\.0\.1:8037/);
	expect(info.appUrl).toContain('/web/index.php');

	// getUserLanguages() works in a real HTTP context (static-variable caching
	// is reset per request; the unit test skips this due to the CLI environment).
	expect(Array.isArray(info.languages)).toBe(true);
	expect(info.languages.length).toBeGreaterThan(0);
	expect(typeof info.languages[0]).toBe('string');

	// getHeaders() exposes real request headers via $_SERVER['HTTP_*'] parsing
	expect(typeof info.headers).toBe('object');
	expect(info.headers['host']).toContain('127.0.0.1');
	expect(typeof info.headers['user-agent']).toBe('string');
	expect(info.headers['user-agent'].length).toBeGreaterThan(0);

	// ── POST: itemAt() merges GET + POST; method reports POST ──
	// Inject and submit a form programmatically so the browser makes a real POST
	// navigation — this avoids undici strict-parser issues and nested-form problems.
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.evaluate((url) => {
			const f = document.createElement('form');
			f.method = 'POST';
			f.action = url;
			const i = document.createElement('input');
			i.name = 'postkey';
			i.value = 'testpostvalue';
			f.appendChild(i);
			document.body.appendChild(f);
			f.submit();
		}, BASE),
	]);

	const postInfo = await readInfo(page);
	expect(postInfo.method).toBe('POST');
	expect(postInfo.postkey).toBe('testpostvalue');
	// itemAt() returns null for absent GET param
	expect(postInfo.foo).toBeNull();
});
