import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket722TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket722');
	await h.assertTitle('Verifying Ticket 722');

	await h.assertText(`${base}InPlaceTextBox__label`, 'Editable Text');
	await h.byId(`${base}InPlaceTextBox__label`).click();

	await h.assertVisible(`${base}InPlaceTextBox`);

	// calling clear() would trigger an onBlur event on the textbox
	// so we empty the textbox one char at a time
	await h.byId(`${base}InPlaceTextBox`).click();

	await h.keys('End');
	for (let i = 0; i < 13; i++) {
		await h.keys('Backspace');
	}

	await h.type(`${base}InPlaceTextBox`, 'Prado');
	await h.assertNotVisible(`${base}InPlaceTextBox`);
	await h.assertText(`${base}InPlaceTextBox__label`, 'Prado');

	await h.byId(`${base}ctl0`).click();
	await h.assertText(`${base}InPlaceTextBox__label`, 'Prado [Read Only]');

	await h.byId(`${base}InPlaceTextBox__label`).click();
	await h.assertNotVisible(`${base}InPlaceTextBox`);
});
