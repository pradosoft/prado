import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket433TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket433');
	await h.assertTitle('Verifying Ticket 433');
	await h.assertText(`${base}VoteClick`, 'BEFORE click');

	await h.byId(`${base}VoteClick`).click();
	await h.assertText(`${base}VoteClick`, 'AFTER click CALLBACK DONE');
});
