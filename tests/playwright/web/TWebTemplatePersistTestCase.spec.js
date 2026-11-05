import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=WebTemplatePersistTest';

/**
 * With PersistInstances enabled, client-stamped instances are serialized into a
 * hidden field, round-trip a full-page postback through the post data, and are
 * restored — same UIDs, same data — after the page re-renders.
 */
test('TWebTemplatePersistTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Web Template Persist Test Case');

	// Stamp two instances from the client, then update the first in place, so
	// the postback must restore the UPDATED data rather than the stamped data
	await page.locator('#btnStampAda').click();
	await page.locator('#btnStampGrace').click();
	await expect(page.locator('#listBody .name')).toHaveText(['Ada', 'Grace']);
	await page.locator('#btnUpdateFirst').click();
	await expect(page.locator('#listBody .name')).toHaveText(['Updated', 'Grace']);

	const uidsBefore = await page
		.locator('#listBody [data-prado-instance]')
		.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('data-prado-instance')));
	const renderTimeBefore = await page.locator('#ctl0_Content_renderTime').textContent();

	// Full-page postback
	await page.locator('#ctl0_Content_btnPostBack').click();
	await page.waitForLoadState('domcontentloaded');

	// The page genuinely re-rendered...
	await expect(page.locator('#ctl0_Content_renderTime')).not.toHaveText(renderTimeBefore);

	// ...and the instances came back with their UIDs and the UPDATED data
	await expect(page.locator('#listBody .name')).toHaveText(['Updated', 'Grace']);
	const uidsAfter = await page
		.locator('#listBody [data-prado-instance]')
		.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('data-prado-instance')));
	expect(uidsAfter).toEqual(uidsBefore);

	// Restored instances are live: a fresh stamp gets a UID past the restored ones
	await page.locator('#btnStampAda').click();
	await expect(page.locator('#listBody .name')).toHaveText(['Updated', 'Grace', 'Ada']);
	const allUids = await page
		.locator('#listBody [data-prado-instance]')
		.evaluateAll((nodes) => nodes.map((n) => n.getAttribute('data-prado-instance')));
	expect(new Set(allUids).size).toBe(3);

	// A second postback keeps all three
	await page.locator('#ctl0_Content_btnPostBack').click();
	await page.waitForLoadState('domcontentloaded');
	await expect(page.locator('#listBody .name')).toHaveText(['Updated', 'Grace', 'Ada']);
});
