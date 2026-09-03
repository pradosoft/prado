import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ListGroupingA11yTest';

/**
 * TCheckBoxList and TRadioButtonList expose their items as a named group so
 * assistive technology announces them as one set rather than a bare table.
 */
test('ListGroupingA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('List Grouping Accessibility Test Case');

	// TCheckBoxList -> role=group, named from ToolTip
	const cbl = page.locator('#ctl0_Content_cbl');
	await expect(cbl).toHaveAttribute('role', 'group');
	await expect(cbl).toHaveAttribute('aria-label', 'Pick your toppings');

	// TRadioButtonList -> role=radiogroup, named from ToolTip
	const rbl = page.locator('#ctl0_Content_rbl');
	await expect(rbl).toHaveAttribute('role', 'radiogroup');
	await expect(rbl).toHaveAttribute('aria-label', 'Choose a size');

	// Without a ToolTip the role is still applied (no aria-label)
	const cblNoTip = page.locator('#ctl0_Content_cblNoTip');
	await expect(cblNoTip).toHaveAttribute('role', 'group');
	expect(await cblNoTip.getAttribute('aria-label')).toBeNull();

	// The items remain real, keyboard-operable inputs inside the group
	await expect(page.locator('#ctl0_Content_rbl input[type=radio]')).toHaveCount(3);
	await expect(page.locator('#ctl0_Content_cbl input[type=checkbox]')).toHaveCount(3);
});
