import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=OutputTest';
const BASE = 'ctl0_Content_';
const STATUS = `${BASE}status`;

/**
 * Returns the ids in the `htmlFor` token list of an output.
 */
async function forIds(page, id) {
	return page.evaluate((outputId) => [...document.getElementById(outputId).htmlFor], id);
}

/**
 * TOutput renders an `<output>` with its name, the `for` client IDs of its
 * visible source controls, and its text.
 */
test('TOutputTestCase: page render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Output Test Case');

	const total = page.locator(`#${BASE}total`);
	expect(await total.evaluate((el) => el.tagName.toLowerCase())).toBe('output');
	await expect(total).toHaveText('6.00');
	expect(await total.evaluate((el) => el.value)).toBe('6.00');
	expect(await forIds(page, `${BASE}total`)).toEqual([`${BASE}price`, `${BASE}quantity`]);

	// The invisible source control is left out; entities decode as in any page text
	await expect(page.locator(`#${BASE}note`)).toHaveText('Café & Bar');
	expect(await forIds(page, `${BASE}note`)).toEqual([`${BASE}price`]);

	// The name puts the output in the form's elements; it is not posted
	const name = await total.getAttribute('name');
	expect(await page.evaluate((n) => document.forms[0].elements[n]?.id, name)).toBe(`${BASE}total`);
});

/**
 * The browser exposes each output as a status live region, named by its TLabel.
 */
test('TOutputTestCase: output is a labeled status', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.getByRole('status', { name: 'Total' })).toHaveAttribute('id', `${BASE}total`);
	await expect(page.getByRole('status', { name: 'Note' })).toHaveAttribute('id', `${BASE}note`);
	await expect(page.getByRole('status', { name: 'Empty' })).toHaveAttribute('id', `${BASE}empty`);
	await h.assertAttribute(`${BASE}total@role`, null);
	await h.assertAttribute(`${BASE}total@aria-live`, null);
});

/**
 * TActiveOutput replaces its text when a source text box raises its callback.
 */
test('TOutputTestCase: active output recalculates in a callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.type(`${BASE}quantity`, '5');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('calculated');
	await expect(page.locator(`#${BASE}total`)).toHaveText('10.00');
	expect(await forIds(page, `${BASE}total`)).toEqual([`${BASE}price`, `${BASE}quantity`]);
});

/**
 * The output rendered empty is in the page and receives its text in a callback.
 */
test('TOutputTestCase: empty output filled in a callback', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await expect(page.locator(`#${BASE}empty`)).toHaveText('');
	await h.click(`${BASE}btnFill`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('filled');
	await expect(page.locator(`#${BASE}empty`)).toHaveText('filled');
});

/**
 * Callback text escapes markup and decodes entities as the page render does.
 */
test('TOutputTestCase: callback text matches the page render', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnMarkup`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('markup');
	await expect(page.locator(`#${BASE}total`)).toHaveText('<img src=x onerror="window.__outputXss=1">');
	await expect(page.locator(`#${BASE}total img`)).toHaveCount(0);
	expect(await page.evaluate(() => window.__outputXss)).toBeUndefined();

	await h.click(`${BASE}btnEntity`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('entity');
	await expect(page.locator(`#${BASE}total`)).toHaveText(await page.locator(`#${BASE}note`).textContent());
});

/**
 * TActiveOutput updates and removes the client `for` attribute in a callback.
 */
test('TOutputTestCase: active output changes its source controls', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.click(`${BASE}btnPrice`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('price only');
	expect(await forIds(page, `${BASE}total`)).toEqual([`${BASE}price`]);

	await h.click(`${BASE}btnDetach`);
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${STATUS}`)).toHaveText('detached');
	await h.assertAttribute(`${BASE}total@for`, null);
});

/**
 * A full postback restores the text set in a callback from view state.
 */
test('TOutputTestCase: postback keeps the text', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.type(`${BASE}price`, '4');
	await h.waitForAjaxCalls();
	await expect(page.locator(`#${BASE}total`)).toHaveText('12.00');
	await Promise.all([
		page.waitForLoadState('load'),
		page.waitForNavigation(),
		h.click(`${BASE}btnSubmit`),
	]);
	await expect(page.locator(`#${STATUS}`)).toHaveText('submitted');
	await expect(page.locator(`#${BASE}total`)).toHaveText('12.00');
	await expect(page.locator(`#${BASE}note`)).toHaveText('Café & Bar');
});
