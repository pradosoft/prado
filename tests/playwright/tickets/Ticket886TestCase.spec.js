import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

function phpDate(fmt, ts = null) {
	const d = ts !== null ? new Date(ts * 1000) : new Date();
	const pad = n => String(n).padStart(2, '0');
	// Single-pass substitution so an already-inserted token value (e.g. the "n"
	// in the month name "June") is not reprocessed by a later token replacement.
	const map = {
		m: pad(d.getUTCMonth() + 1),
		d: pad(d.getUTCDate()),
		Y: String(d.getUTCFullYear()),
		F: d.toLocaleString('en-US', { month: 'long', timeZone: 'UTC' }),
		n: String(d.getUTCMonth() + 1),
		j: String(d.getUTCDate()),
	};
	return fmt.replace(/[mdYFnj]/g, ch => map[ch]);
}

test('Ticket886TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket886');
	await h.assertTitle('Verifying Ticket 886');

	await h.byId(`${base}SendButton`).click();
	await h.assertSourceContains(phpDate('Y-m-d'));
});
