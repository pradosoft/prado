import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket488TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('active-controls/index.php?page=CustomValidatorByPass');
	await h.assertSourceContains('Custom Login');
	await h.assertNotVisible('loginBox');
	await h.byId('showLogin').click();
	await h.assertVisible('loginBox');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.byId(`${base}checkLogin`).click();
	await h.assertVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.type(`${base}Username`, 'tea');
	await h.type(`${base}Password`, 'mmama');

	await h.byId(`${base}checkLogin`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);

	await h.type(`${base}Password`, 'test');
	await h.byId(`${base}checkLogin`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
});
