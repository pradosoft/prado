import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket274TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket274');
	await h.assertTitle('Verifying Ticket 274');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.byId(`${base}button1`).click();
	await h.assertVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.type(`${base}MyDate`, 'asd');
	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
});
