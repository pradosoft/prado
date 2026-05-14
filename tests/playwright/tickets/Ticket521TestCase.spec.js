import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket521TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket521');
	await h.assertTitle('Verifying Ticket 521');
	await h.assertText(`${base}label1`, 'Label 1');

	await h.byId(`${base}button1`).click();
	await h.pause(1200);

	await h.assertText(`${base}label1`, 'Button 1 was clicked on callback');
});
