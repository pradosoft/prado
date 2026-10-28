import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=TextTagsTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the lowercase tag name of the element with the given control ID.
 */
async function tagOf(page, id) {
	return page.locator(`#${BASE}${id}`).evaluate((el) => el.tagName.toLowerCase());
}

/**
 * Each control renders its HTML element.
 */
test('TTextTagsTestCase: controls render their elements', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Text Tags Test Case');

	const tags = {
		code: 'code', samp: 'samp', kbd: 'kbd', pre: 'pre', sub: 'sub', sup: 'sup',
		cite: 'cite', quote: 'q', blockquote: 'blockquote', address: 'address',
		ins: 'ins', del: 'del', bdi: 'bdi', bdo: 'bdo', data: 'data',
	};
	for (const [id, tag] of Object.entries(tags)) {
		expect(await tagOf(page, id), id).toBe(tag);
	}
	await expect(page.locator(`#${BASE}kbd kbd`)).toHaveText(['Ctrl', 'C']);
});

/**
 * The elements expose their implicit roles, and none renders a `role` attribute.
 * Playwright does not map `<address>` to `group`; the Chromium test below checks it.
 */
test('TTextTagsTestCase: elements expose their roles', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const roles = {
		code: 'code', sub: 'subscript', sup: 'superscript', blockquote: 'blockquote',
		ins: 'insertion', del: 'deletion',
	};
	for (const [id, role] of Object.entries(roles)) {
		await expect(page.getByRole(role).and(page.locator(`#${BASE}${id}`)), id).toHaveCount(1);
		await h.assertAttribute(`${BASE}${id}@role`, null);
	}
	await expect(page.getByRole('img', { name: 'A cat face' })).toHaveAttribute('id', `${BASE}art`);
});

/**
 * Chromium's accessibility tree gives each element the role its docblock states.
 */
test('TTextTagsTestCase: browser accessibility roles', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const expected = {
		code: 'code', samp: 'generic', kbd: 'generic', pre: 'generic', art: 'image',
		sub: 'subscript', sup: 'superscript', cite: 'generic', quote: 'generic',
		blockquote: 'blockquote', address: 'group', ins: 'insertion', del: 'deletion',
		bdi: 'generic', bdo: 'generic', data: 'generic',
	};
	const names = { art: 'A cat face', address: 'Webmaster' };
	for (const [id, role] of Object.entries(expected)) {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `#${BASE}${id}` });
		const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
		expect(nodes[0].role.value, id).toBe(role);
		if (names[id]) {
			expect(nodes[0].name.value, id).toBe(names[id]);
		}
	}
});

/**
 * Cite, DateTime, Dir, and Value render their attributes.
 */
test('TTextTagsTestCase: attributes render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.assertAttribute(`${BASE}quote@cite`, 'https://example.com/q');
	await h.assertAttribute(`${BASE}blockquote@cite`, 'https://example.com/b');
	await h.assertAttribute(`${BASE}ins@cite`, '/changes/12');
	await h.assertAttribute(`${BASE}ins@datetime`, '2026-10-01');
	await h.assertAttribute(`${BASE}del@datetime`, '2026-10-01');
	await h.assertAttribute(`${BASE}bdi@dir`, null);
	await h.assertAttribute(`${BASE}bdo@dir`, 'rtl');
	await h.assertAttribute(`${BASE}data@value`, '398');
	await expect(page.locator(`#${BASE}data`)).toHaveText('Mini Ketchup');
});

/**
 * The browser adds the quotation marks of `<q>`, strikes through `<del>`, and
 * underlines `<ins>`, so the edits are not marked by color alone.
 */
test('TTextTagsTestCase: browser styles mark quotations and edits', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const before = await page.locator(`#${BASE}quote`).evaluate((el) => getComputedStyle(el, '::before').content);
	expect(before).not.toBe('none');
	await expect(page.locator(`#${BASE}del`)).toHaveCSS('text-decoration-line', 'line-through');
	await expect(page.locator(`#${BASE}ins`)).toHaveCSS('text-decoration-line', 'underline');
	await expect(page.locator(`#${BASE}pre`)).toHaveCSS('white-space', 'pre');
});
