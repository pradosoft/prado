import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket227TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket227');
	await h.assertTitle('Verifying Ticket 227');
});
