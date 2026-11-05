import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

const BASE = GENERIC_BASE_URL + 'security/index.php';
const HOME = BASE + '?page=Home';
const LOGIN = BASE + '?page=Login';

/** Fills the harness login form and submits it. */
async function submitLogin(page, username, password) {
	await page.fill('#username', username);
	await page.fill('#password', password);
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.click('#login-button'),
	]);
}

/** Logs out through the harness Home page. */
async function logout(page) {
	await page.goto(HOME + '&action=logout');
}

/**
 * constructLoginUrl() puts the current request in the ReturnUrl parameter, and
 * getReturnUrl() returns there after login without a session value.
 */
test('TAuthManagerReturnUrlTestCase: login link returns to the page', async ({ page }) => {
	await page.goto(HOME + '&from=link');
	const href = await page.locator('#login-link').getAttribute('href');
	const loginUrl = new URL(href, page.url());
	expect(loginUrl.searchParams.get('page')).toBe('Login');
	expect(loginUrl.searchParams.get('ReturnUrl')).toContain('page=Home&from=link');

	await page.click('#login-link');
	await submitLogin(page, 'Joe', 'demo');
	expect(new URL(page.url()).searchParams.get('from')).toBe('link');
	await expect(page.locator('#current-user')).toHaveText('joe');
	await logout(page);
});

/**
 * A ReturnUrl parameter to another host is ignored, and login falls back to Home.
 */
test('TAuthManagerReturnUrlTestCase: foreign ReturnUrl is ignored', async ({ page }) => {
	for (const target of ['//evil.example/', 'https://evil.example/', 'javascript:alert(1)', '/\\evil.example']) {
		await page.goto(LOGIN + '&ReturnUrl=' + encodeURIComponent(target));
		await expect(page.locator('#return-url')).toHaveValue('');
		await submitLogin(page, 'Joe', 'demo');
		const url = new URL(page.url());
		expect(url.origin, target).toBe(new URL(BASE).origin);
		expect(url.searchParams.get('page'), target).toBe('Home');
		await logout(page);
	}
});
