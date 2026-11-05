import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'security/index.php';
const PAGE_URL = BASE + '?page=AuthViewTest';
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

/** Clicks a postback button and waits for the page to reload. */
async function postBack(page, name) {
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.getByRole('button', { name }).click(),
	]);
}

/**
 * A guest sees the guest case and the single guest view; no other case is in the page.
 */
test('TAuthViewTestCase: guest', async ({ page }) => {
	await page.goto(PAGE_URL);
	await expect(page.getByRole('button', { name: 'Sign in as Joe' })).toBeVisible();
	await expect(page.locator('#single')).toHaveText('Guests only');
	await expect(page.locator('#welcome')).toHaveCount(0);
	await expect(page.locator('#admin-link')).toHaveCount(0);
	await expect(page.locator(`#${ID}Count`)).toHaveCount(0);
});

/**
 * A login during a postback renders the new case on the same response, and the
 * controls of the chosen case keep their view state across postbacks.
 */
test('TAuthViewTestCase: login, view state, and logout by postback', async ({ page }) => {
	await page.goto(PAGE_URL);
	await postBack(page, 'Sign in as Joe');
	await expect(page.locator('#welcome')).toHaveText('Welcome, joe');
	await expect(page.getByRole('button', { name: 'Sign in as Joe' })).toHaveCount(0);
	await expect(page.locator('#single')).toHaveText('');
	await expect(page.locator(`#${ID}Counter`)).toHaveText('0');

	await postBack(page, 'Count');
	await postBack(page, 'Count');
	await expect(page.locator(`#${ID}Counter`)).toHaveText('2');

	await postBack(page, 'Sign out');
	await expect(page.getByRole('button', { name: 'Sign in as Joe' })).toBeVisible();
	await expect(page.locator('#welcome')).toHaveCount(0);
});

/**
 * An administrator gets the admin case before the fallback; a postback in it works.
 */
test('TAuthViewTestCase: role case', async ({ page }) => {
	await loginAs(page, 'John', 'demo');
	await page.goto(PAGE_URL);
	await expect(page.locator('#admin-link')).toHaveText('Administration');
	await expect(page.locator('#welcome')).toHaveCount(0);
	await expect(page.getByRole('button', { name: 'Purge' })).toHaveAttribute('name', 'ctl0$Content$Purge');
	await postBack(page, 'Purge');
	await expect(page.locator(`#${ID}Purged`)).toHaveText('purged');
	await page.goto(BASE + '?page=Home&action=logout');
});

/**
 * A crafted postback to a button in an unchosen case raises no event.  The field name
 * is the Purge button's name, checked in the role case test.
 */
test('TAuthViewTestCase: unchosen case ignores a crafted postback', async ({ page }) => {
	await loginAs(page, 'Joe', 'demo');
	await page.goto(PAGE_URL);
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.evaluate(() => {
			const form = document.forms[0];
			const field = document.createElement('input');
			field.type = 'hidden';
			field.name = 'ctl0$Content$Purge';
			field.value = 'Purge';
			form.appendChild(field);
			form.submit();
		}),
	]);
	await expect(page.locator('#welcome')).toHaveText('Welcome, joe');
	await expect(page.locator(`#${ID}Purged`)).toHaveText('');
	await page.goto(BASE + '?page=Home&action=logout');
});

/**
 * TAuthView adds no element: the chosen content keeps its own structure and roles,
 * and unchosen content is absent from the accessibility tree.
 */
test('TAuthViewTestCase: accessibility', async ({ page }) => {
	await page.goto(PAGE_URL);
	await expect(page.locator('#nav')).toMatchAriaSnapshot('- list:\n  - listitem:\n    - button "Sign in as Joe"');
	await expect(page.locator('#nav > *')).toHaveCount(1);
	await expect(page.locator('#nav > li')).toHaveCount(1);

	await postBack(page, 'Sign in as Joe');
	await expect(page.locator('#nav')).toMatchAriaSnapshot(
		'- list:\n  - listitem: Welcome, joe\n  - listitem:\n    - button "Count"\n    - text: "0"\n  - listitem:\n    - button "Sign out"'
	);
	await expect(page.locator('#nav > *')).toHaveCount(3);
	await postBack(page, 'Sign out');
});
