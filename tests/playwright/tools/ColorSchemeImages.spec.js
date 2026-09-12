import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * Generates the light/dark documentation images for the quickstart.
 *
 * This is a generator, not a test: it is skipped unless
 * PRADO_COLOR_SCHEME_IMAGES is set, so a normal run and CI never write files.
 *
 *   PRADO_COLOR_SCHEME_IMAGES=1 npx playwright test --project=chromium \
 *     tests/playwright/tools/ColorSchemeImages.spec.js
 *
 * Each image holds one control twice, light on the left and dark on the right,
 * captured from the same markup with only the root color-scheme changed. Hovered
 * and focused states are forced through the Chrome DevTools Protocol, because a
 * real pointer can only hover one of the two halves at a time.
 */

const OUT_DIR = resolve(process.cwd(), '..', 'prado-demos', 'local', 'v4.4', 'color-scheme-awareness');
const GALLERY = 'web/index.php?page=ColorSchemeGalleryTest';
const SCAFFOLD = 'web/index.php?page=ColorSchemeScaffoldTest';
const SCHEMES = ['light', 'dark'];

// 2x output so the images stay sharp in the quickstart.
test.use({ deviceScaleFactor: 2, viewport: { width: 1400, height: 1400 } });

const declareScheme = (page, value) =>
	page.evaluate((v) => {
		document.documentElement.style.colorScheme = v;
	}, value);

/**
 * Opens a CDP session and returns a forcePseudo() bound to one document.
 *
 * DOM.getDocument resets the protocol's node map and invalidates every nodeId
 * handed out before it, which silently drops any state already forced. So the
 * document is fetched once and its root reused for every query.
 */
async function pseudoForcer(context, page) {
	const cdp = await context.newCDPSession(page);
	await cdp.send('DOM.enable');
	await cdp.send('CSS.enable');
	const { root } = await cdp.send('DOM.getDocument');

	return async function forcePseudo(selector, index, forcedPseudoClasses) {
		const { nodeIds } = await cdp.send('DOM.querySelectorAll', {
			nodeId: root.nodeId,
			selector,
		});
		expect(nodeIds.length, `no match ${index} for ${selector}`).toBeGreaterThan(index);
		await cdp.send('CSS.forcePseudoState', {
			nodeId: nodeIds[index],
			forcedPseudoClasses,
		});
	};
}

/** Put the two captures side by side under a title, and return the PNG. */
async function compose(page, title, shots) {
	const sides = SCHEMES.map(
		(scheme) => `
			<div class="side">
				<div class="label">${scheme === 'light' ? 'Light' : 'Dark'}</div>
				<img src="data:image/png;base64,${shots[scheme].toString('base64')}" />
			</div>`,
	).join('');

	await page.setContent(`
		<style>
			body { margin: 0; background: #6f767d; }
			#pair { display: inline-block; padding: 10px 12px 12px; }
			#title {
				font: 600 13px/1.8 -apple-system, "Segoe UI", Arial, sans-serif;
				color: #fff; padding-left: 2px;
			}
			.row { display: flex; gap: 12px; align-items: flex-start; }
			.label {
				font: 600 11px/1.9 -apple-system, "Segoe UI", Arial, sans-serif;
				color: #e8eaed; text-transform: uppercase; letter-spacing: .06em;
			}
			img { display: block; }
		</style>
		<div id="pair">
			<div id="title">${title}</div>
			<div class="row">${sides}</div>
		</div>
	`);
	await page.waitForFunction(() => Array.from(document.images).every((i) => i.complete));
	// The captures are 2x; render them at logical size so the composite is 2x too.
	await page.evaluate(() => {
		for (const img of document.images) {
			img.style.width = `${img.naturalWidth / 2}px`;
		}
	});
	return page.locator('#pair').screenshot();
}

/** Capture every target in both schemes, then write one composite per target. */
async function captureGroup(page, composer, targets) {
	const shots = {};
	for (const scheme of SCHEMES) {
		await declareScheme(page, scheme);
		for (const [name, , selector] of targets) {
			shots[name] = shots[name] || {};
			shots[name][scheme] = await page.locator(selector).first().screenshot();
		}
	}
	for (const [name, title] of targets) {
		const png = await compose(composer, title, shots[name]);
		writeFileSync(resolve(OUT_DIR, `${name}.png`), png);
		console.log(`wrote ${name}.png`);
	}
}

test.describe('color-scheme documentation images', () => {
	test.skip(
		!process.env.PRADO_COLOR_SCHEME_IMAGES,
		'set PRADO_COLOR_SCHEME_IMAGES=1 to regenerate the quickstart images',
	);

	test('generate', async ({ page, context }) => {
		mkdirSync(OUT_DIR, { recursive: true });
		const h = new PradoTestHelper(page, GENERIC_BASE_URL);
		const composer = await context.newPage();

		// ── Gallery page ──────────────────────────────────────────────────────
		await h.url(GALLERY);
		await h.assertSourceContains('Color Scheme Gallery');
		const forcePseudo = await pseudoForcer(context, page);

		// Tabs read left to right as active, normal, hovered, focused, mouse down.
		await forcePseudo('#shot-tabpanel .tab-normal', 1, ['hover']);
		await forcePseudo('#shot-tabpanel .tab-normal', 2, ['focus', 'focus-visible']);
		await forcePseudo('#shot-tabpanel .tab-normal', 3, ['hover', 'active']);

		// Keyboard: one key hovered, one key pressed.
		await page.evaluate(() => {
			const keys = document.querySelectorAll('div.Keyboard div.Key div.Key1');
			keys[2].classList.add('Hover');
			keys[4].classList.add('Active');
		});

		// A forced state that silently failed would ship a misleading image, so
		// check each one changed something before anything is captured.
		const tabState = await page.evaluate(() => {
			const tabs = [...document.querySelectorAll('#shot-tabpanel .tab-normal')];
			const sheen = (t) => getComputedStyle(t).backgroundImage;
			return {
				plain: getComputedStyle(tabs[0]).color,
				hovered: getComputedStyle(tabs[1]).color,
				focusOutline: getComputedStyle(tabs[2]).outlineWidth,
				plainSheen: sheen(tabs[0]),
				hoverSheen: sheen(tabs[1]),
				focusSheen: sheen(tabs[2]),
				pressedSheen: sheen(tabs[3]),
			};
		});
		expect(tabState.hovered, 'forced :hover did not recolor the tab').not.toBe(tabState.plain);
		expect(tabState.focusOutline, 'forced :focus-visible drew no outline').not.toBe('0px');
		expect(tabState.hoverSheen, 'forced :hover did not shift the sheen').not.toBe(
			tabState.plainSheen,
		);
		expect(tabState.focusSheen, 'forced :focus-visible did not shift the sheen').not.toBe(
			tabState.plainSheen,
		);
		expect(tabState.pressedSheen, 'forced :active did not shift the sheen').not.toBe(
			tabState.hoverSheen,
		);

		const keyState = await page.evaluate(() => {
			const keys = document.querySelectorAll('div.Keyboard div.Key div.Key1');
			return {
				hovered: getComputedStyle(keys[2]).color,
				pressed: getComputedStyle(keys[4]).backgroundColor,
				plain: getComputedStyle(keys[6]).color,
				plainBg: getComputedStyle(keys[6]).backgroundColor,
			};
		});
		expect(keyState.hovered, 'hovered key looks like a plain key').not.toBe(keyState.plain);
		expect(keyState.pressed, 'pressed key looks like a plain key').not.toBe(keyState.plainBg);

		await captureGroup(page, composer, [
			['tabpanel', 'TTabPanel — active, normal, hovered, focused and pressed tabs', '#shot-tabpanel'],
			['accordion', 'TAccordion — active and normal headers', '#shot-accordion'],
			['slider', 'TSlider — horizontal and vertical, track and progress', '#shot-slider'],
			['keyboard', 'TKeyboard — normal, hovered and pressed keys', '#shot-keyboard'],
		]);

		// ── Color picker, opened on its own ───────────────────────────────────
		await page.locator('.TColorPicker_button').first().click();
		await expect(page.locator('.TColorPicker').first()).toBeVisible();
		await page.evaluate(() => {
			const swatch = document.querySelector('.BasicColorPicker .basic_colors td img');
			if (swatch) {
				swatch.classList.add('pickerhover');
			}
		});
		await captureGroup(page, composer, [
			['colorpicker', 'TColorPicker — basic palette panel', '.TColorPicker'],
		]);

		// ── Date picker, with the color picker dismissed first ────────────────
		await page.locator('#shot-datepicker').click({ position: { x: 2, y: 2 } });
		await page.locator('.TDatePickerImageButton').first().click();
		await expect(page.locator('.TDatePicker_default').first()).toBeVisible();
		await page.evaluate(() => {
			const days = document.querySelectorAll('.TDatePicker_default td.date');
			for (const day of days) {
				if (!day.className.includes('selected') && !day.className.includes('today')) {
					day.classList.add('hover');
					break;
				}
			}
		});
		await captureGroup(page, composer, [
			['datepicker', 'TDatePicker — calendar panel, today, selected and hovered days', '.TDatePicker_default'],
		]);

		// ── Scaffold page ─────────────────────────────────────────────────────
		await h.url(SCAFFOLD);
		await h.assertSourceContains('Color Scheme Scaffold');
		const forceScaffoldPseudo = await pseudoForcer(context, page);
		await forceScaffoldPseudo('#pagerLink', 0, ['hover']);
		const pagerState = await page.evaluate(() => ({
			hovered: getComputedStyle(document.querySelector('#pagerLink')).backgroundColor,
			plain: getComputedStyle(document.querySelector('.pager a')).backgroundColor,
		}));
		expect(pagerState.hovered, 'forced :hover did not recolor the pager link').not.toBe(
			pagerState.plain,
		);
		await captureGroup(page, composer, [
			['scaffold', 'ActiveRecord Scaffold — rows, pager and edit inputs', '#shot-scaffold'],
		]);
	});
});
