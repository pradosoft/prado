import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

// phpDate: UTC — matches PHP-server-rendered values.
// localPhpDate: local TZ — matches the datepicker JS popup "Today" button (new Date() in widget).
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

test('Ticket785TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	const year = new Date().getUTCFullYear() - 1;
	await h.url('tickets/index.php?page=Ticket785');
	await h.assertTitle('Verifying Ticket 785');

	await h.assertText('selDate', '');
	await h.select(`${base}datePicker_year`, String(year));
	const expectedDate = phpDate('d-m') + '-' + year;
	await h.assertText('selDate', expectedDate);

	await h.byId(`${base}datePickerbutton`).click();
	await h.byCssSelector('input.todayButton').click(); // JS popup button — uses browser local TZ
	await h.byCssSelector('body').click(); // Hide calendar
	await h.assertText('selDate', localPhpDate('d-m-Y'));

	await h.assertText('selDate2', '');
	await h.type(`${base}datePicker2`, '12/05/2006');
	await h.byCssSelector('body').click();
	await h.assertText('selDate2', '12/05/2006');
});
