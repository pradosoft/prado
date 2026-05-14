import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Issue120TestCase', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Issue120');
	await h.assertSourceContains('TActiveDropDownList PromptValue Test');

	await h.assertSelectedIndex('ctl0_Content_ddl1', 0);
	await h.assertSelectedValue('ctl0_Content_ddl1', 'PromptValue');

	await h.byId('ctl0_Content_btn1').click();

	await h.assertSelectedIndex('ctl0_Content_ddl1', 0);
	await h.assertSelectedValue('ctl0_Content_ddl1', 'PromptValue');
});
