import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket592TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket592');
	await h.assertTitle('Verifying Ticket 592');

	await h.assertText(`${base}label1`, 'Label 1');

	await h.byId(`${base}radio1`).click();
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}label1`, 'radio1 checked:{1} radio2 checked:{}');

	await h.byId(`${base}radio2`).click();
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}label1`, 'radio1 checked:{1} radio2 checked:{1}');

	await h.byId(`${base}bad_radio1`).click();
	await h.byId(`${base}button2`).click();
	await h.assertText(`${base}label1`, 'bad_radio1 checked:{1} bad_radio2 checked:{}');

	await h.byId(`${base}bad_radio2`).click();
	await h.byId(`${base}button2`).click();
	await h.assertText(`${base}label1`, 'bad_radio1 checked:{} bad_radio2 checked:{1}');

	await h.byId(`${base}bad_radio3`).click();
	await h.byId(`${base}button3`).click();
	await h.assertText(`${base}label1`, 'bad_radio3 checked:{1} bad_radio4 checked:{}');

	await h.byId(`${base}bad_radio4`).click();
	await h.byId(`${base}button3`).click();
	await h.assertText(`${base}label1`, 'bad_radio3 checked:{} bad_radio4 checked:{1}');
});
