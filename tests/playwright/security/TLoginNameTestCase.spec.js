import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'security/index.php';
const PAGE_URL = BASE + '?page=LoginNameTest';
const LOGIN = BASE + '?page=Login';
const ID = 'ctl0_Content_';

/** Signs in through the harness login page. */
async function loginAs(page, username, password) {
	await page.goto(LOGIN);
	await page.fill('#username', username);
	await page.fill('#password', password);
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.click('#login-button'),
	]);
}

/**
 * A guest sees no name, unless DisplayGuest is true.
 */
test('TLoginNameTestCase: guest', async ({ page }) => {
	await page.goto(PAGE_URL);
	await expect(page.locator(`#${ID}Plain`)).toHaveCount(0);
	await expect(page.locator(`#${ID}Formatted`)).toHaveCount(0);
	await expect(page.locator('#plain')).toHaveText('User:');
	await expect(page.locator(`#${ID}Guest`)).toHaveText('Guest');
});

/**
 * A signed-in user's name renders in a span, with FormatString around it.
 */
test('TLoginNameTestCase: signed-in user', async ({ page }) => {
	await loginAs(page, 'Joe', 'demo');
	await page.goto(PAGE_URL);

	const plain = page.locator(`#${ID}Plain`);
	await expect(plain).toHaveText('joe');
	await expect(plain).toHaveClass('login-name');
	expect(await plain.evaluate((el) => el.tagName.toLowerCase())).toBe('span');

	await expect(page.locator(`#${ID}Formatted`)).toHaveText('Signed in as joe');
	await expect(page.locator(`#${ID}Formatted strong`)).toHaveText('joe');
	await expect(page.locator(`#${ID}Guest`)).toHaveText('joe');

	await page.goto(BASE + '?page=Home&action=logout');
});

/**
 * The name is read as text in the paragraph; the span adds no role, name, or live region.
 */
test('TLoginNameTestCase: accessibility', async ({ page }) => {
	await loginAs(page, 'Joe', 'demo');
	await page.goto(PAGE_URL);

	for (const id of ['Plain', 'Formatted', 'Guest']) {
		const span = page.locator(`#${ID}${id}`);
		await expect(span, id).not.toHaveAttribute('role');
		await expect(span, id).not.toHaveAttribute('aria-live');
		await expect(span, id).not.toHaveAttribute('aria-label');
	}
	await expect(page.locator('#plain')).toMatchAriaSnapshot('- paragraph: "User: joe"');
	await expect(page.locator('#formatted')).toMatchAriaSnapshot('- paragraph:\n  - text: Signed in as\n  - strong: joe');

	await page.goto(BASE + '?page=Home&action=logout');
});

/**
 * Chromium's accessibility tree exposes the span as a generic element without its own name.
 */
test('TLoginNameTestCase: browser accessibility role', async ({ page, browserName }) => {
	test.skip(browserName !== 'chromium', 'reads the Chromium accessibility tree over CDP');
	await loginAs(page, 'Joe', 'demo');
	await page.goto(PAGE_URL);

	const cdp = await page.context().newCDPSession(page);
	const { root } = await cdp.send('DOM.getDocument');
	const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector: `#${ID}Plain` });
	const { nodes } = await cdp.send('Accessibility.getPartialAXTree', { nodeId, fetchRelatives: false });
	expect(nodes[0].role.value).toBe('generic');
	expect(nodes[0].name?.value ?? '').toBe('');

	await page.goto(BASE + '?page=Home&action=logout');
});
