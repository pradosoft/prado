import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * The shipped control stylesheets follow the color-scheme the application
 * declares, through light-dark() for designed colors and through the Canvas and
 * CanvasText system colors for chrome that tracks the page.
 *
 * The contract under test:
 *   application declares nothing        → light, whatever the OS prefers
 *   application declares dark           → dark
 *   application declares light dark     → follows the OS
 *   a container declares dark           → only that subtree goes dark
 *
 * Author-specified colors are asserted as exact values, since every browser
 * resolves light-dark() to the same two arguments. Canvas and CanvasText are
 * asserted by luminance direction, because their values come from the browser
 * and differ between engines.
 */

const PAGE_URL = 'web/index.php?page=ColorSchemeTest';

// Computed value of one property on the first element matching a selector.
const css = (page, selector, prop) =>
	page.evaluate(
		([s, p]) => getComputedStyle(document.querySelector(s)).getPropertyValue(p),
		[selector, prop],
	);

// WCAG relative luminance of a computed rgb()/rgba() value, 0 (black) to 1 (white).
function luminance(value) {
	const [r, g, b] = value.match(/[\d.]+/g).slice(0, 3).map(Number);
	const channel = (c) => {
		const s = c / 255;
		return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
	};
	return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

// Declare a color-scheme on the root the way an application's own CSS would.
const declareScheme = (page, value) =>
	page.evaluate((v) => {
		document.documentElement.style.colorScheme = v;
	}, value);

// The light values every one of these rules carries today, so an application
// that declares no scheme keeps the rendering it already has.
const LIGHT = {
	'.tab-normal@background-color': 'rgb(234, 242, 255)',
	'.tab-normal@color': 'rgb(128, 128, 128)',
	'div.accordion-header@background-color': 'rgb(85, 119, 170)',
	'div.accordion-header-active@background-color': 'rgb(51, 68, 119)',
	'div.accordion-header-active@color': 'rgb(255, 0, 0)',
	'.Slider@background-color': 'rgb(220, 220, 220)',
	'.Track@background-color': 'rgb(173, 216, 230)',
	'div.Keyboard@background-color': 'rgb(221, 221, 221)',
	'div.Keyboard div.Key1@background-color': 'rgb(247, 247, 247)',
	'div.Keyboard div.Key1@color': 'rgb(85, 108, 95)',
};

// The dark half of each light-dark() pair. These mirror the shipped stylesheets;
// changing a palette value in the CSS means changing it here in the same commit.
const DARK = {
	'.tab-normal@background-color': 'rgb(30, 41, 59)',
	'.tab-normal@color': 'rgb(154, 166, 178)',
	'div.accordion-header@background-color': 'rgb(61, 86, 117)',
	'div.accordion-header-active@background-color': 'rgb(43, 58, 92)',
	'div.accordion-header-active@color': 'rgb(238, 0, 0)',
	'.Slider@background-color': 'rgb(46, 46, 46)',
	'.Track@background-color': 'rgb(42, 77, 94)',
	'div.Keyboard@background-color': 'rgb(42, 42, 42)',
	'div.Keyboard div.Key1@background-color': 'rgb(58, 58, 58)',
	'div.Keyboard div.Key1@color': 'rgb(200, 214, 205)',
};

// Read every probe in one pass so a mismatch reports the whole set at once.
async function probe(page) {
	const out = {};
	for (const key of Object.keys(LIGHT)) {
		const [selector, prop] = key.split('@');
		out[key] = await css(page, selector, prop);
	}
	return out;
}

test('ColorSchemeTestCase — application declaring nothing renders light', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Color Scheme Test Case');

	expect(await probe(page)).toEqual(LIGHT);

	// The active tab tracks the page canvas, which is light here.
	expect(luminance(await css(page, '.tab-active', 'background-color'))).toBeGreaterThan(0.5);
	expect(luminance(await css(page, '.tab-active', 'color'))).toBeLessThan(0.5);
});

test('ColorSchemeTestCase — a dark OS alone changes nothing', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await page.emulateMedia({ colorScheme: 'dark' });
	await h.url(PAGE_URL);

	// This is the backward-compatibility guard. An application that has never
	// declared a color-scheme must look exactly as it did, even for a visitor
	// whose operating system prefers dark.
	expect(await probe(page)).toEqual(LIGHT);
});

test('ColorSchemeTestCase — application declaring dark goes dark', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await declareScheme(page, 'dark');
	expect(await probe(page)).toEqual(DARK);

	// The active tab follows the canvas into dark, and its text inverts with it.
	expect(luminance(await css(page, '.tab-active', 'background-color'))).toBeLessThan(0.2);
	expect(luminance(await css(page, '.tab-active', 'color'))).toBeGreaterThan(0.5);

	// Declaring light again restores the original rendering.
	await declareScheme(page, 'light');
	expect(await probe(page)).toEqual(LIGHT);
});

test('ColorSchemeTestCase — application declaring "light dark" follows the OS', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await page.emulateMedia({ colorScheme: 'dark' });
	await h.url(PAGE_URL);

	await declareScheme(page, 'light dark');
	expect(await probe(page)).toEqual(DARK);

	await page.emulateMedia({ colorScheme: 'light' });
	expect(await probe(page)).toEqual(LIGHT);
});

test('ColorSchemeTestCase — a dark container darkens only its own subtree', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// #darkSubtree declares dark on itself while the root declares nothing.
	const inside = await page.evaluate(
		() => getComputedStyle(document.querySelector('#darkSubtree .tab-normal')).backgroundColor,
	);
	expect(inside).toBe(DARK['.tab-normal@background-color']);

	// The tab panel outside the container is untouched.
	expect(await css(page, '.tab-normal', 'background-color')).toBe(
		LIGHT['.tab-normal@background-color'],
	);
});

// Both popup panels are built by script on first open and are positioned over
// the page, so each gets its own fresh page. Opening both at once leaves the
// first panel covering the second trigger.
const PANEL_BORDER_LIGHT = 'rgb(145, 158, 169)';
const PANEL_BORDER_DARK = 'rgb(100, 110, 120)';

test('ColorSchemeTestCase — the date picker panel adapts', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('.TDatePickerImageButton').first().click();
	await expect(page.locator('.TDatePicker_default').first()).toBeVisible();

	expect(await css(page, '.TDatePicker_default', 'border-top-color')).toBe(PANEL_BORDER_LIGHT);
	// The calendar sits on the page canvas, which is light here.
	expect(luminance(await css(page, '.TDatePicker_default', 'background-color'))).toBeGreaterThan(0.5);

	await declareScheme(page, 'dark');

	expect(await css(page, '.TDatePicker_default', 'border-top-color')).toBe(PANEL_BORDER_DARK);
	expect(luminance(await css(page, '.TDatePicker_default', 'background-color'))).toBeLessThan(0.2);
});

test('ColorSchemeTestCase — the color picker panel adapts', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.locator('.TColorPicker_button').first().click();
	await expect(page.locator('.TColorPicker').first()).toBeVisible();

	expect(await css(page, '.TColorPicker', 'border-top-color')).toBe(PANEL_BORDER_LIGHT);
	expect(luminance(await css(page, '.TColorPicker', 'background-color'))).toBeGreaterThan(0.5);

	await declareScheme(page, 'dark');

	expect(await css(page, '.TColorPicker', 'border-top-color')).toBe(PANEL_BORDER_DARK);
	expect(luminance(await css(page, '.TColorPicker', 'background-color'))).toBeLessThan(0.2);
});
