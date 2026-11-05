import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ActiveSvgTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Active Svg Test Case');
	return h;
}

async function press(page, id, calls) {
	await page.locator(`#${B}${id}`).click();
	await expect(page.locator(`#${B}calls`)).toHaveText(String(calls));
}

/**
 * The graphic loads idiomorph and is named by its AlternateText.
 */
test('TActiveSvgTestCase: the graphic morphs and is named', async ({ page }) => {
	await open(page);
	expect(await page.evaluate(() => typeof Idiomorph)).toBe('object');
	await expect(page.getByRole('img', { name: 'Sales 0' })).toHaveCount(2);
});

/**
 * New content keeps the morphed nodes and their listeners and renames the graphic;
 * the Replace graphic gets new nodes.
 */
test('TActiveSvgTestCase: content updates keep nodes and listeners', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__bar = document.getElementById('ctl0_Content_chart_bar1');
		window.__title = document.getElementById('ctl0_Content_chart_title');
		window.__clicks = 0;
		window.__bar.addEventListener('click', () => window.__clicks++);
		window.__replacedBar = document.getElementById('ctl0_Content_replaced_bar1');
	});
	await press(page, 'grow', 1);
	const result = await page.evaluate(() => {
		const bar = document.getElementById('ctl0_Content_chart_bar1');
		bar.dispatchEvent(new MouseEvent('click', { bubbles: true }));
		return {
			sameBar: bar === window.__bar,
			sameTitle: document.getElementById('ctl0_Content_chart_title') === window.__title,
			clicks: window.__clicks,
			height: bar.getAttribute('height'),
			ns: bar.namespaceURI,
			sameReplacedBar: document.getElementById('ctl0_Content_replaced_bar1') === window.__replacedBar,
			replacedHeight: document.getElementById('ctl0_Content_replaced_bar1').getAttribute('height'),
		};
	});
	expect(result).toEqual({
		sameBar: true, sameTitle: true, clicks: 1, height: '30', ns: 'http://www.w3.org/2000/svg',
		sameReplacedBar: false, replacedHeight: '30',
	});
	await expect(page.getByRole('img', { name: 'Sales 1' })).toHaveCount(2);
	await expect(page.locator(`#${B}chart`)).toHaveAttribute('aria-labelledby', `${B}chart_title`);
});

/**
 * Setting the same content and name sends no update: the morphed graphic has no
 * mutation and the Replace graphic keeps its nodes.
 */
test('TActiveSvgTestCase: unchanged content sends no update', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__mutations = 0;
		new MutationObserver((records) => { window.__mutations += records.length; })
			.observe(document.getElementById('ctl0_Content_chart'), { subtree: true, childList: true, attributes: true, characterData: true });
		window.__replacedBar = document.getElementById('ctl0_Content_replaced_bar1');
	});
	await press(page, 'same', 1);
	const result = await page.evaluate(() => ({
		mutations: window.__mutations,
		sameReplacedBar: document.getElementById('ctl0_Content_replaced_bar1') === window.__replacedBar,
	}));
	expect(result).toEqual({ mutations: 0, sameReplacedBar: true });
});

/**
 * A new `<style>` in the content reaches the page in the callback and applies to the kept nodes.
 */
test('TActiveSvgTestCase: new hoisted styles apply', async ({ page }) => {
	await open(page);
	const bar = page.locator(`#${B}chart_bar1`);
	await expect(bar).toHaveCSS('fill', 'rgb(0, 0, 255)');
	await page.evaluate(() => { window.__bar = document.getElementById('ctl0_Content_chart_bar1'); });
	await press(page, 'restyle', 1);
	await expect(bar).toHaveCSS('fill', 'rgb(0, 128, 0)');
	expect(await page.evaluate(() => document.getElementById('ctl0_Content_chart_bar1') === window.__bar)).toBe(true);
	expect(await page.locator('svg style').count()).toBe(0);
});

/**
 * Decorative hides the graphic from assistive technology and removes its title; clearing it
 * names the graphic again.
 */
test('TActiveSvgTestCase: Decorative toggles in a callback', async ({ page }) => {
	await open(page);
	const chart = page.locator(`#${B}chart`);
	await press(page, 'decorate', 1);
	await expect(chart).toHaveAttribute('aria-hidden', 'true');
	await expect(chart).not.toHaveAttribute('role', 'img');
	await expect(page.locator(`#${B}chart_title`)).toHaveCount(0);
	await expect(page.getByRole('img', { name: 'Sales 0' })).toHaveCount(1);
	await press(page, 'decorate', 2);
	await expect(chart).not.toHaveAttribute('aria-hidden', 'true');
	await expect(page.getByRole('img', { name: 'Sales 0' })).toHaveCount(2);
});

/**
 * An explicit render of template content morphs it in place.
 */
test('TActiveSvgTestCase: template content re-renders in place', async ({ page }) => {
	await open(page);
	await page.evaluate(() => { window.__dot = document.getElementById('drawn_dot'); });
	await press(page, 'redraw', 1);
	const dot = await page.evaluate(() => {
		const d = document.getElementById('drawn_dot');
		return { same: d === window.__dot, r: d.getAttribute('r') };
	});
	expect(dot).toEqual({ same: true, r: '6' });
	await expect(page.getByRole('img', { name: 'Dot' })).toHaveCount(1);
});

/**
 * Chromium's accessibility tree renames the graphic after an update and ignores it while decorative.
 */
test('TActiveSvgTestCase: browser accessibility tree after updates', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	await open(page);
	const cdp = await page.context().newCDPSession(page);
	const node = async (selector) => {
		const { root } = await cdp.send('DOM.getDocument');
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
		return (await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false })).nodes[0];
	};
	await press(page, 'grow', 1);
	const chart = await node(`#${B}chart`);
	expect(chart.role.value).toBe('image');
	expect(chart.name.value).toBe('Sales 1');
	await press(page, 'decorate', 2);
	expect((await node(`#${B}chart`)).ignored).toBe(true);
});
