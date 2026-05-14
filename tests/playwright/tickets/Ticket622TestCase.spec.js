import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket622TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket622');
	await h.assertTitle('Verifying Ticket 622');

	await h.byId(`${base}ctl0`).click();
	await h.waitForAjaxCalls();

	// After clearing styles, getAttribute('style') returns null (absent) or ''
	// (empty attribute).  Both represent "no style applied".
	expect(await h.byId(`${base}ALB`).getAttribute('style') ?? '').toBe('');
	expect(await h.byCssSelector('span#acb span').getAttribute('style') ?? '').toBe('');
	expect(await h.byCssSelector('span#arb span').getAttribute('style') ?? '').toBe('');
});
