import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket659TestCase', async ({ page }) => {
	const h = genericHelper(page);
	const base = 'ctl0_Content_';
	// Normal component (working)
	await h.url('tickets/index.php?page=ToggleTest');
	await h.assertText(`${base}lbl`, 'Down');
	await h.byId(`${base}btn`).click();
	await h.assertText(`${base}lbl`, 'Up');
	// Extended component (not working)
	await h.url('tickets/index.php?page=Ticket659');
	await h.assertText(`${base}lbl`, 'Down');
	await h.byId(`${base}btn`).click();
	await h.assertText(`${base}lbl`, 'Up');
});
