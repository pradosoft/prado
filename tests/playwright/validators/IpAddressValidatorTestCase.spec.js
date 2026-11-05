import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=IpAddressValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado IpAddressValidator Tests');
	return h;
}

/**
 * The client validator accepts IPv4 and IPv6 addresses without a prefix.
 */
test('IpAddressValidatorTestCase: client validation with the defaults', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['999.0.0.1', '01.2.3.4', '1::2::3', '10.0.0.0/8', 'example.com']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['192.0.2.1', '2001:db8::1', '::ffff:192.0.2.1']) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * Version, RequireCidr, and the resolved Ranges reach the client validator.
 */
test('IpAddressValidatorTestCase: client validation with options', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text2`);

	for (const value of ['8.8.8.0', '2606:4700::/32', '10.0.0.0/8', '0.0.0.0/0']) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator2`);
	}

	await input.fill('8.8.8.0/24');
	await input.dispatchEvent('change');
	await h.assertNotVisible(`${base}validator2`);
});

/**
 * A validator without client script validates on the postback.
 */
test('IpAddressValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${base}text3`).fill('8.8.8.8');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator3`);

	await page.locator(`#${base}text3`).fill('192.168.1.10');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator3`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('IpAddressValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('Server address')).toHaveCount(1);

	await input.fill('not-an-ip');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Enter an IPv4 or IPv6 address' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
