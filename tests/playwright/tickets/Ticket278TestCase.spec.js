import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket278TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket278');
	await h.assertTitle('Verifying Ticket 278');
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertNotVisible(`${base}panel1`);

	await h.byId(`${base}button1`).click();
	await h.assertVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);

	await h.type(`${base}text1`, 'asd');
	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertNotVisible(`${base}panel1`);

	await h.byId(`${base}check1`).click();
	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
	await h.assertVisible(`${base}panel1`);

	await h.type(`${base}text1`, '');
	await h.type(`${base}text2`, 'asd');
	await h.byId(`${base}button1`).click();
	await h.assertVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertVisible(`${base}panel1`);

	await h.type(`${base}text1`, 'asd');
	await h.byId(`${base}button1`).click();
	await h.assertNotVisible(`${base}validator1`);
	await h.assertNotVisible(`${base}validator2`);
	await h.assertVisible(`${base}panel1`);

	await h.type(`${base}text1`, '');
	await h.type(`${base}text2`, '');
	await h.byId(`${base}button1`).click();
	await h.assertVisible(`${base}validator1`);
	await h.assertVisible(`${base}validator2`);
	await h.assertVisible(`${base}panel1`);
});
