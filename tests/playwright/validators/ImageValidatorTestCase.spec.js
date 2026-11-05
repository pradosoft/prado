import { test } from '@playwright/test';
import { genericHelper } from '../helpers.js';
import { pngFile } from './png.js';

test.describe('ImageValidatorTestCase', () => {
	test('testMaxImageDimensions', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=ImageValidator');
		await h.assertSourceContains('Prado ImageValidator Tests');

		// An oversized image fails: client side once the asynchronous decode
		// completes, or server side when the submit outruns the decode.
		await h.assertNotVisible(`${base}validator1`);
		await page.setInputFiles(`#${base}upload1`, pngFile('wide.png', 200, 50));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator1`);

		await page.setInputFiles(`#${base}upload1`, pngFile('small.png', 50, 50));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertNotVisible(`${base}validator1`);
	});

	test('testNotAnImage', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=ImageValidator');

		await h.assertNotVisible(`${base}validator1`);
		await page.setInputFiles(`#${base}upload1`, {
			name: 'fake.png',
			mimeType: 'image/png',
			buffer: Buffer.from('plain text pretending to be an image'),
		});
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator1`);

		await page.setInputFiles(`#${base}upload1`, pngFile('real.png', 20, 20));
		await h.assertNotVisible(`${base}validator1`);
	});

	test('testServerSideMinImageDimensions', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=ImageValidator');

		// validator2 has EnableClientScript=false: the undersized image posts
		// back and the server-side validation shows the message after reload.
		await h.assertNotVisible(`${base}validator2`);
		await page.setInputFiles(`#${base}upload2`, pngFile('tiny.png', 5, 5));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator2`);

		await page.setInputFiles(`#${base}upload2`, pngFile('ok.png', 20, 20));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertNotVisible(`${base}validator2`);
	});
});
