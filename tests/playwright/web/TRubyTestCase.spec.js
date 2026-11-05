import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=RubyTest';
const BASE = 'ctl0_Content_';

/**
 * The browser lays out the annotation as ruby text and hides the parentheses.
 */
test('TRubyTestCase: ruby renders annotations and hides parentheses', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Ruby Test Case');

	await h.assertAttribute(`${BASE}word@lang`, 'ja');
	await expect(page.locator(`#${BASE}word`)).toHaveCSS('display', 'ruby');
	await expect(page.locator(`#${BASE}word rt`)).toHaveCSS('display', 'ruby-text');
	await expect(page.locator(`#${BASE}word rp`)).toHaveCount(2);
	for (const rp of await page.locator(`#${BASE}word rp, #${BASE}children rp`).all()) {
		await expect(rp).toHaveCSS('display', 'none');
	}
	await expect(page.locator(`#${BASE}word`)).toHaveText('漢字(かんじ)');
	expect(await page.locator(`#${BASE}word`).evaluate((el) => el.innerText)).toBe('漢字かんじ');
});

/**
 * The accessible text reads each base text before its annotation.
 */
test('TRubyTestCase: base text reads before its annotation', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.locator('p').nth(0)).toMatchAriaSnapshot('- paragraph: This is 漢字 かんじ .');
	await expect(page.locator('p').nth(1)).toMatchAriaSnapshot('- paragraph: 漢 かん 字 じ');
	await expect(page.locator('p').nth(2)).toMatchAriaSnapshot('- paragraph: 東 とう');
});

/**
 * Chromium exposes a ruby's annotation as its description. One TRuby per base
 * text gives each annotation its own ruby.
 */
test('TRubyTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	for (const [id, annotation] of [['word', 'かんじ'], ['kan', 'かん'], ['ji', 'じ'], ['children', 'とう']]) {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `#${BASE}${id}` });
		const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
		expect(nodes[0].role.value, id).toBe('Ruby');
		expect(nodes[0].description.value, id).toBe(annotation);
	}
});
