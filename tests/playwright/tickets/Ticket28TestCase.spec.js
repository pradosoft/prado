import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket28TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket28');
	await h.assertSourceContains('Label 1');
	await h.byLinkText('Click Me').click();
	await h.assertSourceContains('Link Button 1 Clicked!');
});
