import { test } from '@playwright/test';
import { genericHelper } from '../helpers.js';
import { pngFile } from './png.js';

function makeFile(name, mimeType, size = 10) {
	return { name, mimeType, buffer: Buffer.alloc(size, 'a') };
}

test.describe('FileValidatorTestCase', () => {
	test('testMaxFileSize', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');
		await h.assertSourceContains('Prado FileValidator Tests');

		// the Capture property renders the capture attribute
		await h.assertAttribute(`${base}upload1@capture`, 'environment');

		await h.assertNotVisible(`${base}validator1`);
		await page.setInputFiles(`#${base}upload1`, makeFile('big.txt', 'text/plain', 200));
		await h.assertNotVisible(`${base}validator1`);
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator1`);
		await page.setInputFiles(`#${base}upload1`, makeFile('small.txt', 'text/plain', 50));
		await h.assertNotVisible(`${base}validator1`);
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertNotVisible(`${base}validator1`);
	});

	test('testAllowedFileExtensions', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		await h.assertNotVisible(`${base}validator2`);
		await page.setInputFiles(`#${base}upload2`, makeFile('anim.gif', 'image/gif'));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator2`);
		// the {files} token is replaced with the invalid file name
		await h.assertText(`${base}validator2`, 'Wrong type: anim.gif');
		await page.setInputFiles(`#${base}upload2`, makeFile('photo.jpg', 'image/jpeg'));
		await h.assertNotVisible(`${base}validator2`);
	});

	test('testMaxFileCount', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		await h.assertNotVisible(`${base}validator3`);
		await page.setInputFiles(`#${base}upload3`, [
			makeFile('a.txt', 'text/plain'),
			makeFile('b.txt', 'text/plain'),
			makeFile('c.txt', 'text/plain'),
		]);
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator3`);
		await page.setInputFiles(`#${base}upload3`, [
			makeFile('a.txt', 'text/plain'),
			makeFile('b.txt', 'text/plain'),
		]);
		await h.assertNotVisible(`${base}validator3`);
	});

	test('testServerSideValidation', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		// validator4 has EnableClientScript=false: the invalid file posts back
		// and the server-side validation shows the message after the reload.
		await h.assertNotVisible(`${base}validator4`);
		await page.setInputFiles(`#${base}upload4`, makeFile('report.pdf', 'application/pdf'));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator4`);

		await page.setInputFiles(`#${base}upload4`, makeFile('report.txt', 'text/plain'));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertNotVisible(`${base}validator4`);
	});

	test('testTotalMaxFileSize', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		await h.assertNotVisible(`${base}validator6`);
		await page.setInputFiles(`#${base}upload6`, [
			makeFile('a.txt', 'text/plain', 100),
			makeFile('b.txt', 'text/plain', 100),
		]);
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator6`);
		await page.setInputFiles(`#${base}upload6`, [
			makeFile('a.txt', 'text/plain', 60),
			makeFile('b.txt', 'text/plain', 60),
		]);
		await h.assertNotVisible(`${base}validator6`);
	});

	test('testCheckExtensionMimeType', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		// validator7 is server side only: a text file renamed to .png passes the
		// extension restriction but fails the sniffed content cross-check.
		await h.assertNotVisible(`${base}validator7`);
		await page.setInputFiles(`#${base}upload7`, makeFile('fake.png', 'image/png', 50));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator7`);

		await page.setInputFiles(`#${base}upload7`, pngFile('real.png', 4, 4));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertNotVisible(`${base}validator7`);
	});

	test('testAcceptDerivedRestrictions', async ({ page }) => {
		const h = genericHelper(page);
		const base = 'ctl0_Content_';
		await h.url('validators/index.php?page=FileValidator');

		// the Accept property renders the accept attribute
		await h.assertAttribute(`${base}upload5@accept`, '.txt, image/png');

		await h.assertNotVisible(`${base}validator5`);
		await page.setInputFiles(`#${base}upload5`, makeFile('setup.exe', 'application/x-msdownload'));
		await h.byXPath("//input[@type='submit' and @value='Test']").click();
		await h.assertVisible(`${base}validator5`);
		// a file matching the extension token is valid
		await page.setInputFiles(`#${base}upload5`, makeFile('notes.txt', 'text/plain'));
		await h.assertNotVisible(`${base}validator5`);
		// a file matching the MIME token is valid
		await page.setInputFiles(`#${base}upload5`, makeFile('logo.png', 'image/png'));
		await h.assertNotVisible(`${base}validator5`);
	});
});
