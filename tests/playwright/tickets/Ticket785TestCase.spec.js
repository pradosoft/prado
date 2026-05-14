import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

// phpDate: UTC — matches PHP-server-rendered values.
// localPhpDate: local TZ — matches the datepicker JS popup "Today" button (new Date() in widget).
function localPhpDate(fmt, ts = null) {
	const d = ts !== null ? new Date(ts * 1000) : new Date();
	const pad = n => String(n).padStart(2, '0');
	return fmt
		.replace('m', pad(d.getMonth() + 1))
		.replace('d', pad(d.getDate()))
		.replace('Y', String(d.getFullYear()))
		.replace('F', d.toLocaleString('en-US', { month: 'long' }))
		.replace('n', String(d.getMonth() + 1))
		.replace('j', String(d.getDate()));
}

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
