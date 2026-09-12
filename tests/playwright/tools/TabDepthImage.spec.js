import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

/**
 * EXPERIMENT — renders the candidate tab depth treatments for review.
 *
 * Skipped unless PRADO_TAB_DEPTH_IMAGE is set, and writes to build/, which is
 * ignored, so nothing here touches the quickstart images.
 *
 *   PRADO_TAB_DEPTH_IMAGE=1 npx playwright test --project=chromium \
 *     tests/playwright/tools/TabDepthImage.spec.js
 *
 * Delete this with tab-depth.css and the TabDepthTest page if the experiment is
 * dropped.
 */

const OUT_DIR = resolve(process.cwd(), 'build', 'tab-depth');
const PAGE = 'web/index.php?page=TabDepthTest';
const SCHEMES = ['light', 'dark'];

test.use({ deviceScaleFactor: 2, viewport: { width: 900, height: 1400 } });

const declareScheme = (page, value) =>
	page.evaluate((v) => {
		document.documentElement.style.colorScheme = v;
	}, value);

test.describe('tab depth experiment', () => {
	test.skip(
		!process.env.PRADO_TAB_DEPTH_IMAGE,
		'set PRADO_TAB_DEPTH_IMAGE=1 to render the tab depth comparison',
	);

	test('render', async ({ page, context }) => {
		mkdirSync(OUT_DIR, { recursive: true });
		const h = new PradoTestHelper(page, GENERIC_BASE_URL);
		await h.url(PAGE);
		await h.assertSourceContains('Tab Depth Test');

		// The experiment is inert unless its stylesheet actually loaded.
		const applied = await page.evaluate(
			() => getComputedStyle(document.querySelector('.sheen-medium .tab-normal')).backgroundImage,
		);
		expect(applied, 'tab-depth.css did not load').not.toBe('none');

		// Each strip shows selected, deselected, hover and mouse down. Only the
		// first two happen on their own, so the last two are forced. DOM.getDocument
		// resets the node map and drops anything already forced, so it is fetched
		// once and its root reused.
		const cdp = await context.newCDPSession(page);
		await cdp.send('DOM.enable');
		await cdp.send('CSS.enable');
		const { root } = await cdp.send('DOM.getDocument');
		for (const wrapper of ['.sheen-none', '.sheen-subtle', '.sheen-medium', '.sheen-strong']) {
			const { nodeIds } = await cdp.send('DOM.querySelectorAll', {
				nodeId: root.nodeId,
				selector: `${wrapper} .tab-normal`,
			});
			expect(nodeIds.length, `expected four inactive tabs in ${wrapper}`).toBe(4);
			const forced = [
				[1, ['hover']],
				[2, ['focus', 'focus-visible']],
				[3, ['hover', 'active']],
			];
			for (const [index, forcedPseudoClasses] of forced) {
				await cdp.send('CSS.forcePseudoState', {
					nodeId: nodeIds[index],
					forcedPseudoClasses,
				});
			}
		}

		// A forced state that quietly failed would ship a misleading picture.
		const states = await page.evaluate(() => {
			const tabs = [...document.querySelectorAll('.sheen-medium .tab-normal')];
			return tabs.map((t) => getComputedStyle(t).backgroundImage);
		});
		expect(states[1], 'forced :hover did not change the gradient').not.toBe(states[0]);
		expect(states[2], 'forced :focus-visible did not change the gradient').not.toBe(states[0]);
		expect(states[3], 'forced :active did not change the gradient').not.toBe(states[1]);

		const outline = await page.evaluate(
			() =>
				getComputedStyle(document.querySelectorAll('.sheen-medium .tab-normal')[2]).outlineWidth,
		);
		expect(outline, 'forced :focus-visible drew no outline').not.toBe('0px');

		const shots = {};
		for (const scheme of SCHEMES) {
			await declareScheme(page, scheme);
			shots[scheme] = await page.locator('#shot-depth').screenshot();
		}

		const composer = await context.newPage();
		const sides = SCHEMES.map(
			(s) => `
				<div class="side">
					<div class="label">${s === 'light' ? 'Light' : 'Dark'}</div>
					<img src="data:image/png;base64,${shots[s].toString('base64')}" />
				</div>`,
		).join('');
		await composer.setContent(`
			<style>
				body { margin: 0; background: #6f767d; }
				#pair { display: inline-block; padding: 10px 12px 12px; }
				#title { font: 600 13px/1.8 -apple-system, "Segoe UI", Arial, sans-serif; color: #fff; }
				.row { display: flex; gap: 12px; align-items: flex-start; }
				.label {
					font: 600 11px/1.9 -apple-system, "Segoe UI", Arial, sans-serif;
					color: #e8eaed; text-transform: uppercase; letter-spacing: .06em;
				}
				img { display: block; }
			</style>
			<div id="pair">
				<div id="title">TTabPanel — sheen across the tab states, left to right: selected, deselected, hover, focus, mouse down</div>
				<div class="row">${sides}</div>
			</div>
		`);
		await composer.waitForFunction(() => Array.from(document.images).every((i) => i.complete));
		await composer.evaluate(() => {
			for (const img of document.images) {
				img.style.width = `${img.naturalWidth / 2}px`;
			}
		});
		writeFileSync(resolve(OUT_DIR, 'tab-depth.png'), await composer.locator('#pair').screenshot());
		console.log('wrote build/tab-depth/tab-depth.png');
	});
});
