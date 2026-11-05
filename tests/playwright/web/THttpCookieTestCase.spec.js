import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=HttpCookieTest';

test('THttpCookieTestCase', async ({ page, context }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── Start with a clean cookie jar ──
	await context.clearCookies();

	// ── set-basic: adds a plain name/value cookie ──
	await h.url(PAGE_URL + '&action=set-basic');
	await h.assertSourceContains('THttpCookie Test');

	let cookies = await context.cookies();
	const basicCookie = cookies.find(c => c.name === 'basic_cookie');
	expect(basicCookie, 'basic_cookie should be set').toBeDefined();
	expect(basicCookie.value).toBe('basic_value');

	// ── set-httponly: HttpOnly flag prevents JavaScript access ──
	await context.clearCookies();
	await h.url(PAGE_URL + '&action=set-httponly');

	cookies = await context.cookies();
	const httpOnlyCookie = cookies.find(c => c.name === 'httponly_cookie');
	expect(httpOnlyCookie, 'httponly_cookie should be set').toBeDefined();
	expect(httpOnlyCookie.value).toBe('secret_value');
	expect(httpOnlyCookie.httpOnly).toBe(true);

	// ── set-expiry: cookie has a future expiry timestamp ──
	await context.clearCookies();
	await h.url(PAGE_URL + '&action=set-expiry');

	cookies = await context.cookies();
	const expiryCookie = cookies.find(c => c.name === 'expiry_cookie');
	expect(expiryCookie, 'expiry_cookie should be set').toBeDefined();
	expect(expiryCookie.value).toBe('expires_value');
	// Expires should be roughly 1 hour from now (> current time)
	expect(expiryCookie.expires).toBeGreaterThan(Date.now() / 1000);

	// ── set-samesite-lax: SameSite attribute is propagated ──
	await context.clearCookies();
	await h.url(PAGE_URL + '&action=set-samesite-lax');

	cookies = await context.cookies();
	const samesiteCookie = cookies.find(c => c.name === 'samesite_cookie');
	expect(samesiteCookie, 'samesite_cookie should be set').toBeDefined();
	expect(samesiteCookie.value).toBe('lax_value');
	expect(samesiteCookie.sameSite).toBe('Lax');

	// ── remove: removeCookie() deletes the cookie from the browser ──
	// First set the cookie
	await context.clearCookies();
	await h.url(PAGE_URL + '&action=set-basic');
	cookies = await context.cookies();
	expect(cookies.find(c => c.name === 'basic_cookie')).toBeDefined();

	// Then remove it — server sends Set-Cookie with expired timestamp
	await h.url(PAGE_URL + '&action=remove&name=basic_cookie');
	cookies = await context.cookies();
	// The cookie should be gone (expired cookies are dropped by the browser)
	const removed = cookies.find(c => c.name === 'basic_cookie');
	expect(!removed || removed.expires < Date.now() / 1000).toBe(true);

	// ── cookie value is readable server-side on subsequent requests ──
	// Set a cookie, then navigate again — $_COOKIE on next load should contain it
	await context.clearCookies();
	await h.url(PAGE_URL + '&action=set-basic');
	// Second request — cookie is now in the request
	await h.url(PAGE_URL);
	const html = await h.source();
	// The cookie-display div shows $_COOKIE as JSON
	expect(html).toContain('basic_cookie');
	expect(html).toContain('basic_value');
});
