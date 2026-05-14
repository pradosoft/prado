import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket72TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket72');
	await h.type('ctl0$Content$K1', 'abc');
	await h.type('ctl0$Content$K2', 'efg');
	await h.byXPath("//input[@type='submit' and @value='Send']").click();
	await h.assertSourceContains('efg');
	await h.assertSourceNotContains('abcefg');
});
