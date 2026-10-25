import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=DataGridSortTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the aria-sort value of each header cell of a grid, or null.
 */
async function ariaSorts(page, id) {
	return page.locator(`#${BASE}${id} thead th`).evaluateAll((cells) => cells.map((cell) => cell.getAttribute('aria-sort')));
}

/**
 * Returns the text of the first column of each body row of a grid.
 */
async function names(page, id) {
	return page.locator(`#${BASE}${id} tbody tr td:first-child`).allTextContents();
}

/**
 * The header cell of the current sort column carries aria-sort; clicking a column
 * sorts it ascending, and clicking it again reverses the direction.
 */
test('TDataGridSortTestCase: postback sort state', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('DataGrid Sort Test Case');

	expect(await ariaSorts(page, 'grid')).toEqual([null, 'ascending', null]);
	expect(await names(page, 'grid')).toEqual(['Bea', 'Cy', 'Al']);

	const grid = page.locator(`#${BASE}grid`);
	await grid.getByRole('link', { name: 'Name', exact: true }).click();
	await expect(grid.locator('thead th').first()).toHaveAttribute('aria-sort', 'ascending');
	expect(await ariaSorts(page, 'grid')).toEqual(['ascending', null, null]);
	expect(await names(page, 'grid')).toEqual(['Al', 'Bea', 'Cy']);

	await grid.getByRole('link', { name: 'Name', exact: true }).click();
	await expect(grid.locator('thead th').first()).toHaveAttribute('aria-sort', 'descending');
	expect(await names(page, 'grid')).toEqual(['Cy', 'Bea', 'Al']);
});

/**
 * aria-sort is on the column header, the role that carries it; the sort link
 * keeps its name, and it has focus after the sort, so keyboard and screen reader
 * users stay at the header they activated.
 */
test('TDataGridSortTestCase: accessible sort state and focus', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const grid = page.locator(`#${BASE}grid`);
	await expect(grid.getByRole('columnheader', { name: 'City', exact: true })).toHaveAttribute('aria-sort', 'ascending');
	await expect(grid.getByRole('columnheader', { name: 'Note', exact: true })).not.toHaveAttribute('aria-sort');

	await grid.getByRole('link', { name: 'Name', exact: true }).focus();
	await page.keyboard.press('Enter');
	await expect(grid.getByRole('columnheader', { name: 'Name', exact: true })).toHaveAttribute('aria-sort', 'ascending');
	await expect(grid.getByRole('link', { name: 'Name', exact: true })).toBeFocused();

	const active = page.locator(`#${BASE}activeGrid`);
	await active.getByRole('link', { name: 'City', exact: true }).focus();
	await page.keyboard.press('Enter');
	await h.waitForAjaxCalls();
	await expect(active.getByRole('columnheader', { name: 'City', exact: true })).toHaveAttribute('aria-sort', 'ascending');
	await expect(active.getByRole('link', { name: 'City', exact: true })).toBeFocused();
});

/**
 * A TActiveDataGrid re-rendered in a callback carries the new aria-sort.
 */
test('TDataGridSortTestCase: callback sort state', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	expect(await ariaSorts(page, 'activeGrid')).toEqual([null, null]);
	const grid = page.locator(`#${BASE}activeGrid`);
	await grid.getByRole('link', { name: 'City', exact: true }).click();
	await h.waitForAjaxCalls();
	await expect(grid.locator('thead th').nth(1)).toHaveAttribute('aria-sort', 'ascending');
	expect(await ariaSorts(page, 'activeGrid')).toEqual([null, 'ascending']);
	expect(await names(page, 'activeGrid')).toEqual(['Bea', 'Cy', 'Al']);

	await grid.getByRole('link', { name: 'City', exact: true }).click();
	await h.waitForAjaxCalls();
	await expect(grid.locator('thead th').nth(1)).toHaveAttribute('aria-sort', 'descending');
	expect(await names(page, 'activeGrid')).toEqual(['Al', 'Cy', 'Bea']);
});
