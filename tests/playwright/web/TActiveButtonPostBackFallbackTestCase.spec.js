import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ActiveWebTemplateTest';

/**
 * Deliberate v4.4 design: a ClientSide hook that throws lets the submit button
 * fall back to a full-page postback, so the click does not dead-end. The error
 * is rethrown and observable before the navigation.
 *
 * Prado.WebUI.CallbackControl.onPostBack() reaches event.preventDefault() only
 * after dispatch() returns, and dispatch() runs the OnPreDispatch hook
 * synchronously, so a throwing hook leaves the button's native submit in place.
 * The unit-level counterpart is in tests/js/activecontrols/activecontrols.test.js.
 */
test('a throwing ClientSide hook falls back to a full-page postback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('#ctl0_Content_btnStamp').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .row')).toHaveCount(1);

	const errors = [];
	page.on('pageerror', (e) => errors.push(e.message));
	let navigations = 0;
	page.on('framenavigated', (f) => {
		if (f === page.mainFrame()) {
			navigations++;
		}
	});

	await page.locator('#ctl0_Content_btnThrowingHook').click();
	await page.waitForLoadState('domcontentloaded');
	await page.waitForTimeout(300);

	// The hook's error surfaced before the navigation wiped the page
	expect(errors.join(' ')).toContain('deliberate hook failure');
	// and the click degraded to a full-page postback
	expect(navigations).toBe(1);
	// which re-rendered the page, discarding the stamped copy (PersistInstances
	// is not enabled on this page)
	await expect(page.locator('#listBody .row')).toHaveCount(0);
});
