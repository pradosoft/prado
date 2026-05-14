import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket507TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket507');
	await h.assertTitle('Verifying Ticket 507');

	await h.assertText(`${base}label1`, 'Label 1');

	await h.byId(`${base}button1`).click();
	await h.waitForAjaxCalls();

	await h.select(`${base}list1`, 'item 1');
	await h.waitForAjaxCalls();
	await h.assertText(`${base}label1`, 'Selection: value 1');

	await h.addSelection(`${base}list1`, 'item 3');
	await h.waitForAjaxCalls();
	await h.assertText(`${base}label1`, 'Selection: value 1, value 3');
});
