import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=IbanValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado IbanValidator Tests');
	return h;
}

/**
 * The client validator checks the country format and the checksum, with the
 * country formats the server sends.
 */
test('IbanValidatorTestCase: client validation', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['DE89 3704 0044 0532 0130 01', 'DE89 3704 0044 0532 0130 0', 'XX89 3704 0044 0532 0130 00', 'NL91 0417 ABNA 1643 00']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['DE89 3704 0044 0532 0130 00', 'gb82 west 1234 5698 7654 32', 'MT84 MALT 0110 0001 2345 MTLC AST0 01S', 'NO93 8601 1117 947']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * Countries limits the client validator to the listed countries.
 */
test('IbanValidatorTestCase: countries', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text2`);
	await input.fill('GB82 WEST 1234 5698 7654 32');
	await page.locator(`#${base}submit1`).click();
	await h.assertVisible(`${base}validator2`);

	for (const value of ['AT61 1904 3002 3457 3201', 'CH93 0076 2011 6238 5295 7']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator2`);
	}
});

/**
 * A validator without client script validates on the postback.
 */
test('IbanValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text3`).fill('FR14 2004 1010 0505 0001 3M02 607');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator3`);

	await page.locator(`#${base}text3`).fill('FR14 2004 1010 0505 0001 3M02 606');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator3`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('IbanValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('IBAN', { exact: true })).toHaveCount(1);

	await input.fill('DE00');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Check the IBAN' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
