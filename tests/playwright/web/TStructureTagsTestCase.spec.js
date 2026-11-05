import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=StructureTagsTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the bitmap size, draw count, and the pixel at (x, y) of a canvas.
 */
async function canvasState(page, id, x = 10, y = 10) {
	return page.locator(`#${BASE}${id}`).evaluate((el, [px, py]) => ({
		width: el.width,
		height: el.height,
		draws: el.dataset.draws,
		pixel: Array.from(el.getContext('2d').getImageData(px, py, 1, 1).data),
	}), [x, y]);
}

/**
 * The description list, heading group, and menu expose their roles and names.
 * Playwright maps neither `<hgroup>` to `group` nor canvas fallback content to roles;
 * the Chromium test below checks both.
 */
test('TStructureTagsTestCase: elements expose their roles', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Structure Tags Test Case');

	await expect(page.getByRole('term')).toHaveText(['Author', 'Editor']);
	await expect(page.getByRole('definition')).toHaveText(['Brad', 'Qiang']);
	await expect(page.locator(`#${BASE}author`)).toHaveJSProperty('tagName', 'DD');

	await expect(page.locator(`#${BASE}hgroup`).getByRole('heading', { level: 2, name: 'Prado' })).toHaveCount(1);
	await expect(page.getByRole('heading', { level: 2 })).toHaveText(['Prado']);

	const menu = page.getByRole('list', { name: 'Edit' });
	await expect(menu).toHaveJSProperty('tagName', 'MENU');
	await expect(menu.getByRole('listitem')).toHaveCount(2);

	await expect(page.getByRole('img', { name: 'Sales rose 20%' })).toHaveAttribute('id', `${BASE}chart`);
});

/**
 * Chromium's accessibility tree gives each element the role its docblock states.
 */
test('TStructureTagsTestCase: browser accessibility roles', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const expected = [
		[`#${BASE}dl`, 'DescriptionList', ''],
		[`#${BASE}dl dt`, 'term', 'Author'],
		[`#${BASE}author`, 'definition', ''],
		[`#${BASE}hgroup`, 'group', ''],
		[`#${BASE}hgroup h2`, 'heading', 'Prado'],
		[`#${BASE}menu`, 'list', 'Edit'],
		[`#${BASE}chart`, 'image', 'Sales rose 20%'],
		[`#${BASE}interactive`, 'Canvas', ''],
		['#region', 'button', 'North region: 40%'],
	];
	for (const [selector, role, name] of expected) {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
		const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
		expect(nodes[0].role.value, selector).toBe(role);
		expect(nodes[0].name ? nodes[0].name.value : '', selector).toBe(name);
	}
});

/**
 * A TMenuList LinkButton item posts back and raises OnClick.
 */
test('TStructureTagsTestCase: menu item posts back', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await Promise.all([
		page.waitForNavigation(),
		page.getByRole('list', { name: 'Edit' }).getByRole('link', { name: 'Copy' }).click(),
	]);
	await expect(page.locator(`#${BASE}status`)).toHaveText('clicked: copy');
});

/**
 * The fallback content of a canvas is focusable.
 */
test('TStructureTagsTestCase: canvas fallback content is focusable', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const region = page.locator('#region');
	await region.focus();
	await expect(region).toBeFocused();
});

/**
 * OnDraw draws on page load, and the bitmap is the CSS size times the pixel ratio.
 */
test('TStructureTagsTestCase: canvas draws on load', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('1');
	const ratio = await page.evaluate(() => window.devicePixelRatio);
	const state = await canvasState(page, 'chart');
	expect(state.width).toBe(200 * ratio);
	expect(state.height).toBe(100 * ratio);
	expect(state.pixel).toEqual([255, 0, 0, 255]);
});

/**
 * A display size change redraws the canvas at the new size.
 */
test('TStructureTagsTestCase: canvas redraws on resize', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('1');

	await page.locator(`#${BASE}chart`).evaluate((el) => { el.style.width = '300px'; });
	await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('2');
	const ratio = await page.evaluate(() => window.devicePixelRatio);
	const state = await canvasState(page, 'chart', 290 * ratio, 90 * ratio);
	expect(state.width).toBe(300 * ratio);
	expect(state.pixel).toEqual([255, 0, 0, 255]);
});

/**
 * A callback renders the canvas again; the new element is drawn and carries the
 * new AlternateText.
 */
test('TStructureTagsTestCase: canvas draws after a callback re-render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('1');
	await page.locator(`#${BASE}chart`).evaluate((el) => { el.dataset.original = 'yes'; });

	await h.click(`${BASE}btnRedraw`);
	await h.waitForAjaxCalls();
	await expect(page.getByRole('img', { name: 'Sales rose 30%' })).toHaveAttribute('id', `${BASE}chart`);
	await expect(page.locator(`#${BASE}chart`)).not.toHaveAttribute('data-original', 'yes');
	await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('1');
	expect((await canvasState(page, 'chart')).pixel).toEqual([255, 0, 0, 255]);
	expect(await page.evaluate((id) => Prado.Registry[id] instanceof Prado.WebUI.TCanvas, `${BASE}chart`)).toBe(true);
});

test.describe('high-density screen', () => {
	test.use({ deviceScaleFactor: 2 });

	/**
	 * The bitmap is the CSS size times the pixel ratio, and the scaled context fills it.
	 */
	test('TStructureTagsTestCase: canvas bitmap matches the pixel ratio', async ({ page }) => {
		const h = new PradoTestHelper(page, GENERIC_BASE_URL);
		await h.url(PAGE_URL);

		await expect.poll(async () => (await canvasState(page, 'chart')).draws).toBe('1');
		const state = await canvasState(page, 'chart', 390, 190);
		expect(state.width).toBe(400);
		expect(state.height).toBe(200);
		expect(state.pixel).toEqual([255, 0, 0, 255]);
		await expect(page.locator(`#${BASE}chart`)).toHaveCSS('width', '200px');
	});
});
