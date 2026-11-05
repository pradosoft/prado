import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=MediaTagsTest';
const BASE = 'ctl0_Content_';

/**
 * The media elements render their attributes, sources, tracks, and fallback content.
 */
test('TMediaTagsTestCase: elements render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Media Tags Test Case');

	await h.assertAttribute(`${BASE}video@controls`, 'controls');
	await h.assertAttribute(`${BASE}video@width`, '320');
	await h.assertAttribute(`${BASE}video@preload`, 'auto');
	await h.assertAttribute(`${BASE}audio@controls`, 'controls');
	await h.assertAttribute(`${BASE}captions@kind`, 'captions');
	await h.assertAttribute(`${BASE}captions@srclang`, 'en');
	await h.assertAttribute(`${BASE}captions@default`, 'default');
	await expect(page.locator(`#${BASE}audio > source`)).toHaveCount(2);
	await expect(page.locator('picture > source + img')).toHaveAttribute('id', `${BASE}picture`);
	await expect(page.getByRole('link', { name: 'Download the clip' })).toBeHidden();
});

/**
 * The captions track is listed with its kind, label, and language, and it loads
 * when shown. Chromium and Firefox show the `default` track; WebKit follows the
 * user's caption preference and shows it when it is selected.
 */
test('TMediaTagsTestCase: captions track loads', async ({ page, browserName }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const track = () => page.evaluate((id) => {
		const t = document.getElementById(id).textTracks[0];
		return { count: document.getElementById(id).textTracks.length, kind: t.kind, label: t.label, language: t.language, mode: t.mode };
	}, `${BASE}video`);
	expect(await track()).toMatchObject({ count: 1, kind: 'captions', label: 'English', language: 'en' });
	if (browserName === 'webkit') {
		await page.evaluate((id) => { document.getElementById(id).textTracks[0].mode = 'showing'; }, `${BASE}video`);
	}
	expect((await track()).mode).toBe('showing');
	await expect.poll(() => page.evaluate((id) => document.getElementById(id).readyState, `${BASE}captions`)).toBe(2);
	expect(await page.evaluate((id) => document.getElementById(id).track.cues.length, `${BASE}captions`)).toBe(1);
});

/**
 * The browser picks the first supported source, and TPicture's image shows the SVG source.
 */
test('TMediaTagsTestCase: sources are selected', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect.poll(() => page.evaluate((id) => document.getElementById(id).currentSrc, `${BASE}audio`)).toMatch(/media\/tone\.wav$/);
	await expect.poll(() => page.evaluate((id) => document.getElementById(id).currentSrc, `${BASE}picture`)).toMatch(/media\/picture\.svg$/);
	await expect(page.getByRole('img', { name: 'A green bar' })).toHaveAttribute('id', `${BASE}picture`);
});

/**
 * With ShowControls, the video takes keyboard focus; Chromium and Firefox place it in
 * the tab order, and WebKit reaches its controls through its own media controls.
 */
test('TMediaTagsTestCase: video takes focus', async ({ page, browserName }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const video = page.locator(`#${BASE}video`);
	await video.focus();
	await expect(video).toBeFocused();
	if (browserName !== 'webkit') {
		expect(await video.evaluate((el) => el.tabIndex)).toBe(0);
	}
});

/**
 * Chromium's accessibility tree names the media elements and the picture's image,
 * and the `<picture>` itself is ignored.
 */
test('TMediaTagsTestCase: browser accessibility roles', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const expected = [
		[`#${BASE}video`, 'Video', 'Silent clip', false],
		[`#${BASE}audio`, 'Audio', 'Silent talk', false],
		[`#${BASE}picture`, 'image', 'A green bar', false],
		['picture', 'none', '', true],
	];
	for (const [selector, role, name, ignored] of expected) {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
		const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
		expect(nodes[0].role.value, selector).toBe(role);
		expect(nodes[0].name ? nodes[0].name.value : '', selector).toBe(name);
		expect(!!nodes[0].ignored, selector).toBe(ignored);
	}
});
