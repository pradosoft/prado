import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket670TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket670');
	await h.assertTitle('Verifying Ticket 670');

	await h.type(`${base}datePicker`, '07-07-2003');
	await h.byId(`${base}datePickerbutton`).click();
	await h.byId(`${base}ok`).click();
	await h.assertText(`${base}lbl`, '07-07-2007');
});
