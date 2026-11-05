import { test } from '@playwright/test';
import { genericHelper } from '../helpers.js';
import { pngBuffer } from '../validators/png.js';

function textFile(name, content = 'plain text content') {
	return { name, mimeType: 'text/plain', buffer: Buffer.from(content) };
}

test('ActiveFileUploadValidatorTestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('active-controls/index.php?page=TActiveFileUploadValidatorTest');
	await h.assertSourceContains('TActiveFileUpload Validator Functional Test');

	await h.assertText(`${base}label1`, 'No upload');
	await h.assertNotVisible(`${base}validator1`);

	// an invalid extension is blocked client side before the upload starts
	await page.setInputFiles(`#${base}uploader`, textFile('fake.png'));
	await h.assertVisible(`${base}validator1`);
	await h.assertText(`${base}validator1`, 'Wrong type: fake.png');
	await h.assertText(`${base}label1`, 'No upload');

	// a valid selection uploads and the server-side validation passes
	await page.setInputFiles(`#${base}uploader`, textFile('ok.txt'));
	await h.assertNotVisible(`${base}validator1`);
	await h.assertText(`${base}label1`, 'ok.txt valid');

	// PNG content renamed to .txt passes the client gate but fails the
	// server-side CheckExtensionMimeType validation during the callback
	await page.setInputFiles(`#${base}uploader`, {
		name: 'trick.txt',
		mimeType: 'text/plain',
		buffer: pngBuffer(4, 4),
	});
	await h.assertText(`${base}label1`, 'trick.txt invalid');
});
