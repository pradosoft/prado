import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpHeaderTest';

test('THttpResponseHeaderTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── Default Content-Type and charset are text/html; charset=UTF-8 ──
	let response = await page.goto(BASE + '&action=default');
	const contentType = response.headers()['content-type'] ?? '';
	expect(contentType.toLowerCase()).toContain('text/html');
	expect(contentType.toLowerCase()).toContain('utf-8');

	// ── Custom X-headers set via appendHeader() appear in the response ──
	expect(response.headers()['x-prado-test']).toBe('functional-test');
	expect(response.headers()['x-prado-version']).toBe('4.3.2');

	// ── Page body is still rendered correctly ──
	await h.assertSourceContains('THttpResponse Header Test');
	await h.assertSourceContains('headers sent');

	// ── Charset override is reflected in Content-Type header ──
	response = await page.goto(BASE + '&action=charset');
	const ctCharset = response.headers()['content-type'] ?? '';
	expect(ctCharset.toLowerCase()).toContain('iso-8859-1');
	expect(response.headers()['x-prado-charset-test']).toBe('iso');

	// ── Multiple custom headers are all present ──
	response = await page.goto(BASE + '&action=multi');
	expect(response.headers()['x-prado-first']).toBe('alpha');
	expect(response.headers()['x-prado-second']).toBe('beta');
	expect(response.headers()['x-prado-third']).toBe('gamma');
});
