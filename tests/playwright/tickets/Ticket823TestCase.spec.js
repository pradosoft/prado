import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket823TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket823');
	await h.assertTitle('Verifying Ticket 823');

	await h.assertElementPresent('//option[@value=""]');
	await h.assertElementPresent('//option[.="Choose..."]');
});
