import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket595TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket595');
	await h.assertTitle('Verifying Ticket 595');

	await h.click(`${base}ctl2`);
	await h.assertAttribute(`${base}A@class`, 'errorclassA');

	await h.type(`${base}A`, 'Prado');
	await h.click(`${base}ctl2`);
	await h.assertAttribute(`${base}A@class`, 'errorclassA');

	await h.type(`${base}A`, 'test@prado.local');
	await h.click(`${base}ctl2`);
	await h.assertAttribute(`${base}A@class`, '');

	await h.click(`${base}ctl5`);
	await h.assertAttribute(`${base}B@class`, 'errorclassB');

	await h.type(`${base}B`, 'Prado');
	await h.click(`${base}ctl5`);
	await h.assertAttribute(`${base}B@class`, 'errorclassB');

	await h.pause(50);
	await h.type(`${base}B`, 'test@prado.local');
	await h.click(`${base}ctl5`);
	await h.assertAttribute(`${base}B@class`, '');
});
