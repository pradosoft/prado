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
function phpTime() { return Math.floor(Date.now() / 1000); }

test('Ticket656TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket656');
	await h.assertTitle('Verifying Ticket 656');

	// First test, current date
	await h.byId(`${base}btnUpdate`).click();
	await h.assertText(`${base}lblStatus`, phpDate('d-m-Y'));

	// Then, set another date
	const year = new Date().getFullYear() - 2;
	await h.select(`${base}datePicker_day`, '20');
	await h.select(`${base}datePicker_month`, '10');
	await h.select(`${base}datePicker_year`, String(year));
	await h.byId(`${base}btnUpdate`).click();
	// mktime(0,0,0,10,20,year) in JS:
	const ts = Math.floor(new Date(year, 9, 20, 0, 0, 0).getTime() / 1000);
	await h.assertText(`${base}lblStatus`, phpDate('d-m-Y', ts));
});
