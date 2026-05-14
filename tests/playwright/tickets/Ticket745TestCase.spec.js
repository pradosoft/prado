import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket745TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket745');
	await h.assertTitle('Verifying Ticket 745');

	await h.select(`${base}Wizard1_DropDownList1`, 'Green');
	await h.byId(`${base}Wizard1_ctl4_ctl1`).click();
	await h.assertSourceContains('Step 3 of 3');
});
