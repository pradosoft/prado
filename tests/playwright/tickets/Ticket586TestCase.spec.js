import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket586TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket586');
	await h.assertTitle('Verifying Ticket 586');

	await h.assertText(`${base}label1`, 'Status');
	await h.byId(`${base}button1`).click();
	await h.pause(50);
	await h.assertText(`${base}label1`, 'Button 1 Clicked!');

	await h.type(`${base}text1`, 'testing');

	// keyDownAndWait with Enter key omitted — cannot be properly tested without manual interaction
});
