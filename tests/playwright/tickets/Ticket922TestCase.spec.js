import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket922TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket922');
	await h.assertTitle('Verifying Ticket 922');

	await h.typeSpecial(`${base}Text`, 'two words');
	await h.byId(`${base}Button`).click();
	await h.assertText(`${base}Result`, 'two words');
});
