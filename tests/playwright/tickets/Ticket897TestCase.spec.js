import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket897TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket897');
	await h.assertTitle('Verifying Ticket 897');

	await h.select(`${base}Date_month`, '10');
	await h.select(`${base}Date_day`, '22');

	await h.byId(`${base}SendButton`).click();
	await h.assertSourceContains(`${new Date().getUTCFullYear()}-10-22`);
});
