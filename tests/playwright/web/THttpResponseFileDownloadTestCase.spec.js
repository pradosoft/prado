import { test, expect } from '@playwright/test';
import { promises as fs } from 'node:fs';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'web/index.php?page=HttpFileDownloadTest';

test('THttpResponseFileDownloadTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── No action: normal page renders, no download headers ──
	// Do this first so we have a page context for subsequent link clicks.
	const pageResponse = await page.goto(BASE);
	expect(pageResponse.status()).toBe(200);

	const pageDisposition = pageResponse.headers()['content-disposition'] ?? '';
	expect(pageDisposition).not.toContain('attachment');

	await h.assertSourceContains('THttpResponse File Download Test');

	// ── download-text: Content-Disposition: attachment ──
	// Clicking a link avoids page.goto() which throws "Download is starting"
	// when the server responds with Content-Disposition: attachment.
	const [download, attachResponse] = await Promise.all([
		page.waitForEvent('download'),
		page.waitForResponse(r => r.url().includes('download-text')),
		page.locator('#dl-attachment').click(),
	]);

	expect(attachResponse.status()).toBe(200);

	const disposition = attachResponse.headers()['content-disposition'] ?? '';
	expect(disposition).toContain('attachment');
	expect(disposition).toContain('prado-test.txt');

	const contentType = attachResponse.headers()['content-type'] ?? '';
	expect(contentType).toContain('text/plain');

	// Verify the filename Playwright derived from the Content-Disposition header
	expect(download.suggestedFilename()).toBe('prado-test.txt');

	// Read the saved file and verify its content
	const downloadPath = await download.path();
	expect(downloadPath).not.toBeNull();
	const fileContent = await fs.readFile(downloadPath, 'utf-8');
	expect(fileContent).toContain('Hello from PRADO writeFile!');
	expect(fileContent).toContain('This is line 2');

	// ── download-inline: Content-Disposition: inline ──
	// Navigate back to the base page, then click the inline link.
	// No download event fires — the browser renders the content inline.
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
