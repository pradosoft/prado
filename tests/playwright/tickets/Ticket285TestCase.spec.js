import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket285TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket285');
	await h.assertSourceContains('350.00');
	await h.assertSourceContains('349.99');
});
