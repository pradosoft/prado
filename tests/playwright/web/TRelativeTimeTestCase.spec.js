import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=RelativeTimeTest';

const ID = {
	past: 'ctl0_Content_rtPast',
	future: 'ctl0_Content_rtFuture',
	short: 'ctl0_Content_rtShort',
	narrow: 'ctl0_Content_rtNarrow',
	multi: 'ctl0_Content_rtMulti',
	sep: 'ctl0_Content_rtSep',
	seconds: 'ctl0_Content_rtSeconds',
	french: 'ctl0_Content_rtFrench',
	toggle: 'ctl0_Content_rtToggle',
	noClick: 'ctl0_Content_rtNoClick',
	duration: 'ctl0_Content_rtDuration',
	noJs: 'ctl0_Content_rtNoJs'
};

/**
 * TRelativeTime renders a <time> element with a machine-readable datetime attribute and a
 * localized absolute date as the no-JavaScript fallback. Client script replaces the text
 * with a live, localized relative time and, when enabled, toggles to the absolute date on
 * click. DateTimes are anchored to the server clock by the harness page.
 */

test('TRelativeTimeTestCase: server-rendered <time> markup and datetime attribute', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Relative Time Test Case');

	// Semantic element with an id (for the client script), a valid HTML5 datetime, and
	// the absolute date as the default tooltip.
	await expect(page.locator(`#${ID.past}`)).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
	await expect(page.locator(`#${ID.past}`)).toHaveAttribute('title', /\d{4}/);
	await expect(page.locator(`#${ID.past}`)).toHaveJSProperty('tagName', 'TIME');
});

test('TRelativeTimeTestCase: past time renders localized relative text', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.past}`)).toHaveText('5 minutes ago');
});

test('TRelativeTimeTestCase: future time renders "in" direction', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.future}`)).toHaveText('in 5 minutes');
});

test('TRelativeTimeTestCase: Short and Narrow modes abbreviate units', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.short}`)).toHaveText('5 min. ago');
	await expect(page.locator(`#${ID.narrow}`)).toHaveText('5m ago');
});

test('TRelativeTimeTestCase: multiple significant elements with a single direction', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.multi}`)).toHaveText('1 hour 5 minutes ago');
});

test('TRelativeTimeTestCase: custom separator between units', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.sep}`)).toHaveText('1 hour, 5 minutes ago');
});

test('TRelativeTimeTestCase: culture drives the localized text', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect(page.locator(`#${ID.french}`)).toHaveText('il y a 5 minutes');
});

test('TRelativeTimeTestCase: seconds update live', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	const loc = page.locator(`#${ID.seconds}`);
	await expect(loc).toHaveText(/\d+ seconds ago/);
	const first = await loc.textContent();
	await page.waitForTimeout(2500);
	const second = await loc.textContent();
	expect(second).not.toBe(first);
});

test('TRelativeTimeTestCase: click toggles to the absolute date and back', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	const loc = page.locator(`#${ID.toggle}`);
	await expect(loc).toHaveText('5 minutes ago');

	// The toggle control opts into a pointer cursor.
	await expect(loc).toHaveCSS('cursor', 'pointer');

	const year = String(new Date().getFullYear());
	await loc.click();
	await expect(loc).toContainText(year);
	await expect(loc).not.toContainText('ago');

	await loc.click();
	await expect(loc).toHaveText('5 minutes ago');
});

test('TRelativeTimeTestCase: ClickForDateTime=false does not toggle', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	const loc = page.locator(`#${ID.noClick}`);
	await expect(loc).toHaveText('5 minutes ago');
	await expect(loc).not.toHaveCSS('cursor', 'pointer');
	await loc.click();
	await expect(loc).toHaveText('5 minutes ago');
});

test('TRelativeTimeTestCase: DurationOnly renders the bare duration', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	const loc = page.locator(`#${ID.duration}`);
	await expect(loc).toHaveText('5 minutes');
	await expect(loc).not.toContainText('ago');
});

test.describe('without JavaScript', () => {
	test.use({ javaScriptEnabled: false });

	test('TRelativeTimeTestCase: server renders the relative text and absolute tooltip', async ({ page }) => {
		const h = new PradoTestHelper(page, GENERIC_BASE_URL);
		await h.url(PAGE_URL);
		const loc = page.locator(`#${ID.noJs}`);

		// The static markup already carries the relative text (a render-time snapshot),
		// a valid datetime, and the absolute date in the tooltip.
		await expect(loc).toHaveText('5 minutes ago');
		await expect(loc).toHaveAttribute('datetime', /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/);
		await expect(loc).toHaveAttribute('title', /\d{4}/);
	});
});
