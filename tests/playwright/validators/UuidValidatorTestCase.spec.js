import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=UuidValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado UuidValidator Tests');
	return h;
}

/**
 * The client validator accepts the hyphenated form of any version with the
 * RFC 9562 variant.
 */
test('UuidValidatorTestCase: client validation with the defaults', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['not-a-uuid', '550e8400e29b41d4a716446655440000', '00000000-0000-0000-0000-000000000000', '550e8400-e29b-41d4-c716-446655440000']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['550e8400-e29b-41d4-a716-446655440000', 'F81D4FAE-7DEC-11D0-A765-00A0C91E6BF6']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * Versions and Strict reach the client validator.
 */
test('UuidValidatorTestCase: client validation with options', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text2`);

	await input.fill('f81d4fae-7dec-11d0-a765-00a0c91e6bf6');
	await page.locator(`#${base}submit1`).click();
	await h.assertVisible(`${base}validator2`);

	for (const value of ['{550e8400-e29b-41d4-a716-446655440000}', 'urn:uuid:017f22e2-79b0-7cc3-98c4-dc0c0c07398f', '017f22e279b07cc398c4dc0c0c07398f']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator2`);
	}
});

/**
 * A validator without client script validates on the postback.
 */
test('UuidValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text3`).fill('550e8400-e29b-91d4-a716-446655440000');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator3`);

	await page.locator(`#${base}text3`).fill('550e8400-e29b-41d4-a716-446655440000');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator3`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('UuidValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('Order ID')).toHaveCount(1);

	await input.fill('not-a-uuid');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Enter a UUID such as' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
