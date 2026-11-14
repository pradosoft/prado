import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'validators/index.php?page=UnicodeValidator';
const base = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Prado UnicodeValidator Tests');
	return h;
}

async function submit(page, text1, text2 = '') {
	await page.locator(`#${base}text1`).fill(text1);
	await page.locator(`#${base}text2`).fill(text2);
	await page.locator(`#${base}submit1`).click();
}

/**
 * Waits for the page's validator scripts to register the validator with its
 * form's Prado.Validation manager, after which the client owns aria-invalid.
 */
async function waitForClientValidator(page, id) {
	await page.waitForFunction(
		(validatorId) => Object.values(globalThis.Prado?.Validation?.managers ?? {})
			.some((manager) => manager.validators.some((v) => v.options.ID === validatorId)),
		id
	);
}

/**
 * The client lets every value through and the server rejects suspicious ones.
 */
test('UnicodeValidatorTestCase: server validation', async ({ page }) => {
	const h = await open(page);

	for (const value of ['pаypal', 'abc​def', 'file‮gpj.exe', '١2']) {
		await submit(page, value);
		await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
		await h.assertVisible(`${base}validator1`);
		await expect(page.locator(`#${base}text1`)).toHaveClass(/required/);
	}

	for (const value of ['José Müller', 'Ελληνικά', '日本語テキスト', 'paypal']) {
		await submit(page, value);
		await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
		await h.assertNotVisible(`${base}validator1`);
	}
});

/**
 * RestrictionLevel="Ascii" rejects any non-ASCII character.
 */
test('UnicodeValidatorTestCase: ASCII restriction', async ({ page }) => {
	const h = await open(page);
	await submit(page, '', 'café');
	await expect(page.locator(`#${base}status`)).toHaveText('Page invalid');
	await h.assertVisible(`${base}validator2`);

	await submit(page, '', 'cafe');
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	await h.assertNotVisible(`${base}validator2`);
});

/**
 * The server-rendered message is an alert linked to the field, and the field
 * reports its validity.
 */
test('UnicodeValidatorTestCase: accessibility', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await expect(page.locator(`#${base}validator1`)).toHaveAttribute('role', 'alert');
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator1`));

	expect(await input.getAttribute('aria-invalid')).toBeNull();
	await submit(page, 'pаypal');
	await expect(page.getByRole('alert').filter({ hasText: 'mistaken for others' })).toBeVisible();
	await expect(page.getByLabel('User name')).toHaveValue('pаypal');
	// The server marks the field invalid; the failed validator owns the attribute on the client.
	await expect(input).toHaveAttribute('aria-invalid', 'true');
	await waitForClientValidator(page, `${base}validator1`);
	expect(await input.evaluate((el) => el.lastAriaValidator)).toBe(`${base}validator1`);

	// A valid postback renders the field without the attribute.
	await submit(page, 'paypal');
	await expect(page.locator(`#${base}status`)).toHaveText('Page valid');
	expect(await input.getAttribute('aria-invalid')).toBeNull();
});

/**
 * A field that fails a server-only validator clears aria-invalid once the
 * validator that failed passes on the client.
 */
test('UnicodeValidatorTestCase: client clears a server aria-invalid', async ({ page }) => {
	await open(page);
	const input = page.locator(`#${base}text1`);
	await submit(page, 'abc​def');
	await expect(input).toHaveAttribute('aria-invalid', 'true');
	await waitForClientValidator(page, `${base}validator1`);
	await input.fill('abcdef');
	await input.dispatchEvent('change');
	await input.evaluate((el) => Prado.Validation.validate(el.form.id));
	await expect(input).toHaveAttribute('aria-invalid', 'false');
});
