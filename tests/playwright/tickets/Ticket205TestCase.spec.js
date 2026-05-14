import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket205TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket205');
	await h.assertTitle('Verifying Ticket 205');

	await h.assertNotVisible(`${base}validator1`);

	await h.type(`${base}textbox1`, 'test');
	await h.byId(`${base}button1`).click();
	await h.pause(100);

	expect(h.alertText()).toBe('error');
	h.acceptAlert();

	await h.assertVisible(`${base}validator1`);

	// type() calls clear() that triggers a focus change and thus a second alert
	await h.typeSpecial(`${base}textbox1`, 'Prado');

	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
});
