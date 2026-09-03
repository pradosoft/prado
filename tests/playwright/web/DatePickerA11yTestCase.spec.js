import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * Accessibility of the TDatePicker calendar in a real browser: the trigger is a
 * named popup button, the date table is a labeled grid whose selected cell is
 * exposed, keyboard navigation moves the selection while focus stays on the
 * input, and Escape closes the popup.
 *
 * Uses the Ticket670 harness page: a Button-mode, TextBox-input date picker.
 */
test('DatePickerA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('tickets/index.php?page=Ticket670');

	// The trigger button carries a name and popup semantics
	const trigger = page.locator('input[aria-haspopup="dialog"]').first();
	await expect(trigger).toHaveAttribute('aria-label', 'Choose date');
	await expect(trigger).toHaveAttribute('aria-expanded', 'false');

	// Open the calendar
	await trigger.click();
	await expect(trigger).toHaveAttribute('aria-expanded', 'true');
	await expect(trigger).toHaveAttribute('aria-controls', /.+_calendar$/);

	// The popup is a dialog holding a grid labeled "MonthName Year"
	const dialog = page.locator('div[role="dialog"][aria-label="Calendar"]');
	await expect(dialog).toBeVisible();
	const grid = dialog.locator('table[role="grid"]');
	await expect(grid).toHaveAttribute('aria-label', /^[^ ]+ \d{4}$/);

	// Exactly one selected gridcell; the input points at it while it has focus
	const selected = grid.locator('td[aria-selected="true"]');
	await expect(selected).toHaveCount(1);
	const cellId = await selected.getAttribute('id');
	const input = page.locator('[aria-activedescendant]');
	await expect(input).toHaveAttribute('aria-activedescendant', cellId);

	// Arrow keys move the selection without moving focus off the input
	const before = await selected.textContent();
	await page.keyboard.press('ArrowRight');
	const after = await grid.locator('td[aria-selected="true"]').textContent();
	expect(after).not.toBe(before);

	// Padding cells are hidden from assistive technology
	const empties = grid.locator('td.empty');
	if (await empties.count() > 0) {
		await expect(empties.first()).toHaveAttribute('aria-hidden', 'true');
	}

	// Escape closes the popup and restores the collapsed state
	await page.keyboard.press('Escape');
	await expect(dialog).toBeHidden();
	await expect(trigger).toHaveAttribute('aria-expanded', 'false');
	expect(await page.locator('[aria-activedescendant]').count()).toBe(0);
});
