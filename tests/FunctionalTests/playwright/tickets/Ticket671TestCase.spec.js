import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket671TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket671');
	await h.assertTitle('Verifying Ticket 671');

	await h.assertNotVisible(`${base}ctl0`);
	// Click submit
	await h.byId(`${base}ctl1`).click();
	await h.assertText(`${base}ctl0`, 'Please Select Test 3');
	await h.assertVisible(`${base}ctl0`);
	await h.select(`${base}addl`, 'Test 2');
	await h.assertVisible(`${base}ctl0`);
	await h.assertText(`${base}lblResult`, "You have selected 'Test 2'. But this is not valid !");
	await h.select(`${base}addl`, 'Test 3');
	await h.assertNotVisible(`${base}ctl0`);
	await h.assertText(`${base}lblResult`, "You have selected 'Test 3'.");
	await h.byId(`${base}ctl1`).click();
	await h.assertText(`${base}lblResult`, 'You have successfully validated the form');

	await h.type(`${base}testTextBox`, 'test');
	await h.byId(`${base}ctl3`).click();
	await h.assertVisible(`${base}ctl2`);
	await h.type(`${base}testTextBox`, 'Prado');
	await h.byId(`${base}ctl3`).click();
	await h.assertNotVisible(`${base}ctl2`);
	await h.assertText(`${base}lblResult2`, 'Thanks !');
});
