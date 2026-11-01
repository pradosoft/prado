import { test, expect } from '@playwright/test';
import { genericHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'active-controls/index.php?page=LazyPanelTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Lazy Panel Test Case');
	await expect(page.locator('#onloadContent')).toHaveText('Loaded on load');
	await h.waitForAjaxCalls();
	return h;
}

/**
 * The first response renders the placeholder busy, and none of the content.
 */
test('LazyPanelTestCase: the page renders placeholders without the content', async ({ page }) => {
	const html = await (await page.request.get(GENERIC_BASE_URL + PAGE_URL)).text();
	expect(html).toContain('Loading on load…');
	expect(html).not.toContain('Loaded on load');
	expect(html).not.toContain('Loaded when visible');
	expect(html).toMatch(new RegExp(`<div id="${B}onload" aria-busy="true">`));
});

/**
 * Trigger Load replaces the placeholder after the page loads; the region is no longer busy.
 */
test('LazyPanelTestCase: Load trigger', async ({ page }) => {
	await open(page);
	const panel = page.locator(`#${B}onload`);
	await expect(panel).not.toHaveAttribute('aria-busy');
	await expect(panel.locator('.placeholder')).toHaveCount(0);
	await expect(page.locator(`#${B}loads`)).toHaveText('1');
	// the panel is no live region: the content arrives without an announcement
	await expect(panel).not.toHaveAttribute('aria-live');
	await expect(panel).not.toHaveAttribute('role');
});

/**
 * Trigger Visible loads when the panel scrolls into the extended viewport, and not before.
 */
test('LazyPanelTestCase: Visible trigger', async ({ page }) => {
	const h = await open(page);
	const panel = page.locator(`#${B}visible`);
	await page.waitForTimeout(300);
	await expect(panel).toHaveAttribute('aria-busy', 'true');
	await expect(page.locator('#visibleContent')).toHaveCount(0);
	await expect(page.locator(`#${B}loads`)).toHaveText('1');

	await page.locator('#spacer').evaluate((el) => el.scrollIntoView({ block: 'end' }));
	await expect(page.locator('#visibleContent')).toHaveText('Loaded when visible');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${B}visible`)).not.toHaveAttribute('aria-busy');
	await expect(page.locator(`#${B}loads`)).toHaveText('2');
});

/**
 * Trigger Manual waits for Prado.WebUI.TLazyPanel.load(); focus stays where it is.
 * The button is pressed from the keyboard, since a WebKit click does not focus a button.
 */
test('LazyPanelTestCase: Manual trigger keeps focus', async ({ page }) => {
	const h = await open(page);
	await expect(page.locator(`#${B}manual`)).toHaveAttribute('aria-busy', 'true');
	await page.locator('#manualLoad').focus();
	await page.keyboard.press('Enter');
	await expect(page.locator('#manualContent')).toHaveText('Loaded manually');
	await h.waitForAjaxCalls();
	await expect(page.locator('#manualLoad')).toBeFocused();
	await expect(page.locator(`#${B}loads`)).toHaveText('2');
});

/**
 * TLazyPanel::loadContent() in another control's callback replaces the placeholder.
 */
test('LazyPanelTestCase: loadContent from another callback', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${B}serverLoad`).click();
	await expect(page.locator('#serverContent')).toHaveText('Loaded by the server');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${B}server`)).not.toHaveAttribute('aria-busy');
	await expect(page.locator(`#${B}loads`)).toHaveText('2');
});

/**
 * Loaded content persists through callbacks and postbacks without loading again;
 * its controls keep their values and raise events.
 */
test('LazyPanelTestCase: loaded content persists', async ({ page }) => {
	const h = await open(page);
	await page.locator(`#${B}onload_note`).fill('hello');
	await page.locator(`#${B}other`).click();
	await expect(page.locator(`#${B}onload_noteEcho`)).toHaveText('HELLO');
	await expect(page.locator(`#${B}callbacks`)).toHaveText('1');
	await expect(page.locator('#onloadContent')).toHaveText('Loaded on load');

	await Promise.all([page.waitForNavigation(), page.locator(`#${B}postback`).click()]);
	await expect(page.locator('#onloadContent')).toHaveText('Loaded on load');
	await expect(page.locator(`#${B}onload`)).not.toHaveAttribute('aria-busy');
	await expect(page.locator(`#${B}onload_note`)).toHaveValue('hello');
	await page.waitForTimeout(300);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${B}loads`)).toHaveText('1');
	// an unloaded panel renders its placeholder again
	await expect(page.locator(`#${B}manual`)).toHaveAttribute('aria-busy', 'true');
	await expect(page.locator(`#${B}manual .placeholder`)).toHaveCount(1);
});

/**
 * A failed load keeps the placeholder, ends aria-busy, and marks the panel; load() retries.
 */
test('LazyPanelTestCase: a failed load and a retry', async ({ page }) => {
	const h = await open(page);
	const panel = page.locator(`#${B}manual`);
	await page.locator(`#${B}fail`).check();
	await page.locator('#manualLoad').click();
	await expect(panel).toHaveAttribute('data-prado-lazy', 'failed');
	await expect(panel).not.toHaveAttribute('aria-busy');
	await expect(panel.locator('.placeholder')).toHaveCount(1);
	await h.waitForAjaxCalls();

	await page.locator(`#${B}fail`).uncheck();
	await page.locator('#manualLoad').click();
	await expect(page.locator('#manualContent')).toHaveText('Loaded manually');
	await expect(page.locator(`#${B}manual`)).not.toHaveAttribute('data-prado-lazy');
	await expect(page.locator(`#${B}loads`)).toHaveText('2');
});
