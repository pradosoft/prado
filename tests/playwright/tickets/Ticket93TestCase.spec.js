import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket93TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket93');
	await h.assertSourceContains('ValidationGroups without any inputs with grouping');
});
