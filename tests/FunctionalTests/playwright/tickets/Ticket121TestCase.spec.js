import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket121TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket121');
	await h.type('ctl0$Content$FooTextBox', '');
	await h.assertNotVisible('ctl0_Content_ctl1');
	await h.byXPath("//input[@type='image' and @id='ctl0_Content_ctl0']").click();
	await h.assertVisible('ctl0_Content_ctl1');
	await h.type('ctl0$Content$FooTextBox', 'content');
	await h.byXPath("//input[@type='image' and @id='ctl0_Content_ctl0']").click();
	await h.assertNotVisible('ctl0_Content_ctl1');
	await h.assertSourceContains('clicked at');
});
