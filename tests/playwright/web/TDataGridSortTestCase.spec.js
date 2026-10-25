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
