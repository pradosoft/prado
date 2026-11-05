import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SearchTest';
const BASE = 'ctl0_Content_';

/**
 * TSearch renders the `<search>` element, which the browser exposes as a
 * labeled search landmark holding the labeled query input.
 */
test('TSearchTestCase: search element is a labeled search landmark', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Search Test Case');

	const site = page.locator(`#${BASE}site`);
	expect(await site.evaluate((el) => el.tagName.toLowerCase())).toBe('search');
	await h.assertAttribute(`${BASE}site@role`, null);
	await expect(page.getByRole('search', { name: 'Site' })).toHaveAttribute('id', `${BASE}site`);
	await expect(site).toHaveCSS('display', 'block');

	const query = page.getByRole('search', { name: 'Site' }).getByRole('searchbox', { name: 'Search the site' });
	await expect(query).toHaveAttribute('id', `${BASE}query`);
});

/**
 * A TSearch with another TagName renders `role="search"` and keeps the landmark.
 */
test('TSearchTestCase: another tag keeps the search landmark', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.assertAttribute(`${BASE}filter@role`, 'search');
	expect(await page.locator(`#${BASE}filter`).evaluate((el) => el.tagName.toLowerCase())).toBe('div');
	await expect(page.getByRole('search', { name: 'Products' })).toHaveAttribute('id', `${BASE}filter`);
	await expect(page.getByRole('search')).toHaveCount(2);
});

/**
 * The controls inside TSearch post back as usual.
 */
test('TSearchTestCase: search posts back the query', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);

	await h.type(`${BASE}query`, 'prado');
	await Promise.all([
		page.waitForLoadState('load'),
		page.waitForNavigation(),
		h.click(`${BASE}btnSearch`),
	]);
	await expect(page.locator(`#${BASE}status`)).toHaveText('searched: prado');
	await h.assertValue(`${BASE}query`, 'prado');
});
