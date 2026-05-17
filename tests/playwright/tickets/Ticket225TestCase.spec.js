import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket225TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket225');
	await h.assertSourceContains('RadioButton Group Tests');
	await h.assertText(`${base}label1`, 'Label 1');

	await h.assertNotVisible(`${base}validator1`);
	await h.byId(`${base}button4`).click();
	await h.assertVisible(`${base}validator1`);

	await h.byId(`${base}button2`).click();
	await h.byId(`${base}button4`).click();

	await h.assertNotVisible(`${base}validator1`);
	await h.assertText(`${base}label1`, 'ctl0$Content$button1 ctl0$Content$button2 ctl0$Content$button3');
});
