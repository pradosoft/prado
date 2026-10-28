import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=FootnoteTest';
const BASE = 'ctl0_Content_';

/**
 * The references are numbered links described by the list heading, and the list is
 * a region named by its heading.
 */
test('TFootnoteTestCase: references and list expose names', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Footnote Test Case');

	const refs = page.locator('sup > a');
	await expect(refs).toHaveText(['1', '2', '2']);
	const first = page.getByRole('link', { name: '1', exact: true });
	await expect(first).toHaveAccessibleDescription('Notes');
	await expect(first).toHaveAttribute('href', `#${BASE}notes_note1`);

	const region = page.getByRole('region', { name: 'Notes' });
	await expect(region.getByRole('heading', { level: 2, name: 'Notes' })).toHaveCount(1);
	await expect(region.getByRole('listitem')).toHaveCount(2);
	await expect(region.getByRole('link', { name: 'Back to reference 1' })).toHaveAttribute('href', `#${BASE}fnDesign_ref`);
	await expect(region.getByRole('link', { name: 'Back to reference 2a' })).toHaveAttribute('href', `#${BASE}fnSource_ref`);
	await expect(region.getByRole('link', { name: 'Back to reference 2b' })).toHaveAttribute('href', `#${BASE}fnSource2_ref`);
	await expect(page.locator(`#${BASE}notes_note2`)).toContainText('See the Prado manual.');
});

/**
 * Following a reference moves to its note, the next Tab reaches the note's back link,
 * and the back link returns to the reference.
 */
test('TFootnoteTestCase: reference and back link navigate', async ({ page, browserName }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await page.getByRole('link', { name: '1', exact: true }).click();
	await expect(page).toHaveURL(new RegExp(`#${BASE}notes_note1$`));
	await expect(page.locator(`#${BASE}notes_note1`)).toBeInViewport();
	const back = page.getByRole('link', { name: 'Back to reference 1' });
	if (browserName !== 'webkit') {
		await page.keyboard.press('Tab');
		await expect(back).toBeFocused();
	}
	await back.click();
	await expect(page).toHaveURL(new RegExp(`#${BASE}fnDesign_ref$`));
	await expect(page.locator(`#${BASE}fnDesign_ref`)).toBeInViewport();
});

/**
 * Chromium's accessibility tree gives the list the region role and the reference
 * its name and description.
 */
test('TFootnoteTestCase: browser accessibility tree', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const node = async (selector) => {
		const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
		return (await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false })).nodes[0];
	};
	const list = await node(`#${BASE}notes`);
	expect(list.role.value).toBe('region');
	expect(list.name.value).toBe('Notes');
	const ref = await node(`#${BASE}fnDesign_ref`);
	expect(ref.role.value).toBe('link');
	expect(ref.name.value).toBe('1');
	expect(ref.description.value).toBe('Notes');
	const back = await node(`#${BASE}notes_note2 a:last-child`);
	expect(back.name.value).toBe('Back to reference 2b');
});
