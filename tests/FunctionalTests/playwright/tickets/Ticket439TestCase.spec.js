import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket439TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket439');
	await h.assertTitle('Verifying Ticket 439');
	await h.byId(`${base}button1`).click();
	await h.assertTitle('Verifying Home');
});
