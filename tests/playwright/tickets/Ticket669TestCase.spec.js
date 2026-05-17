import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket669TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket669');
	await h.assertTitle('Verifying Ticket 669');

	await h.assertSourceContains('1 - Test without callback');
	await h.assertValue(`${base}tb1`, 'ActiveTextBox');
	await h.assertValue(`${base}tb2`, 'TextBox in ActivePanel');

	await h.byId(`${base}ctl4`).click();
	await h.assertValue(`${base}tb1`, 'ActiveTextBox +1');
	await h.assertValue(`${base}tb2`, 'TextBox in ActivePanel +1');

	await h.byId(`${base}ctl1`).click();
	await h.assertSourceContains('2 - Test callback with 2nd ActivePanel');
	await h.assertValue(`${base}tb3`, 'ActiveTextBox');
	await h.assertValue(`${base}tb4`, 'TextBox in ActivePanel');
	await h.assertValue(`${base}tb5`, 'TextBox in ActivePanel');

	await h.byId(`${base}ctl6`).click();

	await h.assertValue(`${base}tb3`, 'ActiveTextBox +1');
	await h.assertValue(`${base}tb4`, 'TextBox in ActivePanel +1');
	await h.assertValue(`${base}tb5`, 'TextBox in ActivePanel +1');

	await h.byId(`${base}ctl2`).click();
	await h.assertSourceContains('3 - Test callback without 2nd ActivePanel');
	await h.assertValue(`${base}tb6`, 'ActiveTextBox');
	await h.assertValue(`${base}tb7`, 'TextBox in Panel');

	await h.byId(`${base}ctl8`).click();

	await h.assertValue(`${base}tb6`, 'ActiveTextBox +1');
	await h.assertValue(`${base}tb7`, 'TextBox in Panel +1');
});
