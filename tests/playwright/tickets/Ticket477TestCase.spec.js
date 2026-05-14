import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket477TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket477');
	await h.assertTitle('Verifying Ticket 477');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.byId(`${base}list1_c1`).click();
	await h.assertNotVisible(`${base}validator2`);
	await h.assertVisible(`${base}validator1`);

	await h.byId(`${base}list2_c1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
});
