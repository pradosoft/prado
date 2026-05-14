import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket191TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket191');
	await h.type('ctl0$Content$TextBox2', 'test');
	await h.byName('ctl0$Content$ctl0').click();
	await h.pause(50);
	await h.type('ctl0$Content$TextBox', 'test');
	await h.byName('ctl0$Content$ctl1').click();
	await h.assertNotVisible('ctl0_Content_ctl2');
});
