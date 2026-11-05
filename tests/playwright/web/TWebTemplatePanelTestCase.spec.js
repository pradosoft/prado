import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=WebTemplatePanelTest';
const TPL_A = 'ctl0_Content_tplA';
const INNER_BUTTON = 'ctl0_Content_innerButton';

/**
 * Case A — a TWebTemplate nested inside a TActivePanel.
 *
 * Re-rendering the panel during a callback replaces the <template> element.
 * The new server markup lands in the new element's content fragment, and the
 * callback's end script re-registers the client wrapper against it, so later
 * stamping uses the updated content.
 */
test('TWebTemplatePanelTestCase: template inside an active panel', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Web Template Panel Test Case');

	const templateContent = () =>
		page.evaluate((id) => document.getElementById(id).content.firstElementChild.outerHTML, TPL_A);
	const wrapperBound = () =>
		page.evaluate((id) => Prado.Registry[id].element === document.getElementById(id), TPL_A);

	// First generation of the template content
	expect(await templateContent()).toContain('gen-1');
	expect(await wrapperBound()).toBe(true);

	await page.locator('#btnStampA').click();
	await expect(page.locator('#targetA .name')).toHaveText('Ada');
	await expect(page.locator('#targetA .gen')).toHaveText('gen-1');

	// The callback re-renders the panel, replacing the template element
	await page.locator('#ctl0_Content_btnRefreshA').click();
	await h.waitForAjaxCalls();
	await expect.poll(templateContent).toContain('gen-2');

	// The wrapper is re-registered and bound to the replacement element
	expect(await wrapperBound()).toBe(true);

	// Already-stamped output is untouched by the re-render
	await expect(page.locator('#targetA .gen')).toHaveText('gen-1');

	// Stamping again uses the new content
	await page.locator('#btnStampA').click();
	await expect(page.locator('#targetA .gen')).toHaveText('gen-2');
});

/**
 * Case B — an active control (TActiveButton) inside TWebTemplate content.
 *
 * The control's client wrapper initializes at page load, when the element is
 * still inside the inert fragment, so it binds to null and never re-binds to a
 * stamped copy. The stamped copy keeps its submit name, so activating it
 * performs a full-page postback instead of an AJAX callback.
 */
test('TWebTemplatePanelTestCase: active control inside template content', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// Inert: the control is registered but its element is not in the document
	expect(await page.evaluate((id) => !!document.getElementById(id), INNER_BUTTON)).toBe(false);
	expect(await page.evaluate((id) => Prado.Registry[id].element, INNER_BUTTON)).toBeNull();

	await page.locator('#btnStampB').click();
	await expect(page.locator(`#targetB #${INNER_BUTTON}`)).toHaveCount(1);

	// The stamped copy is in the document, but the wrapper still holds null
	expect(await page.evaluate((id) => Prado.Registry[id].element, INNER_BUTTON)).toBeNull();

	// Activating the copy navigates (full postback) rather than making a callback
	let navigations = 0;
	page.on('framenavigated', (f) => {
		if (f === page.mainFrame()) {
			navigations++;
		}
	});
	await page.locator(`#targetB #${INNER_BUTTON}`).click();
	await page.waitForLoadState('domcontentloaded');
	await expect(page.locator('#ctl0_Content_innerStatus')).toHaveText('inner callback fired');
	expect(navigations).toBe(1);

	// The full postback discarded every stamped node
	await expect(page.locator(`#targetB #${INNER_BUTTON}`)).toHaveCount(0);
});
