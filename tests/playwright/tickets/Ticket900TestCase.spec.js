import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket900TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket900');
	await h.assertTitle('Verifying Ticket 900');

	await h.byName('ctl0$Content$DataGrid$ctl1$ctl3').click();
	await h.pause(50);
	await h.type(`${base}DataGrid_ctl1_TextBox`, '');
	await h.byId(`${base}DataGrid_ctl1_ctl3`).click();
	await h.pause(50);
	await h.byName('ctl0$Content$DataGrid$ctl1$ctl4').click();
	await h.pause(50);
	await h.assertText(`${base}CommandName`, 'cancel');
});
