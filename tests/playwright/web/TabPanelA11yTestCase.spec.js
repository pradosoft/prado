import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * TTabPanel implements the WAI-ARIA tabs pattern: a tablist of role=tab
 * elements controlling role=tabpanel views, with aria-selected, roving
 * tabindex, and arrow-key navigation. Uses the tickets/Issue216 harness page.
 */
test('TabPanelA11yTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url('tickets/index.php?page=Issue216');
	await h.assertSourceContains("TTabPanel");

	const tablist = page.locator('[role="tablist"]');
	await expect(tablist).toHaveCount(1);
	const tabs = page.locator('[role="tab"]');
	await expect(tabs).toHaveCount(2);

	const tab1 = page.locator('#ctl0_Content_tab1_0');
	const tab2 = page.locator('#ctl0_Content_tab2_0');

	// JavaScript-switching tabs render the caption as plain text on the tab
	// element (no anchor); the fake javascript:// link was removed.
	await expect(tab1.locator('a')).toHaveCount(0);
	await expect(tab1).toHaveText('Tab 1');
	const panel1 = page.locator('#ctl0_Content_tab1');
	const panel2 = page.locator('#ctl0_Content_tab2');

	// Each tab controls its labelled panel
	await expect(tab1).toHaveAttribute('aria-controls', 'ctl0_Content_tab1');
	await expect(panel1).toHaveAttribute('role', 'tabpanel');
	await expect(panel1).toHaveAttribute('aria-labelledby', 'ctl0_Content_tab1_0');

	// Initial selection + roving tabindex
	await expect(tab1).toHaveAttribute('aria-selected', 'true');
	await expect(tab1).toHaveAttribute('tabindex', '0');
	await expect(tab2).toHaveAttribute('aria-selected', 'false');
	await expect(tab2).toHaveAttribute('tabindex', '-1');
	await expect(panel1).toBeVisible();
	await expect(panel2).toBeHidden();

	// Keyboard: focus the selected tab, ArrowRight moves selection to tab 2
	await tab1.focus();
	await expect(tab1).toBeFocused();
	await page.keyboard.press('ArrowRight');
	await expect(tab2).toBeFocused();
	await expect(tab2).toHaveAttribute('aria-selected', 'true');
	await expect(tab2).toHaveAttribute('tabindex', '0');
	await expect(tab1).toHaveAttribute('aria-selected', 'false');
	await expect(panel2).toBeVisible();
	await expect(panel1).toBeHidden();

	// ArrowRight again wraps back to tab 1
	await page.keyboard.press('ArrowRight');
	await expect(tab1).toBeFocused();
	await expect(tab1).toHaveAttribute('aria-selected', 'true');

	// A pointer click still switches tabs and updates the state
	await tab2.click();
	await expect(tab2).toHaveAttribute('aria-selected', 'true');
	await expect(panel2).toBeVisible();
});
