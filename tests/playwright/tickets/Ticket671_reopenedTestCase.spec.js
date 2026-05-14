import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket671_reopenedTestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket671_reopened');
	await h.assertTitle('Verifying Ticket 671_reopened');

	// Type wrong value
	await h.type(`${base}testField`, 'abcd');
	await h.byId(`${base}ctl4`).click();
	await h.assertVisible(`${base}ctl2`);
	await h.assertText(`${base}Result`, 'Check callback called (1) --- Save callback called DATA NOK');

	// Reclick, should not have any callback
	await h.byId(`${base}ctl4`).click();
	await h.assertVisible(`${base}ctl2`);
	await h.assertText(`${base}Result`, 'Check callback called (2) --- Save callback called DATA NOK');

	// Type right value
	await h.type(`${base}testField`, 'Test');
	await h.byId(`${base}ctl4`).click();
	await h.assertNotVisible(`${base}ctl2`);
	await h.assertText(`${base}Result`, 'Check callback called (3) --- Save callback called DATA OK');

	// Type empty value
	await h.type(`${base}testField`, '');
	await h.byId(`${base}ctl4`).click();
	await h.assertVisible(`${base}ctl1`);
	await h.assertNotVisible(`${base}ctl2`);
	await h.assertText(`${base}Result`, 'Check callback called (3) --- Save callback called DATA OK');

	// Type right value
	await h.type(`${base}testField`, 'Test');
	await h.byId(`${base}ctl4`).click();
	await h.assertNotVisible(`${base}ctl1`);
	await h.assertNotVisible(`${base}ctl2`);
	await h.assertText(`${base}Result`, 'Check callback called (4) --- Save callback called DATA OK');
});
