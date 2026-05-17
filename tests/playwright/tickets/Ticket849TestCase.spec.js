import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

// phpDate: UTC — matches PHP-server-rendered values.
// localPhpDate: local TZ — matches the datepicker calendar today cell (new Date() in widget).
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
