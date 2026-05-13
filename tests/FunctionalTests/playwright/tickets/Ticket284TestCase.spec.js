import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket284TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket284');
	await h.assertSourceContains('Verifying Ticket 284');
	await h.byId('ctl0_Content_ctl1').click();
});
