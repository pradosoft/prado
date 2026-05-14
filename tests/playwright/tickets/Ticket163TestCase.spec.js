import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket163TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket163');
	await h.assertSourceContains('100,00 kr');
	await h.assertSourceContains('0,00 kr');
	await h.assertSourceContains('−100,00 kr');
});
