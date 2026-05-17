import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket207TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket207');
	await h.assertTitle('Verifying Ticket 207');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.byId(`${base}button1`).click();
	await h.pause(50);

	expect(h.alertText()).toBe('error on text1 fired');
	h.acceptAlert();

	await h.assertVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);

	await h.type(`${base}text1`, 'test');
	await h.assertVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);

	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);

	await h.type(`${base}text1`, '');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);

	await h.byId(`${base}button1`).click();
	await h.pause(50);

	expect(h.alertText()).toBe('error on text1 fired');
	h.acceptAlert();

	await h.assertVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
});
