import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket27TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket27');
	await h.assertTitle('Verifying Ticket 27');
	await h.byXPath("//input[@value='Agree']").click();
	await h.assertVisible('ctl0_Content_validator1');
	await h.type('ctl0_Content_TextBox', '122');
	await h.assertNotVisible('ctl0_Content_validator1');
	await h.byXPath("//input[@value='Disagree']").click();
	await h.assertNotVisible('ctl0_Content_validator1');
});
