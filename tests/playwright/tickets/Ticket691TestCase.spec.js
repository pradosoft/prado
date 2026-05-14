import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket691TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket691');
	await h.assertTitle('Verifying Ticket 691');

	await h.byXPath(`//input[@id='${base}List_c2']/../..`).click();
	await h.assertText(`${base}Title`, 'Thanks');
	await h.assertText(`${base}Result`, 'You vote 3');
});
