import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * TKeyboard exposes the on-screen keyboard as a labeled group whose keys are
 * focusable buttons; pressing Enter on a key types it into the associated text
 * box, so the widget is operable by keyboard (WCAG 2.1.1).
 */
test('KeyboardA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=KeyboardA11yTest');
	await h.assertSourceContains('Keyboard Accessibility Test Case');

	const group = page.locator('#ctl0_Content_kb [role="group"]');
	await expect(group).toHaveAttribute('aria-label', 'On-screen keyboard');

	// Keys are exposed as focusable buttons with decoded labels.
	const keys = group.locator('[role="button"]');
	expect(await keys.count()).toBeGreaterThan(0);
	await expect(keys.first()).toHaveAttribute('tabindex', '0');

	// The "1" key types into the field when activated by keyboard.
	const oneKey = group.locator('.Key1', { hasText: /^1$/ }).first();
	await oneKey.focus();
	await expect(oneKey).toHaveAttribute('role', 'button');
	await page.keyboard.press('Enter');

	const field = page.locator('#ctl0_Content_field');
	await expect(field).toHaveValue('1');
});
