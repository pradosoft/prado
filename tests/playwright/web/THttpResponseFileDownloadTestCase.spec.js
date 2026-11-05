import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpFileDownloadTest';

test('THttpResponseFileDownloadTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── No action: normal page renders, no download headers ──
	const pageResponse = await page.goto(BASE);
	expect(pageResponse.status()).toBe(200);

	const pageDisposition = pageResponse.headers()['content-disposition'] ?? '';
	expect(pageDisposition).not.toContain('attachment');

	await h.assertSourceContains('THttpResponse File Download Test');

	// ── download-text: Content-Disposition: attachment ──
	// page.waitForResponse intercepts at the browser protocol level, so the browser's
	// lenient HTTP stack handles PRADO's Content-Transfer-Encoding: binary header without
	// issue. No download event or file-read needed — headers are what PRADO owns here.
	const [attachResponse] = await Promise.all([
		page.waitForResponse(r => r.url().includes('download-text')),
		page.locator('#dl-attachment').click(),
	]);

	expect(attachResponse.status()).toBe(200);

	const disposition = attachResponse.headers()['content-disposition'] ?? '';
	expect(disposition).toContain('attachment');
	expect(disposition).toContain('prado-test.txt');

	const contentType = attachResponse.headers()['content-type'] ?? '';
	expect(contentType).toContain('text/plain');

	// ── download-inline: Content-Disposition: inline ──
	await page.goto(BASE);
	const [inlineResponse] = await Promise.all([
		page.waitForResponse(r => r.url().includes('download-inline')),
		page.locator('#dl-inline').click(),
	]);

	expect(inlineResponse.status()).toBe(200);

	const inlineDisposition = inlineResponse.headers()['content-disposition'] ?? '';
	expect(inlineDisposition).toContain('inline');
	expect(inlineDisposition).toContain('prado-test.txt');
});
