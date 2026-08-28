import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=InPlaceDropDownListTest';
const LABEL  = 'ctl0_Content_ddl__label';
const SELECT = 'ctl0_Content_ddl';
const STATUS = 'ctl0_Content_status';
const LAZY_LABEL  = 'ctl0_Content_lazy__label';
const LAZY_SELECT = 'ctl0_Content_lazy';
const LAZY_STATUS = 'ctl0_Content_lazyStatus';

/**
 * TInPlaceDropDownList renders a label span and a hidden select. Clicking the
 * label swaps to the select; changing the selection posts a callback that
 * raises OnSelectedIndexChanged, updates the label, and re-hides the select.
 */
test('TInPlaceDropDownListTestCase: click to edit and select via callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('In Place Drop Down List Test Case');

	// Initial state: label shows the selected item text, select is hidden
	await expect(page.locator(`#${LABEL}`)).toBeVisible();
	await expect(page.locator(`#${LABEL}`)).toHaveText('Red');
	await expect(page.locator(`#${SELECT}`)).toBeHidden();

	// Clicking the label swaps to the select
	await page.locator(`#${LABEL}`).click();
	await expect(page.locator(`#${SELECT}`)).toBeVisible();
	await expect(page.locator(`#${LABEL}`)).toBeHidden();

	// Changing the selection dispatches a callback and re-hides the select
	await page.locator(`#${SELECT}`).selectOption('green');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('changed: green');
	await expect(page.locator(`#${LABEL}`)).toBeVisible();
	await expect(page.locator(`#${LABEL}`)).toHaveText('Green');
	await expect(page.locator(`#${SELECT}`)).toBeHidden();
});

/**
 * A server-side setSelectedValue() during an unrelated callback updates both
 * the select selection (through the active list adapter) and the label text.
 */
test('TInPlaceDropDownListTestCase: server-side selection updates the label', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('#ctl0_Content_btnServerSelect').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('server selected');
	await expect(page.locator(`#${LABEL}`)).toHaveText('Blue');
	await expect(page.locator(`#${SELECT}`)).toHaveValue('blue');
});

/**
 * Changing the selected item's text during a callback refreshes the label,
 * even though the selection itself did not change.
 */
test('TInPlaceDropDownListTestCase: renaming the selected item updates the label', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${LABEL}`)).toHaveText('Red');

	await page.locator('#ctl0_Content_btnRenameItem').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('renamed');
	await expect(page.locator(`#${LABEL}`)).toHaveText('Renamed');
	await expect(page.locator(`#${SELECT} option`).first()).toHaveText('Renamed');
});

/**
 * Changing EmptyDisplayText during a callback reaches a label that is
 * currently showing the placeholder.
 */
test('TInPlaceDropDownListTestCase: empty display text follows a server change', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${LAZY_LABEL}`)).toHaveText('Stale');

	// Clearing the items empties the label, which then shows the placeholder.
	await page.locator('#ctl0_Content_btnEmptyText').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${LAZY_STATUS}`)).toHaveText('empty text changed');
	await expect(page.locator(`#${LAZY_LABEL}`)).toHaveText('(nothing left)');
});

/**
 * setReadOnly(true) during a callback prevents entering edit mode.
 */
test('TInPlaceDropDownListTestCase: read only blocks edit mode', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('#ctl0_Content_btnReadOnly').click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('readonly');

	await page.locator(`#${LABEL}`).click();
	await expect(page.locator(`#${SELECT}`)).toBeHidden();
	await expect(page.locator(`#${LABEL}`)).toBeVisible();
});

/**
 * With an OnLoadingItems handler, entering edit mode first loads the item
 * list from the server; the stale items are replaced before selection.
 */
test('TInPlaceDropDownListTestCase: OnLoadingItems replaces items on edit', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.locator(`#${LAZY_LABEL}`)).toHaveText('Stale');

	// Entering edit mode dispatches the load-items callback
	await page.locator(`#${LAZY_LABEL}`).click();
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${LAZY_SELECT}`)).toBeVisible();
	await expect(page.locator(`#${LAZY_SELECT} option`)).toHaveText(['Fresh A', 'Fresh B']);
	await expect(page.locator(`#${LAZY_SELECT}`)).toHaveValue('a');

	// Selecting one of the fresh items posts the new value
	await page.locator(`#${LAZY_SELECT}`).selectOption('b');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${LAZY_STATUS}`)).toHaveText('changed: b');
	await expect(page.locator(`#${LAZY_LABEL}`)).toHaveText('Fresh B');
	await expect(page.locator(`#${LAZY_SELECT}`)).toBeHidden();
});
