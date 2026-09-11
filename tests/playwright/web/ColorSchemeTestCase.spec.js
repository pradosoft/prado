import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * The shipped control stylesheets follow the color-scheme the application
 * declares, through light-dark() for designed colors and through the Canvas and
 * CanvasText system colors for chrome that tracks the page.
 *
 * These tests assert the contract rather than the palette. No expected color is
 * written down, so changing a design value does not break the suite, while the
 * properties that must hold stay guarded:
 *
 *   declaring nothing          renders identically to declaring light
 *   a dark OS alone            changes nothing, whatever the OS prefers
 *   declaring dark             changes every color the stylesheets adapt
 *   declaring light dark        follows the OS
 *   a container declaring dark  darkens that subtree only
 *   either scheme              meets the WCAG contrast minimums
 *
 * Both schemes are held to the same minimums. They are free to reach them with
 * different ratios, and they do: each scheme picks the colors that suit its own
 * backgrounds.
 *
 * Text, borders and the hovered and pressed states are all measured. The tab
 * focus outline is not measured directly, because :focus-visible depends on
 * browser heuristics that programmatic focus does not reliably satisfy; it is
 * declared with the same accent as the tab borders, which are measured here.
 */

const PAGE_URL = 'web/index.php?page=ColorSchemeTest';

// WCAG 2.1 minimums: 1.4.3 for text, 1.4.11 for borders and other non-text.
const WCAG_TEXT = 4.5;
const WCAG_NONTEXT = 3.0;

// Every color the shipped stylesheets resolve per scheme, as selector@property.
// Structural, not a palette: these are the declarations under test.
const ADAPTING = [
	['.tab-normal', 'background-color'],
	['.tab-normal', 'color'],
	['.tab-active', 'background-color'],
	['.tab-active', 'color'],
	['.tab-normal', 'border-top-color'],
	['.tab-active', 'border-top-color'],
	['.tab-view', 'border-top-color'],
	['div.accordion-header', 'background-color'],
	['div.accordion-header-active', 'background-color'],
	['div.accordion-header-active', 'color'],
	['div.accordion-header-active', 'border-top-color'],
	['div.Keyboard', 'background-color'],
	['div.Keyboard div.Key1', 'background-color'],
	['div.Keyboard div.Key1', 'color'],
	['div.Keyboard div.Key1', 'border-top-color'],
	['.Slider', 'background-color'],
	['.Track', 'background-color'],
];

// Foreground against a background, with the minimum each one owes. The fourth
// entry names the element holding the background when it is not the same element:
// .tab-view draws its border straight onto the page, so it is measured against the
// canvas that .tab-active paints.
const CONTRAST = [
	['.tab-normal', 'color', WCAG_TEXT],
	['.tab-active', 'color', WCAG_TEXT],
	['.tab-normal', 'border-top-color', WCAG_NONTEXT],
	['.tab-active', 'border-top-color', WCAG_NONTEXT],
	['.tab-view', 'border-top-color', WCAG_NONTEXT, '.tab-active'],
	['div.accordion-header', 'color', WCAG_TEXT],
	['div.accordion-header-active', 'color', WCAG_TEXT],
	['div.accordion-header-active', 'border-top-color', WCAG_NONTEXT],
	['div.Keyboard div.Key1', 'color', WCAG_TEXT],
	['div.Keyboard div.Key1', 'border-top-color', WCAG_NONTEXT],
];

// Read many computed values in one round trip, keyed selector@property.
const read = (page, pairs) =>
	page.evaluate(
		(list) =>
			Object.fromEntries(
				list.map(([selector, prop]) => {
					const el = document.querySelector(selector);
					if (!el) {
						throw new Error(`no element for ${selector}`);
					}
					return [`${selector}@${prop}`, getComputedStyle(el).getPropertyValue(prop)];
				}),
			),
		pairs,
	);

// Declare a color-scheme on the root the way an application's own CSS would.
// An empty value declares nothing, which is what an existing application has.
const declareScheme = (page, value) =>
	page.evaluate((v) => {
		document.documentElement.style.colorScheme = v;
	}, value);

function channels(value) {
	const parts = value.match(/[\d.]+/g);
	if (!parts || parts.length < 3) {
		throw new Error(`cannot parse color ${value}`);
	}
	return parts.slice(0, 3).map(Number);
}

// WCAG relative luminance, 0 (black) to 1 (white).
function luminance(value) {
	const [r, g, b] = channels(value);
	const channel = (c) => {
		const s = c / 255;
		return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
	};
	return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
}

// WCAG contrast ratio between two computed colors, 1 to 21.
function contrast(a, b) {
	const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
	return (hi + 0.05) / (lo + 0.05);
}

// Every contrast pair in the scheme currently in effect, as {label: ratio}.
async function ratios(page, pairs = CONTRAST) {
	const needed = pairs.flatMap(([selector, prop, , bgSelector = selector]) => [
		[selector, prop],
		[bgSelector, 'background-color'],
	]);
	const values = await read(page, needed);
	const out = {};
	for (const [selector, prop, , bgSelector = selector] of pairs) {
		out[`${selector}@${prop}`] = contrast(
			values[`${selector}@${prop}`],
			values[`${bgSelector}@background-color`],
		);
	}
	return out;
}

// Assert each pair clears its minimum, naming the offender, scheme and ratio.
function expectContrast(measured, pairs, scheme) {
	const failures = pairs
		.filter(([selector, prop, min]) => measured[`${selector}@${prop}`] < min)
		.map(
			([selector, prop, min]) =>
				`${scheme}: ${selector} ${prop}: ${measured[`${selector}@${prop}`].toFixed(2)} < ${min}`,
		);
	expect(failures, 'contrast below the WCAG minimum').toEqual([]);
}

// Measure the same pairs under each declared scheme in turn.
async function expectContrastBothSchemes(page, pairs) {
	for (const scheme of ['light', 'dark']) {
		await declareScheme(page, scheme);
		expectContrast(await ratios(page, pairs), pairs, scheme);
	}
}

test('ColorSchemeTestCase — declaring nothing renders the same as declaring light', async ({
	page,
}) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Color Scheme Test Case');

	const undeclared = await read(page, ADAPTING);
	await declareScheme(page, 'light');
	expect(await read(page, ADAPTING)).toEqual(undeclared);
});

test('ColorSchemeTestCase — a dark OS alone changes nothing', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// The backward-compatibility guard. An application that has never declared a
	// color-scheme must render the same for a visitor whose OS prefers dark.
	const light = await read(page, ADAPTING);
	await page.emulateMedia({ colorScheme: 'dark' });
	expect(await read(page, ADAPTING)).toEqual(light);
});

test('ColorSchemeTestCase — declaring dark changes every adapting color', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await declareScheme(page, 'light');
	const light = await read(page, ADAPTING);
	await declareScheme(page, 'dark');
	const dark = await read(page, ADAPTING);

	// Each declaration under test must actually resolve differently.
	const unchanged = Object.keys(light).filter((key) => light[key] === dark[key]);
	expect(unchanged, 'colors that failed to adapt to the dark scheme').toEqual([]);

	// Every adapting background darkens. Text and borders carry no such invariant:
	// the active accordion bar is dark in both schemes, so its text is lighter in
	// the light scheme, where the bar behind it is lighter.
	for (const [key, value] of Object.entries(dark)) {
		if (key.endsWith('@background-color')) {
			expect(luminance(value), `${key} should darken`).toBeLessThan(luminance(light[key]));
		}
	}

	// Declaring light again restores the original rendering.
	await declareScheme(page, 'light');
	expect(await read(page, ADAPTING)).toEqual(light);
});

test('ColorSchemeTestCase — declaring "light dark" follows the OS', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await declareScheme(page, 'light');
	const light = await read(page, ADAPTING);
	await declareScheme(page, 'dark');
	const dark = await read(page, ADAPTING);

	await declareScheme(page, 'light dark');
	await page.emulateMedia({ colorScheme: 'dark' });
	expect(await read(page, ADAPTING)).toEqual(dark);

	await page.emulateMedia({ colorScheme: 'light' });
	expect(await read(page, ADAPTING)).toEqual(light);
});

test('ColorSchemeTestCase — a dark container darkens only its own subtree', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// #darkSubtree declares dark on itself while the root declares nothing.
	const [inside, outside] = await page.evaluate(() => [
		getComputedStyle(document.querySelector('#darkSubtree .tab-normal')).backgroundColor,
		getComputedStyle(document.querySelector('.tab-normal')).backgroundColor,
	]);
	expect(inside).not.toBe(outside);

	// The container resolves to the same color the whole page would under dark,
	// and the panel outside it is untouched by the container.
	await declareScheme(page, 'dark');
	const darkTab = await page.evaluate(
		() => getComputedStyle(document.querySelector('.tab-normal')).backgroundColor,
	);
	expect(inside).toBe(darkTab);

	await declareScheme(page, 'light');
	const lightTab = await page.evaluate(
		() => getComputedStyle(document.querySelector('.tab-normal')).backgroundColor,
	);
	expect(outside).toBe(lightTab);
});

test('ColorSchemeTestCase — both schemes meet the WCAG contrast minimums', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// Resting state first: hovering would recolor pairs measured here.
	await expectContrastBothSchemes(page, CONTRAST);

	// The hovered key recolors its text and border over the same key face.
	await page.evaluate(() =>
		document.querySelector('div.Keyboard div.Key1').classList.add('Hover'),
	);
	await expectContrastBothSchemes(page, [
		['div.Keyboard div.Key1', 'color', WCAG_TEXT],
		['div.Keyboard div.Key1', 'border-top-color', WCAG_NONTEXT],
	]);

	// The pressed key paints white on its own red, one value for both schemes. The
	// chip also has to stay clear of an unpressed key face, which does adapt, so
	// the pressed state stays visible as a state and not only as a color.
	const keyBg = 'div.Keyboard div.Key1@background-color';
	const keyFg = 'div.Keyboard div.Key1@color';
	const setKeyClass = (page, action) =>
		page.evaluate(
			(a) => document.querySelector('div.Keyboard div.Key1').classList[a]('Active'),
			action,
		);

	await page.evaluate(() =>
		document.querySelector('div.Keyboard div.Key1').classList.remove('Hover'),
	);
	for (const scheme of ['light', 'dark']) {
		await declareScheme(page, scheme);
		const resting = await read(page, [['div.Keyboard div.Key1', 'background-color']]);

		await setKeyClass(page, 'add');
		const pressed = await read(page, [
			['div.Keyboard div.Key1', 'background-color'],
			['div.Keyboard div.Key1', 'color'],
		]);
		await setKeyClass(page, 'remove');

		expect(
			contrast(pressed[keyFg], pressed[keyBg]),
			`${scheme}: pressed key text on its chip`,
		).toBeGreaterThanOrEqual(WCAG_TEXT);
		expect(
			contrast(pressed[keyBg], resting[keyBg]),
			`${scheme}: pressed chip against an unpressed key face`,
		).toBeGreaterThanOrEqual(WCAG_NONTEXT);
	}

	// The hovered inactive tab recolors its text over the same tab background.
	// The pointer stays put while the scheme changes, so :hover holds.
	await page.locator('.tab-normal').first().hover();
	await expectContrastBothSchemes(page, [['.tab-normal', 'color', WCAG_TEXT]]);
});

// Both popup panels are built by script on first open and are positioned over the
// page, so each gets its own fresh page. Opening both at once leaves the first
// panel covering the second trigger.
const panelCases = [
	['the date picker panel', '.TDatePickerImageButton', '.TDatePicker_default'],
	['the color picker panel', '.TColorPicker_button', '.TColorPicker'],
];

for (const [label, trigger, panel] of panelCases) {
	test(`ColorSchemeTestCase — ${label} adapts and stays readable`, async ({ page }) => {
		const h = new PradoTestHelper(page, GENERIC_BASE_URL);
		await h.url(PAGE_URL);

		await page.locator(trigger).first().click();
		await expect(page.locator(panel).first()).toBeVisible();

		// The panel background tracks the page canvas and its border is designed,
		// so both move between the schemes.
		const props = [
			[panel, 'background-color'],
			[panel, 'border-top-color'],
		];
		await declareScheme(page, 'light');
		const light = await read(page, props);
		await declareScheme(page, 'dark');
		const dark = await read(page, props);

		expect(Object.keys(light).filter((k) => light[k] === dark[k])).toEqual([]);
		expect(luminance(dark[`${panel}@background-color`])).toBeLessThan(
			luminance(light[`${panel}@background-color`]),
		);

		// Panel text and panel border stay legible on the panel in either scheme.
		await expectContrastBothSchemes(page, [
			[panel, 'color', WCAG_TEXT],
			[panel, 'border-top-color', WCAG_NONTEXT],
		]);
	});
}
