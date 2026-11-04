import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=UrlValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado UrlValidator Tests');
	return h;
}

/**
 * The client validator checks the scheme, the host, and the top-level label,
 * converting an internationalized domain name with the browser URL parser.
 */
test('UrlValidatorTestCase: client validation with the defaults', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['example.com', 'ftp://example.com', 'http://localhost', 'http://exa mple.com', 'http://999.1.1.1']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['https://example.com/a?b#c', 'http://bücher.example/', 'http://例え.テスト', 'http://[2001:db8::1]:8080/']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * ValidSchemes, AllowIdn, and RequireTld reach the client validator.
 */
test('UrlValidatorTestCase: client validation with options', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text2`);

	await input.fill('http://bücher.example');
	await page.locator(`#${base}submit1`).click();
	await h.assertVisible(`${base}validator2`);

	for (const value of ['ftp://intranet/file', 'http://localhost:8080/app', 'http://xn--bcher-kva.example']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator2`);
	}

	await input.fill('gopher://intranet');
	await input.dispatchEvent('change');
	await h.assertVisible(`${base}validator2`);
});

/**
 * A validator without client script validates on the postback.
 */
test('UrlValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text3`).fill('javascript:alert(1)');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator3`);
	await expect(page.locator(`#${base}validator3`)).toHaveText('Server: invalid URL');

	await page.locator(`#${base}text3`).fill('https://bücher.example/straße');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator3`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('UrlValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	const message = page.locator(`#${base}validator1`);
	await expect(message).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('Website')).toHaveCount(1);

	await input.fill('example.com');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Enter an http or https URL' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
