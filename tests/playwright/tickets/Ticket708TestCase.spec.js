import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket708TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket708');
	await h.assertTitle('Verifying Ticket 708');

	await h.byId(`${base}grid_ctl1_RadioButton`).click();
	await h.assertText(`${base}Result`, 'You have selected Radio Button #1');

	await h.byId(`${base}grid_ctl2_RadioButton`).click();
	await h.assertText(`${base}Result`, 'You have selected Radio Button #2');

	await h.byId(`${base}grid_ctl3_RadioButton`).click();
	await h.assertText(`${base}Result`, 'You have selected Radio Button #3');

	await h.byId(`${base}grid_ctl4_RadioButton`).click();
	await h.assertText(`${base}Result`, 'You have selected Radio Button #4');
});
