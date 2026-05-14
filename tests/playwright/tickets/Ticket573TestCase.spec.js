import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket573TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket573');
	await h.assertTitle('Verifying Ticket 573');

	await h.assertText('test1', '10.00');
});
