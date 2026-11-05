import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SuggestionListTest';
const BASE = 'ctl0_Content_';
const STATUS = `${BASE}status`;

/**
 * Returns the [value, label] of each option of a datalist.
 */
async function datalistOptions(page, id) {
	return page.evaluate((listId) => [...document.getElementById(listId).options]
		.map((option) => [option.value, option.textContent]), id);
}

/**
 * Returns the id of the datalist the browser associates with an input through
 * the `list` IDL attribute, or null when the input has none. The browser
 * returns null when the id does not resolve to a datalist or the input type
 * does not support suggestions.
 */
async function associatedList(page, id) {
	return page.evaluate((inputId) => document.getElementById(inputId).list?.id ?? null, id);
}

/**
 * TSuggestionList renders a hidden <datalist>, and the TTextBox `list`
 * attribute associates the input with it in the browser.
 */
test('TSuggestionListTestCase: template items render and associate with the input', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Suggestion List Test Case');

	await h.assertAttribute(`${BASE}city@list`, `${BASE}cities`);
	expect(await associatedList(page, `${BASE}city`)).toBe(`${BASE}cities`);
	await expect(page.locator(`#${BASE}cities`)).toBeHidden();

	// Text equal to the value renders no label; the disabled item does not render
	expect(await datalistOptions(page, `${BASE}cities`)).toEqual([
		['Paris', ''],
		['London', ''],
		['NYC', 'New York City'],
	]);
});

/**
 * A list of strings binds each string as its value; a string key binds as the
 * value with the string as its label. TextMode Search supports suggestions.
 */
test('TSuggestionListTestCase: data-bound items and a search input', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.assertAttribute(`${BASE}bound@type`, 'search');
	expect(await associatedList(page, `${BASE}bound`)).toBe(`${BASE}boundList`);
	expect(await datalistOptions(page, `${BASE}boundList`)).toEqual([
		['Rome', ''],
		['Berlin', ''],
		['SF', 'San Francisco'],
		['cafe', 'Caf\u00e9 & Bar'],
	]);
});

/**
 * A Password text box renders no `list` attribute.
 */
test('TSuggestionListTestCase: password input has no suggestions', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.assertAttribute(`${BASE}secret@type`, 'password');
	await h.assertAttribute(`${BASE}secret@list`, null);
	expect(await associatedList(page, `${BASE}secret`)).toBeNull();
});

/**
 * The input keeps its visible label; the browser exposes it as a combobox.
 */
test('TSuggestionListTestCase: input is a labeled combobox', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.getByRole('combobox', { name: 'City' })).toHaveAttribute('id', `${BASE}city`);
});

/**
 * Each text box class works with each list class on the page render.
 */
test('TSuggestionListTestCase: each text box class associates with each list class', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	// TTextBox + TSuggestionList, TTextBox + TActiveSuggestionList,
	// TActiveTextBox + TSuggestionList, TActiveTextBox + TActiveSuggestionList
	for (const [input, list] of [
		['city', 'cities'],
		['plainActive', 'activeList'],
		['activePlain', 'cities'],
		['live', 'liveList'],
	]) {
		await h.assertAttribute(`${BASE}${input}@list`, `${BASE}${list}`);
		expect(await associatedList(page, `${BASE}${input}`), input).toBe(`${BASE}${list}`);
	}
	expect(await datalistOptions(page, `${BASE}activeList`)).toEqual([
		['Berlin', ''],
		['Madrid', ''],
	]);
});

/**
 * A TTextBox keeps its association when its TActiveSuggestionList gains an
 * item during a callback.
 */
test('TSuggestionListTestCase: TTextBox with a TActiveSuggestionList updated in a callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnAddActive`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('active item added');
	expect(await datalistOptions(page, `${BASE}activeList`)).toEqual([
		['Berlin', ''],
		['Madrid', ''],
		['Vienna', ''],
	]);
	await h.assertAttribute(`${BASE}plainActive@list`, `${BASE}activeList`);
	expect(await associatedList(page, `${BASE}plainActive`)).toBe(`${BASE}activeList`);
});

/**
 * A TActiveTextBox with a TSuggestionList raises its callback and keeps the
 * association; the list is unchanged.
 */
test('TSuggestionListTestCase: TActiveTextBox with a TSuggestionList across a callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.type(`${BASE}activePlain`, 'London');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('active plain: London');
	await h.assertAttribute(`${BASE}activePlain@list`, `${BASE}cities`);
	expect(await associatedList(page, `${BASE}activePlain`)).toBe(`${BASE}cities`);
	expect(await datalistOptions(page, `${BASE}cities`)).toEqual([
		['Paris', ''],
		['London', ''],
		['NYC', 'New York City'],
	]);
});

/**
 * TActiveSuggestionList rebinds during the TActiveTextBox callback and the
 * client options are replaced when the callback completes.
 */
test('TSuggestionListTestCase: active list rebinds during a callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	expect(await associatedList(page, `${BASE}live`)).toBe(`${BASE}liveList`);
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([]);

	await h.type(`${BASE}live`, 'P');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('matches: 3');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([
		['Paris', ''],
		['Prague', ''],
		['Porto', ''],
	]);

	await h.type(`${BASE}live`, 'L');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('matches: 2');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([
		['London', ''],
		['Lisbon', ''],
	]);

	await h.type(`${BASE}live`, 'x');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('matches: 0');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([]);

	// The input stays associated with the replaced list
	expect(await associatedList(page, `${BASE}live`)).toBe(`${BASE}liveList`);
});

/**
 * An item added during a callback appends to the client options, and its label
 * escapes markup as the page render does, so it is never parsed as HTML.
 */
test('TSuggestionListTestCase: items added in a callback, markup escaped', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnAdd`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('added');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([['Madrid', 'Madrid, Spain']]);

	await h.click(`${BASE}btnMarkup`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('markup added');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([
		['Madrid', 'Madrid, Spain'],
		['markup', '<img src=x onerror="window.__datalistXss=1">'],
	]);
	await expect(page.locator(`#${BASE}liveList img`)).toHaveCount(0);
	expect(await page.evaluate(() => window.__datalistXss)).toBeUndefined();
});

/**
 * A label with entities shows the same after a callback as after the page
 * render: the bound list renders it, and the active list receives it in a callback.
 */
test('TSuggestionListTestCase: callback label entities match the page render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	const rendered = (await datalistOptions(page, `${BASE}boundList`)).find(([value]) => value === 'cafe');
	expect(rendered).toEqual(['cafe', 'Caf\u00e9 & Bar']);

	await h.click(`${BASE}btnEntity`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('entity added');
	expect(await datalistOptions(page, `${BASE}liveList`)).toEqual([rendered]);
});

/**
 * TActiveTextBox sets and removes the client `list` attribute when its
 * SuggestionList changes during a callback.
 */
test('TSuggestionListTestCase: active text box switches and detaches its list', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnSwap`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('using cities');
	await h.assertAttribute(`${BASE}live@list`, `${BASE}cities`);
	expect(await associatedList(page, `${BASE}live`)).toBe(`${BASE}cities`);

	await h.click(`${BASE}btnDetach`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('detached');
	await h.assertAttribute(`${BASE}live@list`, null);
	expect(await associatedList(page, `${BASE}live`)).toBeNull();
});

/**
 * A full postback posts the typed value, and the lists restore their items
 * from view state.
 */
test('TSuggestionListTestCase: postback keeps the items and posts the value', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.type(`${BASE}city`, 'Paris');
	await Promise.all([
		page.waitForLoadState('load'),
		page.waitForNavigation(),
		h.click(`${BASE}btnSubmit`),
	]);
	await expect(page.locator(`#${STATUS}`)).toHaveText('submitted: Paris');
	await h.assertValue(`${BASE}city`, 'Paris');
	expect(await datalistOptions(page, `${BASE}cities`)).toEqual([
		['Paris', ''],
		['London', ''],
		['NYC', 'New York City'],
	]);
	expect(await datalistOptions(page, `${BASE}boundList`)).toEqual([
		['Rome', ''],
		['Berlin', ''],
		['SF', 'San Francisco'],
		['cafe', 'Caf\u00e9 & Bar'],
	]);
	expect(await datalistOptions(page, `${BASE}activeList`)).toEqual([
		['Berlin', ''],
		['Madrid', ''],
	]);
	expect(await associatedList(page, `${BASE}plainActive`)).toBe(`${BASE}activeList`);
	expect(await associatedList(page, `${BASE}activePlain`)).toBe(`${BASE}cities`);
});
