import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket538TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket538');
	await h.assertTitle('Verifying Ticket 538');

	await h.assertText(`${base}ALLog`, 'waiting for response...');

	await h.select(`${base}DataViewer`, 'empty :(');
	await h.byId(`${base}selectBtn`).click();
	await h.waitForAjaxCalls();
	await h.assertText(`${base}ALLog`, '0,');

	await h.select(`${base}DataSelector`, 'select data set 2');
	await h.waitForAjaxCalls();
	await h.select(`${base}DataViewer`, 'G1: Steven=>10');
	await h.addSelection(`${base}DataViewer`, 'G2: Kevin=>65');

	await h.byId(`${base}selectBtn`).click();
	await h.waitForAjaxCalls();
	await h.assertText(`${base}ALLog`, '4- "test1", 10- "test2",');
});
