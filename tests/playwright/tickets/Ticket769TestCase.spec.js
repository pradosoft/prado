import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket769TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket769');
	await h.assertTitle('Verifying Ticket 769');

	await h.byId(`${base}ctl0`).click();
	await h.assertVisible(`${base}ctl1`);

	await h.type(`${base}T1`, 'Prado');
	await h.byId(`${base}ctl0`).click();
	await h.assertNotVisible(`${base}ctl1`);
	await h.assertValue(`${base}ctl0`, 'T1 clicked');

	await h.byId(`${base}ctl2`).click();
	await h.assertText(`${base}B`, 'This is B');
	await h.byId(`${base}ctl3`).click();

	await h.type(`${base}T1`, '');
	await h.byId(`${base}ctl0`).click();
	await h.assertVisible(`${base}ctl1`);
	await h.type(`${base}T1`, 'Prado');
	await h.byId(`${base}ctl0`).click();
	await h.assertNotVisible(`${base}ctl1`);
	await h.assertValue(`${base}ctl0`, 'T1 clicked clicked');
});
