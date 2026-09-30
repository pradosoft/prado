import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

/**
 * A click in a TActiveTableCell with an OnCellSelected handler, inside a
 * TActiveTableRow with an OnRowSelected handler, sends one callback. The
 * server raises OnCellSelected and bubbles it to OnRowSelected once.
 */
test('ActiveTableRowTestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('active-controls/index.php?page=ActiveTableRowTest');

	let callbacks = 0;
	page.on('request', (request) => {
		if (request.method() === 'POST' && request.url().includes('ActiveTableRowTest')) {
			callbacks++;
		}
	});

	await h.byId(`${base}Row1Cell1`).click();
	await h.assertText(`${base}Result`, 'cell:0 row:0');
	await h.waitForAjaxCalls();
	expect(callbacks).toBe(1);

	await h.byId(`${base}Row1Cell2`).click();
	await h.assertText(`${base}Result`, 'cell:0 row:0 row:0');
	await h.waitForAjaxCalls();
	expect(callbacks).toBe(2);
});
