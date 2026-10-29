import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=HtmlAttributesTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the Chromium accessibility node of the element matching the selector.
 */
async function axNode(page, selector) {
	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
	return (await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false })).nodes[0];
}

/**
 * The AutoFocus text box has focus after the page loads.
 */
test('THtmlAttributesTestCase: autofocus focuses its text box', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('HTML Attributes Test Case');

	await expect(page.getByRole('textbox', { name: 'Search' })).toBeFocused();
});

/**
 * Indeterminate shows the mixed state until a click; Switch exposes the switch role.
 */
test('THtmlAttributesTestCase: mixed check box and switch', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const selectAll = page.getByRole('checkbox', { name: 'Select all' });
	await expect(selectAll).toBeChecked({ indeterminate: true });
	await selectAll.click();
	await expect(selectAll).toBeChecked();
	expect(await selectAll.evaluate((el) => el.indeterminate)).toBe(false);

	const notify = page.getByRole('switch', { name: 'Notifications' });
	await expect(notify).not.toBeChecked();
	await notify.click();
	await expect(notify).toBeChecked();
});

/**
 * Each ID in a cell's headers names a header cell of the table.
 */
test('THtmlAttributesTestCase: table cell headers reference header cells', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.assertAttribute(`${BASE}northQ1@headers`, `${BASE}north ${BASE}q1`);
	const tags = await page.locator(`#${BASE}northQ1`).evaluate((cell) =>
		cell.getAttribute('headers').split(' ').map((id) => document.getElementById(id)?.tagName));
	expect(tags).toEqual(['TH', 'TH']);
});

/**
 * The frame is named by its title, sandboxed, and shows its srcdoc document.
 */
test('THtmlAttributesTestCase: frame, image, and link', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const frame = page.locator(`#${BASE}map`);
	await expect(frame).toHaveAttribute('title', 'Office map');
	await expect(frame).toHaveAttribute('sandbox', '');
	await expect(page.frameLocator(`#${BASE}map`).locator('p')).toHaveText('Map & directions');

	const photo = page.getByRole('img', { name: 'The harbor at dawn' });
	await expect(photo).toHaveAttribute('loading', 'lazy');
	await expect(photo).toHaveAttribute('width', '40');

	await expect(page.getByRole('link', { name: 'Download report' })).toHaveAttribute('download', 'report.html');
	await expect(page.getByLabel('Folder')).toHaveAttribute('webkitdirectory', 'webkitdirectory');
});

/**
 * Chromium's accessibility tree: the mixed state, the switch role, and the frame name.
 */
test('THtmlAttributesTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const selectAll = await axNode(page, `#${BASE}selectAll`);
	expect(selectAll.role.value).toBe('checkbox');
	expect(selectAll.properties.find((p) => p.name === 'checked').value.value).toBe('mixed');
	const notify = await axNode(page, `#${BASE}notify`);
	expect(notify.role.value).toBe('switch');
	expect(notify.name.value).toBe('Notifications');
	const frame = await axNode(page, `#${BASE}map`);
	expect(frame.name.value).toBe('Office map');
});

/**
 * A manual popover stays hidden until shown; hidden="until-found" content opens
 * on fragment navigation.
 */
test('THtmlAttributesTestCase: popover and hidden until found', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const tip = page.locator(`#${BASE}tip`);
	await expect(tip).toHaveAttribute('popover', 'manual');
	await expect(tip).toBeHidden();
	await tip.evaluate((el) => el.showPopover());
	await expect(tip).toBeVisible();
	await page.keyboard.press('Escape');
	await expect(tip).toBeVisible();

	const more = page.locator(`#${BASE}more`);
	await expect(more).toBeHidden();
	const supported = await page.evaluate(() => 'onbeforematch' in document.body);
	test.skip(!supported, 'the browser has no hidden="until-found"');
	await page.evaluate((id) => { location.hash = id; }, `${BASE}more`);
	await expect(more).toBeVisible();
	await expect(more).not.toHaveAttribute('hidden', /.*/);
});

/**
 * A button whose CausesValidation is false renders formnovalidate, so it submits
 * past an empty required field.
 */
test('THtmlAttributesTestCase: formnovalidate skips the required field', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.getByRole('textbox', { name: 'Full name' })).toHaveAttribute('required', 'required');
	await h.assertAttribute(`${BASE}cancel@formnovalidate`, 'formnovalidate');
	await page.getByRole('button', { name: 'Cancel' }).click();
	await expect(page.locator(`#${BASE}status`)).toHaveText('cancelled');
});

/**
 * The script postback of a validating button applies its FormAction.
 */
test('THtmlAttributesTestCase: script postback applies FormAction', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.getByRole('button', { name: 'Export' }).click();
	await expect(page.locator(`#${BASE}status`)).toHaveText('exported via export');
	expect(page.url()).toContain('via=export');
});
