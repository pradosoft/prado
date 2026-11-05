import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * TColorPicker exposes its trigger as a button that opens a labeled dialog, and
 * its basic palette is a keyboard-navigable grid: the button opens with Enter,
 * arrow keys move between colour cells, Enter selects, and Escape closes and
 * returns focus to the trigger.
 */
test('ColorPickerA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=ColorPickerA11yTest');
	await h.assertSourceContains('ColorPicker Accessibility Test Case');

	const button = page.locator('#ctl0_Content_basic_button');
	const input = page.locator('#ctl0_Content_basic');

	// The trigger is an accessible button that controls a dialog.
	await expect(button).toHaveAttribute('role', 'button');
	await expect(button).toHaveAttribute('aria-haspopup', 'dialog');
	await expect(button).toHaveAttribute('aria-expanded', 'false');
	await expect(button).toHaveAttribute('aria-label', 'Pick a color');
	await expect(button).toHaveAttribute('tabindex', '0');

	// Open with the keyboard: focus the button and press Enter.
	await button.focus();
	await page.keyboard.press('Enter');
	await expect(button).toHaveAttribute('aria-expanded', 'true');

	const dialog = page.locator('#ctl0_Content_basic_picker');
	await expect(dialog).toHaveAttribute('role', 'dialog');
	await expect(dialog).toHaveAttribute('aria-label', 'Pick a color');

	// Focus landed on the first palette cell (single tab stop).
	const cells = dialog.locator('img[role="button"]');
	await expect(cells.first()).toBeFocused();
	await expect(cells.first()).toHaveAttribute('aria-label', /^#/);

	// Arrow to the next cell and select it with Enter; the input updates.
	await page.keyboard.press('ArrowRight');
	await page.keyboard.press('Enter');
	const value = await input.inputValue();
	expect(value).toMatch(/^#[0-9A-Fa-f]{3,6}$/);

	// Escape closes the dialog and returns focus to the trigger.
	await page.keyboard.press('Escape');
	await expect(button).toHaveAttribute('aria-expanded', 'false');
	await expect(button).toBeFocused();
});
