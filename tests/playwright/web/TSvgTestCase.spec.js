import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SvgTest';
const BASE = 'ctl0_Content_';

/**
 * A named TSvg is an image named by its title and described by its desc; a
 * decorative TSvg is hidden; a group TSvg keeps its link.
 */
test('TSvgTestCase: graphics expose names or are hidden', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('SVG Test Case');

	const icon = page.getByRole('img', { name: 'Settings' });
	await expect(icon).toHaveAttribute('id', `${BASE}icon`);
	await expect(icon).toHaveAccessibleDescription('A gear');
	await h.assertAttribute(`${BASE}decor@aria-hidden`, 'true');
	await expect(page.getByRole('img', { name: 'Badge' })).toHaveCount(2);
	const map = page.getByRole('group', { name: 'Regions' });
	await expect(map.getByRole('link', { name: 'North' })).toHaveCount(1);
});

/**
 * Chromium's accessibility tree names the graphics, ignores the decorative one,
 * and keeps the group's link.
 */
test('TSvgTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const node = async (selector) => {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
		return (await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false })).nodes[0];
	};
	const icon = await node(`#${BASE}icon`);
	expect(icon.role.value).toBe('image');
	expect(icon.name.value).toBe('Settings');
	expect(icon.description.value).toBe('A gear');
	expect((await node(`#${BASE}decor`)).ignored).toBe(true);
	const badge = await node(`#${BASE}badge1`);
	expect(badge.role.value).toBe('image');
	expect(badge.name.value).toBe('Badge');
	const map = await node(`#${BASE}map`);
	expect(map.role.value).toBe('group');
	expect(map.name.value).toBe('Regions');
	expect((await node(`#${BASE}map a`)).role.value).toBe('link');
});

/**
 * The SvgFile is sanitized: its script does not run and its event attribute is gone.
 */
test('TSvgTestCase: SvgFile content is sanitized', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	expect(await page.evaluate(() => document.documentElement.dataset.svgScript)).toBeUndefined();
	await expect(page.locator(`#${BASE}badge1 script`)).toHaveCount(0);
	await expect(page.locator(`#${BASE}badge1 circle`)).not.toHaveAttribute('onclick', /.*/);
	await page.locator(`#${BASE}badge1 circle`).click();
	expect(await page.evaluate(() => document.documentElement.dataset.svgClicked)).toBeUndefined();
});

/**
 * Two copies of one SvgFile get their own gradient IDs, and the hoisted style,
 * rendered once in the head, fills each copy from its own gradient.
 */
test('TSvgTestCase: copies keep their own IDs and share one hoisted style', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.locator(`#${BASE}badge1 linearGradient`)).toHaveAttribute('id', `${BASE}badge1_fill`);
	await expect(page.locator(`#${BASE}badge2 linearGradient`)).toHaveAttribute('id', `${BASE}badge2_fill`);
	await expect(page.locator('svg style')).toHaveCount(0);
	const headCss = await page.locator('head style').evaluateAll((els) => els.map((el) => el.textContent).join('\n'));
	expect(headCss).toContain(`url(#${BASE}badge1_fill)`);
	expect(headCss).toContain(`url(#${BASE}badge2_fill)`);
	const fill = (id) => page.locator(`#${BASE}${id} circle`).evaluate((el) => getComputedStyle(el).fill);
	expect(await fill('badge1')).toContain(`#${BASE}badge1_fill`);
	expect(await fill('badge2')).toContain(`#${BASE}badge2_fill`);
});

/**
 * ReducedMotion stops the CSS animation for a user who prefers reduced motion.
 */
test('TSvgTestCase: reduced motion stops CSS animation', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	const name = () => page.locator(`#${BASE}spinner circle`).evaluate((el) => getComputedStyle(el).animationName);

	await h.url(PAGE_URL);
	expect(await name()).toBe('spin');

	await page.emulateMedia({ reducedMotion: 'reduce' });
	expect(await name()).toBe('none');
});
