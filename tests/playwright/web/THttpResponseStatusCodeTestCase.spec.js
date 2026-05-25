import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpStatusCodeTest';

test('THttpResponseStatusCodeTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── Default: 200 OK ──
	let response = await page.goto(BASE);
	expect(response.status()).toBe(200);
	await h.assertSourceContains('THttpResponse Status Code Test');
	await h.assertSourceContains('status:200');

	// ── 404 Not Found ──
	response = await page.goto(BASE + '&status=404');
	expect(response.status()).toBe(404);
	// Page still renders (PRADO does not throw on custom status codes)
	await h.assertSourceContains('status:404');

	// ── 403 Forbidden ──
	response = await page.goto(BASE + '&status=403');
	expect(response.status()).toBe(403);
	await h.assertSourceContains('status:403');

	// ── 503 Service Unavailable ──
	response = await page.goto(BASE + '&status=503');
	expect(response.status()).toBe(503);
	await h.assertSourceContains('status:503');

	// ── 201 Created ──
	response = await page.goto(BASE + '&status=201');
	expect(response.status()).toBe(201);
	await h.assertSourceContains('status:201');

	// ── Custom reason phrase is reflected in status text ──
	response = await page.goto(BASE + '&status=418&reason=Im+a+teapot');
	expect(response.status()).toBe(418);
	expect(response.statusText()).toBe("Im a teapot");
});
