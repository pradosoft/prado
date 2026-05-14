import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket54TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket54');
	await h.assertSourceContains('|A|a|B|b|C|');
});
