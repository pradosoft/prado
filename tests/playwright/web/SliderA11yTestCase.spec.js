import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * TSlider exposes its handle as a role=slider with the value semantics and is
 * operable by keyboard: arrow keys step by StepSize, Home/End jump to the ends,
 * and aria-valuenow tracks the value.
 */
test('SliderA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=SliderA11yTest');
	await h.assertSourceContains('Slider Accessibility Test Case');

	const handle = page.locator('#ctl0_Content_vol_handle');
	await expect(handle).toHaveAttribute('role', 'slider');
	await expect(handle).toHaveAttribute('aria-valuemin', '0');
	await expect(handle).toHaveAttribute('aria-valuemax', '100');
	await expect(handle).toHaveAttribute('aria-valuenow', '40');
	await expect(handle).toHaveAttribute('aria-orientation', 'horizontal');
	await expect(handle).toHaveAttribute('aria-label', 'Volume');
	await expect(handle).toHaveAttribute('tabindex', '0');

	// Keyboard: focus the handle and step it up by the StepSize (5)
	await handle.focus();
	await expect(handle).toBeFocused();
	await page.keyboard.press('ArrowRight');
	await expect(handle).toHaveAttribute('aria-valuenow', '45');
	await page.keyboard.press('ArrowLeft');
	await expect(handle).toHaveAttribute('aria-valuenow', '40');

	// Home / End jump to the range ends
	await page.keyboard.press('Home');
	await expect(handle).toHaveAttribute('aria-valuenow', '0');
	await page.keyboard.press('End');
	await expect(handle).toHaveAttribute('aria-valuenow', '100');

	// The hidden field the server reads mirrors the keyboard value
	const hidden = await page.locator('#ctl0_Content_vol_1').inputValue();
	expect(hidden).toBe('100');
});
