import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

/**
 * A validator is accessible: its message carries role="alert" so it is
 * announced when shown, it links the message to the validated control with
 * aria-describedby, and it toggles aria-invalid on the control as validity
 * changes.
 */
test('RequiredFieldValidatorA11yTestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('validators/index.php?page=RequiredFieldValidator');
	await h.assertSourceContains('RequiredFieldValidator Tests');

	const input = page.locator(`#${base}text3`);
	const message = page.locator(`#${base}validator5`);

	// The message element is an alert region.
	await expect(message).toHaveAttribute('role', 'alert');

	// On registration the validator links its message to the control.
	await expect(input).toHaveAttribute('aria-describedby', new RegExp(`${base}validator5`));

	// Not yet validated: no aria-invalid on the control.
	expect(await input.getAttribute('aria-invalid')).toBeNull();

	// Submitting the no-group button validates text3, which is empty and fails.
	await page.locator(`#${base}submit3`).click();
	await expect(input).toHaveAttribute('aria-invalid', 'true');

	// Supplying a value and revalidating clears aria-invalid.
	await input.fill('something');
	await page.locator(`#${base}submit3`).click();
	await expect(input).toHaveAttribute('aria-invalid', 'false');
});
