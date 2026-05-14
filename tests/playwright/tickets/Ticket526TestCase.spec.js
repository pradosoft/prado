import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket526TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket526');
	await h.assertTitle('Verifying Ticket 526');

	await h.assertElementNotPresent(`${base}dpbutton`);

	await h.byId(`${base}btn`).click();
	await h.assertElementPresent(`${base}dpbutton`);
});
