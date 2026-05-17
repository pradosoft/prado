import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket470TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket470');
	await h.assertTitle('Verifying Ticket 470');
	await h.assertText(`${base}counter`, '0');
	await h.assertText(`${base}Results`, '');
	await h.assertNotVisible(`${base}validator1`);

	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}counter`, '0');
	await h.assertText(`${base}Results`, '');
	await h.assertVisible(`${base}validator1`);

	await h.type(`${base}TextBox`, 'hello');
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}counter`, '0');
	await h.assertText(`${base}Results`, 'OK!!!');
	await h.assertNotVisible(`${base}validator1`);

	// reload
	await h.byId(`${base}reloadButton`).click();
	await h.assertValue(`${base}TextBox`, 'hello');
	await h.assertText(`${base}counter`, '1');
	await h.assertText(`${base}Results`, '');
	await h.assertNotVisible(`${base}validator1`);

	await h.type(`${base}TextBox`, '');
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}counter`, '1');
	await h.assertText(`${base}Results`, '');
	await h.assertVisible(`${base}validator1`);

	await h.type(`${base}TextBox`, 'test');
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}counter`, '1');
	await h.assertText(`${base}Results`, 'OK!!!');
	await h.assertNotVisible(`${base}validator1`);
});
