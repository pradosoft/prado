import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket698TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket698');
	await h.assertTitle('Verifying Ticket 698');

	await h.byId(`${base}switchContentTypeButton`).click();
	await h.assertVisible(`${base}EditHtmlTextBox`);
	await h.byId(`${base}switchContentTypeButton`).click();
	await h.pause(1000);
	await h.assertNotVisible(`${base}EditHtmlTextBox`);
});
