import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=InPlaceListBoxTest';
const LABEL  = 'ctl0_Content_lb__label';
const SELECT = 'ctl0_Content_lb';
const STATUS = 'ctl0_Content_status';

/**
 * A multiple-selection TInPlaceListBox accumulates the selection while the
 * editor is open and commits it on blur. Individual toggles must not post a
 * callback or collapse the editor, or a multi-item selection could never be
 * built interactively.
 */
test('TInPlaceListBoxTestCase: multi-select accumulates and commits on blur', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('In Place List Box Test Case');

	await expect(page.locator(`#${LABEL}`)).toHaveText('Red');
	await expect(page.locator(`#${SELECT}`)).toBeHidden();

	// Open the editor
	await page.locator(`#${LABEL}`).click();
	await expect(page.locator(`#${SELECT}`)).toBeVisible();

	// Build a multi-selection; the editor must stay open and nothing is posted.
	// Settle any callback first: with per-change posting (the bug) the callback
	// would fire and collapse the editor here.
	await page.locator(`#${SELECT}`).selectOption(['red', 'green']);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('none');
	await expect(page.locator(`#${SELECT}`)).toBeVisible();

	// Blur (click away from the select) commits the accumulated selection
	await page.locator('h1').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('changed: red,green');
	await expect(page.locator(`#${LABEL}`)).toHaveText('Red + Green');
	await expect(page.locator(`#${SELECT}`)).toBeHidden();
});

/**
 * Blurring without changing the selection just closes the editor.
 */
test('TInPlaceListBoxTestCase: blur without change does not post', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator(`#${LABEL}`).click();
	await expect(page.locator(`#${SELECT}`)).toBeVisible();
	await page.locator('h1').click();
	await expect(page.locator(`#${STATUS}`)).toHaveText('none');
	await expect(page.locator(`#${LABEL}`)).toHaveText('Red');
});

/**
 * A server-side setSelectedValues() during a callback updates the joined label.
 */
test('TInPlaceListBoxTestCase: server-side multi-selection updates the label', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('#ctl0_Content_btnServerSelect').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('server selected');
	await expect(page.locator(`#${LABEL}`)).toHaveText('Green + Blue');
});

/**
 * Clearing the selection during a callback shows the EmptyDisplayText.
 */
test('TInPlaceListBoxTestCase: clearing the selection shows the empty text', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('#ctl0_Content_btnClear').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('cleared');
	await expect(page.locator(`#${LABEL}`)).toHaveText('(none selected)');
});
