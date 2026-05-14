import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket169TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket169');
	await h.assertNotVisible('ctl0_Content_validator1');
	await h.byId('ctl0_Content_ctl0').click();
	await h.assertVisible('ctl0_Content_validator1');
});
