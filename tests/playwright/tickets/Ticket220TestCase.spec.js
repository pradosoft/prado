import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket220TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket220');
	await h.assertSourceContains('ClientScript Test');
	await h.assertText(`${base}label1`, 'Label 1');

	await h.byId('button1').click();
	await h.assertText(`${base}label1`, 'Label 1: ok; ok 3?; ok 2!');
	await h.assertAlertNotPresent();
});
