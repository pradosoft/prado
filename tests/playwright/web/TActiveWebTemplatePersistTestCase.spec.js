import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ActiveWebTemplatePersistTest';

/**
 * Emergent behavior: an instance stamped entirely from the SERVER during a
 * callback (TActiveWebTemplate.stampInto) is persisted too. The client wrapper
 * updates the persist hidden field as a side effect of the callback-driven
 * stamp, so the server-created instance survives a later full-page postback —
 * even though the server never re-issued the stamp.
 */
test('TActiveWebTemplatePersistTestCase: server-stamped instance survives a postback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Active Web Template Persist Test Case');
	await expect(page.locator('#listBody .row')).toHaveCount(0);

	// Stamp via a server callback — the client issues no appendTo of its own
	await page.locator('#ctl0_Content_btnServerStamp').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .name')).toHaveText('Server');

	const uidBefore = await page
		.locator('#listBody .row')
		.getAttribute('data-prado-instance');
	const renderTimeBefore = await page.locator('#ctl0_Content_renderTime').textContent();

	// The callback-driven stamp must have written itself into the persist field
	const fieldAfterStamp = await page
		.locator('#ctl0_Content_rowTemplate_instances')
		.inputValue();
	expect(fieldAfterStamp).toContain('"name":"Server"');

	// Full-page postback (a plain TButton, unrelated to the template)
	await page.locator('#ctl0_Content_btnPostBack').click();
	await page.waitForLoadState('domcontentloaded');

	// The page genuinely re-rendered...
	await expect(page.locator('#ctl0_Content_renderTime')).not.toHaveText(renderTimeBefore);

	// ...and the server-stamped instance came back, same data and UID, without
	// the server re-issuing the stamp
	await expect(page.locator('#listBody .name')).toHaveText('Server');
	expect(await page.locator('#listBody .row').getAttribute('data-prado-instance')).toBe(uidBefore);
});
