import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue216TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Issue216');
	await h.assertSourceContains("TTabPanel doesn't preserve active tab on callback request");

	await h.assertVisible('ctl0_Content_tab1');

	await h.byId('ctl0_Content_btn1').click();

	await h.assertText('ctl0_Content_result', 'Tab ActiveIndex is : 0');

	await h.byId('ctl0_Content_tab2_0').click();

	await h.assertVisible('ctl0_Content_tab2');

	await h.byId('ctl0_Content_btn1').click();
	await h.assertText('ctl0_Content_result', 'Tab ActiveIndex is : 1');
});
