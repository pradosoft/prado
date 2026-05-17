import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket828TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket828');

	await h.byId(`${base}submit1`).click();
	await h.waitForPageLoad();
	await h.assertVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
	await h.assertVisible(`${base}validator3`);
	await h.byId(`${base}list1_c0`).click();
	await h.addSelection(`${base}list2`, 'One');
	await h.addSelection(`${base}list2`, 'Two');
	await h.byId(`${base}list3_c3`).click();
	await h.byId(`${base}submit1`).click();
	await h.waitForPageLoad();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertNotVisible(`${base}validator3`);
	await h.byId(`${base}list1_c1`).click();
	await h.byId(`${base}list1_c2`).click();
	await h.byId(`${base}list1_c3`).click();
	await h.addSelection(`${base}list2`, 'Two');
	await h.byId(`${base}list1_c3`).click();
	await h.byId(`${base}submit1`).click();
	await h.waitForPageLoad();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertNotVisible(`${base}validator3`);
	await h.byId(`${base}list3_c3`).click();
	await h.byId(`${base}submit1`).click();
	await h.waitForPageLoad();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertNotVisible(`${base}validator3`);
});
