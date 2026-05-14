import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket246TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket246');
	await h.assertTitle('Verifying Ticket 246');
});
