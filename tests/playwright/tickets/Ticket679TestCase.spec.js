import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket679TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket679');
	await h.assertTitle('Verifying Ticket 679');

	// First part of ticket : Repeater bug
	await h.byId(`${base}ctl0`).click();
	await h.assertText(`${base}myLabel`, 'outside');
	await h.assertVisible(`${base}myLabel`);

	// Reload completely the page
	await h.refresh();

	await h.byId(`${base}Repeater_ctl0_ctl0`).click();
	await h.assertText(`${base}myLabel`, 'inside');
	await h.assertVisible(`${base}myLabel`);

	// Second part of ticket : ARB bug
	await h.assertNotChecked(`${base}myRadioButton`);
	await h.byId(`${base}ctl1`).click();
	await h.assertChecked(`${base}myRadioButton`);
	await h.byId(`${base}ctl2`).click();
	await h.assertNotChecked(`${base}myRadioButton`);
});
