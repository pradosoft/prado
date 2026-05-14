import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

function phpDate(fmt, ts = null) {
	const d = ts !== null ? new Date(ts * 1000) : new Date();
	const pad = n => String(n).padStart(2, '0');
	return fmt
		.replace('m', pad(d.getUTCMonth() + 1))
		.replace('d', pad(d.getUTCDate()))
		.replace('Y', String(d.getUTCFullYear()))
		.replace('F', d.toLocaleString('en-US', { month: 'long', timeZone: 'UTC' }))
		.replace('n', String(d.getUTCMonth() + 1))
		.replace('j', String(d.getUTCDate()));
}

test('Ticket886TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket886');
	await h.assertTitle('Verifying Ticket 886');

	await h.byId(`${base}SendButton`).click();
	await h.assertSourceContains(phpDate('Y-m-d'));
});
