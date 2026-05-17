import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket290TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket290');
	await h.assertTitle('Verifying Ticket 290');

	await h.assertText(`${base}label1`, 'Label 1');
	await h.assertText(`${base}label2`, 'Label 2');

	await h.type(`${base}textbox1`, 'test');

	await h.byId(`${base}textbox1`).click();
	await h.keys('Enter');

	await h.assertText(`${base}label1`, 'Doing Validation');
	await h.assertText(`${base}label2`, 'Button 2 (default) Clicked!');
});
