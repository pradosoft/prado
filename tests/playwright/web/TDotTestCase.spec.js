import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=DotTest';
const BASE = 'ctl0_Content_';

/**
 * An Inline dot with AlternateText is an image named by its <title>; one without
 * is hidden; an explicit Role replaces role="img"; an Embed dot keeps its alt.
 */
test('TDotTestCase: inline dots render accessible names', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Dot Test Case');

	const green = page.locator(`#${BASE}green`);
	await expect(green).toHaveAttribute('role', 'img');
	await expect(green).toHaveAttribute('aria-labelledby', `${BASE}green_title`);
	await expect(page.locator(`#${BASE}green_title`)).toHaveText('Online');
	await expect(page.getByRole('img', { name: 'Online' })).toHaveCount(1);
	await expect(page.getByRole('img', { name: 'Offline' })).toHaveCount(1);

	const decorative = page.locator(`#${BASE}decorative`);
	await expect(decorative).toHaveAttribute('aria-hidden', 'true');
	await expect(decorative).not.toHaveAttribute('role');

	await expect(page.locator(`#${BASE}status`)).toHaveAttribute('role', 'status');
	await expect(page.locator(`#${BASE}embed`)).toHaveAttribute('alt', 'Idle');
});

/**
 * Each inline dot's url(#…) references resolve to its own gradients and filters,
 * so the second dot draws in its own color.
 */
test('TDotTestCase: inline dots have distinct IDs', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const result = await page.evaluate(() => {
		const ids = [...document.querySelectorAll('[id]')].map((el) => el.id);
		const dots = {};
		for (const svg of document.querySelectorAll('svg')) {
			const refs = [...svg.querySelectorAll('[fill^="url("], [filter^="url("], [clip-path^="url("]')]
				.flatMap((el) => ['fill', 'filter', 'clip-path'].map((a) => el.getAttribute(a)))
				.filter((v) => v && v.startsWith('url(#'))
				.map((v) => v.slice(5, -1));
			const grad = document.getElementById(`${svg.id}_grad`);
			dots[svg.id] = {
				ownRefs: refs.every((id) => svg.contains(document.getElementById(id))),
				refCount: refs.length,
				main: grad ? grad.querySelector('stop[offset="100%"]').getAttribute('stop-color') : null,
			};
		}
		return { duplicates: ids.filter((id, i) => ids.indexOf(id) !== i), dots };
	});

	expect(result.duplicates).toEqual([]);
	for (const id of ['green', 'red', 'decorative', 'status']) {
		expect(result.dots[`${BASE}${id}`].refCount, id).toBe(6);
		expect(result.dots[`${BASE}${id}`].ownRefs, id).toBe(true);
	}
	expect(result.dots[`${BASE}green`].main).toBe('#007000');
	expect(result.dots[`${BASE}red`].main).toBe('#B80000');
});

/**
 * Chromium exposes a named inline dot as an image with its AlternateText, and
 * leaves a decorative one out of the accessibility tree.
 */
test('TDotTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const axNode = async (id) => {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `#${BASE}${id}` });
		const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
		return nodes[0];
	};
	for (const [id, role, name] of [['green', 'image', 'Online'], ['red', 'image', 'Offline'], ['status', 'status', 'Away'], ['embed', 'image', 'Idle']]) {
		const node = await axNode(id);
		expect(node.role.value, id).toBe(role);
		expect(node.name.value, id).toBe(name);
	}
	expect((await axNode('decorative')).ignored).toBe(true);
});
