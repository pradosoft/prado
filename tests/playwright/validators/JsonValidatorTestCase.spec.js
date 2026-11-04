import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=JsonValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado JsonValidator Tests');
	return h;
}

/**
 * The client validator rejects JSON syntax errors, unpaired surrogate escapes,
 * and nesting past the PHP depth.
 */
test('JsonValidatorTestCase: client validation', async ({ page }) => {
	const h = await open(page);
	const input = page.locator(`#${base}text1`);
	await h.assertNotVisible(`${base}validator1`);

	for (const value of ['{"a":1,}', "{'a':1}", '"\\ud800"', `${'['.repeat(512)}${']'.repeat(512)}`]) {
		await input.fill(value);
		await page.locator(`#${base}submit1`).click();
		await h.assertVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveClass(/required/);
	}

	for (const value of ['{"theme": "dark",\n "sizes": [1, 2, 3]}', '42', `${'['.repeat(511)}${']'.repeat(511)}`]) {
		await input.fill(value);
		await input.dispatchEvent('change');
		await h.assertNotVisible(`${base}validator1`);
		await expect(input).toHaveAttribute('aria-invalid', 'false');
	}
});

/**
 * A validator without client script validates on the postback, and agrees
 * with the browser on the same values.
 */
test('JsonValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);
	for (const value of ['[1,]', '"\\ud800"']) {
		await page.locator(`#${base}text2`).fill(value);
		await page.locator(`#${base}submit1`).click();
		await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
		await h.assertVisible(`${base}validator2`);
	}

	await page.locator(`#${base}text2`).fill('{"a": [true, null]}');
	await page.locator(`#${base}submit1`).click();
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator2`);
});

/**
 * The message is an alert linked to the field, and the field reports its validity.
 */
test('JsonValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));
	await expect(page.getByLabel('Settings (JSON)')).toHaveCount(1);

	await input.fill('{');
	await page.locator(`#${base}submit1`).click();
	await expect(page.getByRole('alert').filter({ hasText: 'Enter valid JSON' })).toBeVisible();
	await expect(input).toHaveAttribute('aria-invalid', 'true');
});
