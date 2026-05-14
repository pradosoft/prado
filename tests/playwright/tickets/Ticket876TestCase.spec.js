import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket876TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket876');
	await h.assertTitle('Verifying Ticket 876');

	await h.assertElementPresent('//link[@rel="stylesheet"]');
	await h.byId(`${base}Button`).click();
	await h.assertElementNotPresent('//link[@rel="stylesheet"]');
});
