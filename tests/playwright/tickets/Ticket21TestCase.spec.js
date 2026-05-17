import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket21TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket21');
	await h.assertTitle('Verifying Ticket 21');
	await h.byId('ctl0_Content_button1').click();
	await h.assertSourceContains('Radio button clicks: 1');
	await h.byId('ctl0_Content_button1').click();
	await h.assertSourceContains('Radio button clicks: 1');
});
