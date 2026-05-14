import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket585TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket585');
	await h.assertTitle('Verifying Ticket 585');

	await h.assertText('error', '');
	await h.assertNotVisible(`${base}validator1`);

	await h.byId(`${base}button1`).click();
	await h.assertText('error', 'Success');
	await h.assertNotVisible(`${base}validator1`);

	await h.type(`${base}test`, '15-03-2007');
	await h.byId(`${base}button1`).click();
	await h.assertText('error', 'Error');
	await h.assertVisible(`${base}validator1`);
});
