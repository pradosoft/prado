import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=LuhnValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado LuhnValidator Tests');
	return h;
}

/**
 * The client validator checks the Luhn checksum of digits only.
 */
test('LuhnValidatorTestCase: client validation', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['490154203237519', '4901 5420 3237 518', '5']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	await input.fill('490154203237518');
	await input.dispatchEvent('change');
	await h.assertNotVisible(`${base}validator1`);
	await expect(input).toHaveAttribute('aria-invalid', 'false');
});

/**
 * AllowSeparators ignores spaces and hyphens.
 */
test('LuhnValidatorTestCase: separators', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text2`);
	await input.fill('4111 1111 1111 1112');
	await page.locator(`#${base}submit1`).click();
	await h.assertVisible(`${base}validator2`);

	for (const value of ['4111 1111 1111 1111', '4111-1111-1111-1111']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator2`);
	}
});

/**
 * A validator without client script validates on the postback.
 */
test('LuhnValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text3`).fill('79927398710');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator3`);

	await page.locator(`#${base}text3`).fill('79927398713');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator3`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('LuhnValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('IMEI')).toHaveAttribute('inputmode', 'numeric');

	await input.fill('12');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'a digit may be mistyped' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
