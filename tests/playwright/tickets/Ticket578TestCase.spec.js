import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket578TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	await h.url('tickets/index.php?page=Ticket578');
	await h.assertTitle('Verifying Ticket 578');

	await h.assertText(`${base}label1`, 'Label 1');
	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}label1`, 'Button 1 was clicked :');

	const text = 'helloworld';

	await h.executeScript(
		'tinyMCE.get(arguments[0]).setContent(arguments[1])',
		[`${base}text1`, text]
	);

	await h.byId(`${base}button1`).click();
	await h.assertText(`${base}label1`, `Button 1 was clicked : <p>${text}</p>`);
});
