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
 * its parent TPanel.
 */
test('TTermTestCase: callback re-render marks terms', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	expect(await marks(page, `#${BASE}live`, 'abbr')).toEqual([]);
	await h.click(`${BASE}btnUpdate`);
	await h.waitForAjaxCalls();
	await expect(page.locator('#note')).toHaveText('Each URL is new.');
	expect(await marks(page, `#${BASE}live`, 'abbr')).toEqual([['URL', 'Uniform Resource Locator']]);
});
