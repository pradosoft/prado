import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket653TestCase', async ({ page }) => {
	const h = genericHelper(page);
	// Open with 'Friendly URL'
	await h.url('tickets/index.php/ticket653');
	await h.assertTitle('Verifying Ticket 653');

	await h.assertText('textspan', 'This is the page for Ticket653');
});
