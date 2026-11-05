import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * TAccordion implements the disclosure pattern: each header is a role=button
 * with aria-expanded controlling a labeled role=region, operable with
 * Enter/Space and navigable with the arrow keys.
 */
test('AccordionA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('web/index.php?page=AccordionA11yTest');
	await h.assertSourceContains('Accordion Accessibility Test Case');

	const h1 = page.locator('#ctl0_Content_v1_0');
	const h2 = page.locator('#ctl0_Content_v2_0');
	const p1 = page.locator('#ctl0_Content_v1');
	const p2 = page.locator('#ctl0_Content_v2');

	// Headers are disclosure buttons controlling labeled regions
	await expect(h1).toHaveAttribute('role', 'button');
	await expect(h1).toHaveAttribute('aria-controls', 'ctl0_Content_v1');
	await expect(p1).toHaveAttribute('role', 'region');
	await expect(p1).toHaveAttribute('aria-labelledby', 'ctl0_Content_v1_0');

	// First region open initially
	await expect(h1).toHaveAttribute('aria-expanded', 'true');
	await expect(h2).toHaveAttribute('aria-expanded', 'false');
	await expect(p1).toBeVisible();
	await expect(p2).toBeHidden();

	// Space on a focused header opens its region
	await h2.focus();
	await expect(h2).toBeFocused();
	await page.keyboard.press('Space');
	await expect(h2).toHaveAttribute('aria-expanded', 'true');
	await expect(h1).toHaveAttribute('aria-expanded', 'false');
	await expect(p2).toBeVisible();

	// ArrowDown moves focus to the next header without toggling it
	await h2.focus();
	await page.keyboard.press('ArrowDown');
	await expect(page.locator('#ctl0_Content_v3_0')).toBeFocused();
	await expect(h2).toHaveAttribute('aria-expanded', 'true'); // unchanged

	// Pointer click still toggles
	await h1.click();
	await expect(h1).toHaveAttribute('aria-expanded', 'true');
	await expect(p1).toBeVisible();
});
