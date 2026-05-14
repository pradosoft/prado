import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

test('Ticket504TestCase', async ({ page }) => {
	const base = 'ctl0_Content_';
	const h = genericHelper(page);
	await h.url('tickets/index.php?page=Ticket504');
	await h.assertTitle('Verifying Ticket 504');

	await h.assertText('status', '');

	await h.assertVisible(`${base}panelA`);
	await h.assertVisible(`${base}panelB`);
	await h.assertVisible(`${base}panelC`);
	await h.assertVisible(`${base}panelD`);

	await h.byId(`${base}linka`).click();
	await h.assertVisible(`${base}panelA`);
	await h.assertNotVisible(`${base}panelB`);
	await h.assertNotVisible(`${base}panelC`);
	await h.assertNotVisible(`${base}panelD`);
	await h.assertText('status', 'panelA updated');

	await h.byId(`${base}linkb`).click();
	await h.assertNotVisible(`${base}panelA`);
	await h.assertVisible(`${base}panelB`);
	await h.assertNotVisible(`${base}panelC`);
	await h.assertNotVisible(`${base}panelD`);
	await h.assertText('status', 'panelB updated');

	await h.byId(`${base}linkc`).click();
	await h.assertNotVisible(`${base}panelA`);
	await h.assertNotVisible(`${base}panelB`);
	await h.assertVisible(`${base}panelC`);
	await h.assertNotVisible(`${base}panelD`);
	await h.assertText('status', 'panelC updated');

	await h.byId(`${base}linkd`).click();
	await h.assertNotVisible(`${base}panelA`);
	await h.assertNotVisible(`${base}panelB`);
	await h.assertNotVisible(`${base}panelC`);
	await h.assertVisible(`${base}panelD`);
	await h.assertText('status', 'panelD updated');
});
