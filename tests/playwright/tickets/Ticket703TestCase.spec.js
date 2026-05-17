import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket703TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket703.Ticket703');
	await h.assertTitle('Verifying Ticket703.Ticket703 703.703');

	// Start with an empty log
	await h.byId(`${base}ctl2`).click();
	await h.waitForAjaxCalls();
	await h.pause(1000);
	await h.assertText(`${base}logBox`, '');

	await h.type(`${base}logMessage`, 'Test of prado logging system');
	await h.byId(`${base}ctl0`).click();
	await h.waitForAjaxCalls();
	await h.byId(`${base}ctl1`).click();
	await h.waitForAjaxCalls();
	await h.pause(1000);
	// logBox is a textarea — use inputValue() to read its current content
	expect(await h.byId(`${base}logBox`).inputValue()).toContain('Test of prado logging system');

	// Clean log for next run
	await h.byId(`${base}ctl2`).click();
	await h.waitForAjaxCalls();
	await h.pause(1000);
	await h.assertText(`${base}logBox`, '');
});
