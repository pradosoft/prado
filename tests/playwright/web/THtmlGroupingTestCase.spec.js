import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=HtmlGroupingTest';

/**
 * Each fieldset is a group named by its first legend: Legend text, or a TLegend child.
 */
test('THtmlGroupingTestCase: fieldsets are groups named by their legends', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('HTML Grouping Test Case');

	const shipping = page.getByRole('group', { name: 'Shipping address' });
	await expect(shipping).toBeVisible();
	await expect(shipping.getByRole('textbox', { name: 'Street' })).toBeVisible();

	const speed = page.getByRole('group', { name: 'Delivery speed' });
	await expect(speed.getByRole('radio')).toHaveCount(2);

	await expect(page.getByRole('group', { name: 'Contact *' })).toBeVisible();
	expect(await page.locator('fieldset > legend').count()).toBe(3);
});

/**
 * A disabled fieldset disables its controls, and a postback keeps their state.
 */
test('THtmlGroupingTestCase: disabled fieldset keeps its controls on postback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.getByRole('button', { name: 'Disable shipping' }).click();
	await expect(page.locator('#ctl0_Content_status')).toHaveText('disabled');

	const shipping = page.getByRole('group', { name: 'Shipping address' });
	await expect(shipping.getByRole('textbox', { name: 'Street' })).toBeDisabled();
	const gift = shipping.getByRole('checkbox', { name: 'Gift wrap' });
	await expect(gift).toBeDisabled();
	await expect(gift).toBeChecked();

	await page.getByRole('button', { name: 'Submit' }).click();
	await expect(page.locator('#ctl0_Content_status')).toHaveText('gift: yes, street: 1 Harbor Road');
	await expect(page.getByRole('checkbox', { name: 'Gift wrap' })).toBeChecked();
});

/**
 * TSeparator renders a separator.
 */
test('THtmlGroupingTestCase: separator', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.getByRole('separator')).toHaveCount(1);
	expect(await page.locator('#ctl0_Content_break').evaluate((el) => el.tagName)).toBe('HR');
});

/**
 * The canonical TLinkTag renders in the head.
 */
test('THtmlGroupingTestCase: canonical link tag in the head', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.locator('head link[rel="canonical"]')).toHaveAttribute('href', 'https://example.com/grouping');
});

/**
 * TNoScript serves its fallback in `<noscript>`, which a scripting browser does not show.
 * Playwright's javaScriptEnabled: false keeps the parser's scripting flag, so the
 * fallback is checked in the served markup.
 */
test('THtmlGroupingTestCase: noscript fallback', async ({ page }) => {
	const response = await page.goto(GENERIC_BASE_URL + PAGE_URL);
	expect(await response.text()).toMatch(/<noscript id="ctl0_Content_fallback">\s*<p>Scripting is off\.<\/p>\s*<\/noscript>/);

	await expect(page.getByText('Scripting is off.')).toHaveCount(0);
});
