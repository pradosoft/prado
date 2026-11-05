import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=TermTest';
const BASE = 'ctl0_Content_';

/**
 * Returns the tag name, text, and title of each element with the tag inside the element.
 */
async function marks(page, selector, tag) {
	return page.locator(selector).evaluate(
		(el, t) => [...el.getElementsByTagName(t)].map((m) => [m.textContent, m.getAttribute('title')]),
		tag
	);
}

/**
 * TAbbr, TDfn, and TVar render their element and mark the other occurrences of
 * their terms in their parent.
 */
test('TTermTestCase: page render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Term Test Case');

	const expansion = 'HyperText Markup Language';
	expect(await marks(page, '#intro', 'abbr')).toEqual([
		['HTML', expansion],
		['HTML', expansion],
		['CSS', 'Cascading Style Sheets'],
	]);
	await expect(page.locator(`#${BASE}html`)).toHaveAttribute('title', expansion);
	expect(await marks(page, '#learn', 'abbr')).toEqual([['HTML', expansion]]);
	expect(await marks(page, '#help', 'abbr')).toEqual([['HTML', expansion]]);

	// Code, exempt markup, text outside the parent, and a term past its limit stay text
	expect(await marks(page, '#code', 'abbr')).toEqual([]);
	expect(await marks(page, '#exempt', 'abbr')).toEqual([]);
	expect(await marks(page, '#outside', 'abbr')).toEqual([]);
	expect(await marks(page, '#again', 'abbr')).toEqual([]);

	// A TAbbr without Text renders no element of its own
	await expect(page.locator(`#${BASE}css`)).toHaveCount(0);

	// A term has one defining instance
	expect(await marks(page, '#define', 'dfn')).toEqual([['render filter', null]]);
	expect(await marks(page, '#hold', 'dfn')).toEqual([['placeholder', null]]);

	expect(await marks(page, `#${BASE}formula`, 'var')).toEqual([
		['w', null],
		['h', null],
	]);
	await expect(page.locator(`#${BASE}glossary`)).toContainText('pages use HTML and CSS.');
});

/**
 * Marking keeps the text, so the names of links and buttons holding a term are
 * unchanged. Each definition is a term.
 */
test('TTermTestCase: accessible names and roles', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.getByRole('link', { name: 'Learn HTML', exact: true })).toHaveAttribute('id', 'learn');
	await expect(page.getByRole('button', { name: 'HTML help', exact: true })).toHaveAttribute('id', 'help');
	await expect(page.getByRole('term')).toHaveText(['render filter', 'placeholder']);
	await expect(page.locator('#intro')).toHaveText('HTML pages use HTML and CSS.');
});

/**
 * A TAbbr inside a TActivePanel re-rendered in a callback marks the new text of
 * the panel, except in its exempt child.
 */
test('TTermTestCase: callback re-render marks terms', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	expect(await marks(page, `#${BASE}live`, 'abbr')).toEqual([]);
	await h.click(`${BASE}btnUpdate`);
	await h.waitForAjaxCalls();
	await expect(page.locator('#note')).toHaveText('Each URL is new.');
	await expect(page.locator('#noteExempt')).toHaveText('This URL is exempt.');
	expect(await marks(page, '#note', 'abbr')).toEqual([['URL', 'Uniform Resource Locator']]);
	expect(await marks(page, '#noteExempt', 'abbr')).toEqual([]);
});

/**
 * A TActivePanel re-rendered alone in a callback is a fragment of the TPanel
 * around it: the TAbbr of the TPanel marks it, and the TDfn, with its MarkLimit,
 * does not. A fragment inside a `<pre>` stays unmarked, as in a full render.
 */
test('TTermTestCase: callback fragment marks terms of an ancestor', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnInner`);
	await h.waitForAjaxCalls();
	await expect(page.locator('#innerNote')).toHaveText('The API runs a callback.');
	expect(await marks(page, '#innerNote', 'abbr')).toEqual([['API', 'Application Programming Interface']]);
	expect(await marks(page, '#innerNote', 'dfn')).toEqual([]);
	await expect(page.locator('#preNote')).toHaveText('The API is literal here.');
	expect(await marks(page, '#preNote', 'abbr')).toEqual([]);
});

/**
 * A TAbbr with a DepthLimit of 2 marks the text of the controls at most two levels
 * below its parent: each TPanel and TActiveLabel is a level. A TActivePanel
 * re-rendered alone in a callback is marked to the same levels.
 */
test('TTermTestCase: depth limit', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const dom = [['DOM', 'Document Object Model']];
	expect(await marks(page, '#levelTop', 'abbr')).toEqual(dom);
	expect(await marks(page, '#levelOneNote', 'abbr')).toEqual(dom);
	expect(await marks(page, '#levelTwoNote', 'abbr')).toEqual(dom);
	expect(await marks(page, '#levelThreeNote', 'abbr')).toEqual([]);
	expect(await page.locator(`#${BASE}levels`).evaluate((el) => el.innerHTML.includes('prado-depth'))).toBe(false);

	await h.click(`${BASE}btnLevels`);
	await h.waitForAjaxCalls();
	await expect(page.locator('#levelLiveNote')).toHaveText('The DOM changed.');
	await expect(page.locator('#levelDeepNote')).toHaveText('A deeper DOM change.');
	expect(await marks(page, '#levelLiveNote', 'abbr')).toEqual(dom);
	expect(await marks(page, '#levelDeepNote', 'abbr')).toEqual([]);
	expect(await page.locator(`#${BASE}levels`).evaluate((el) => el.innerHTML.includes('prado-depth'))).toBe(false);
});
