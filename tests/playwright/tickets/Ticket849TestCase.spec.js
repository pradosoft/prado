import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

// phpDate: UTC — matches PHP-server-rendered values.
// localPhpDate: local TZ — matches the datepicker calendar today cell (new Date() in widget).
function localPhpDate(fmt, ts = null) {
	const d = ts !== null ? new Date(ts * 1000) : new Date();
	const pad = n => String(n).padStart(2, '0');
	// Single-pass substitution so an already-inserted token value (e.g. the "n"
	// in the month name "June") is not reprocessed by a later token replacement.
	const map = {
		m: pad(d.getMonth() + 1),
		d: pad(d.getDate()),
		Y: String(d.getFullYear()),
		F: d.toLocaleString('en-US', { month: 'long' }),
		n: String(d.getMonth() + 1),
		j: String(d.getDate()),
	};
	return fmt.replace(/[mdYFnj]/g, ch => map[ch]);
}

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

test('Ticket849TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket849');
	await h.assertTitle('Verifying Ticket 849');

	await h.byId(`${base}ctl0`).click();
	await h.byCssSelector('td.date.today.selected').click(); // JS calendar cell — uses browser local TZ
	await h.pause(1000);
	await h.assertValue(`${base}ctl0`, localPhpDate('m-d-Y'));
});
