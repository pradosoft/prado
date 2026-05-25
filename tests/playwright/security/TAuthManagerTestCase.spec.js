import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

const BASE     = GENERIC_BASE_URL + 'security/index.php';
const HOME     = BASE + '?page=Home';
const LOGIN    = BASE + '?page=Login';
const MEMBERS  = BASE + '?page=Members.Members';
const ADMIN    = BASE + '?page=Admin.AdminOnly';

// ── Helpers ───────────────────────────────────────────────────────────────────

/** Navigate to LOGIN, fill credentials, submit, wait for navigation. */
async function loginAs(page, username, password, remember = false) {
	await page.goto(LOGIN);
	await page.fill('#username', username);
	await page.fill('#password', password);
	if (remember) {
		await page.check('#remember');
	}
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.click('#login-button'),
	]);
}

/** Navigate to Home with action=logout and wait for the redirect back to Home. */
async function logout(page) {
	await page.goto(HOME + '&action=logout');
	// After logout, TAuthManager destroys the session and the Home page
	// redirects back to itself; wait for the final settled load.
	await page.waitForLoadState('load');
}

// ── Tests ─────────────────────────────────────────────────────────────────────

test('TAuthManagerTestCase', async ({ page }) => {
	// ── 1. Guest sees Home with no authenticated user ──────────────────────
	// Tests: TAuthManager.doAuthentication() restores a guest when there is no
	// session data; TAuthManager.onAuthenticate() fires without error.
	await page.goto(HOME);
	expect(await page.locator('#current-user').textContent()).toBe('Guest');

	// ── 2. Guest access to protected page → redirect to Login ─────────────
	// Tests: TAuthManager.doAuthorization() sets 401 when a rule denies the user;
	// TAuthManager.leave() detects 401 and redirects to the configured LoginPage.
	await page.goto(MEMBERS);
	expect(page.url()).toContain('page=Login');

	// ── 3. Guest access to admin-only page → redirect to Login ────────────
	// Tests: role-based deny (<allow roles="Administrator" /><deny users="*" />)
	// triggers the same 401 → redirect flow for guests.
	await page.goto(ADMIN);
	expect(page.url()).toContain('page=Login');

	// ── 4. Login with invalid credentials → error shown, stay on Login ────
	// Tests: TAuthManager.login() returns false; onLoginFailed is raised.
	await page.goto(LOGIN);
	await page.fill('#username', 'Joe');
	await page.fill('#password', 'wrongpassword');
	await Promise.all([
		page.waitForNavigation({ waitUntil: 'load' }),
		page.click('#login-button'),
	]);
	expect(page.url()).toContain('page=Login');
	expect(await page.locator('#login-error').textContent()).toContain('Invalid');

	// ── 5. Successful login with Joe (Writer, not Administrator) ───────────
	// Tests: TAuthManager.login() calls updateSessionUser() which stores user
	// data in the session (only effective outside CLI — the whole point of
	// having functional tests for this path).
	await loginAs(page, 'Joe', 'demo');
	// Successful login redirects to Home (no ReturnUrl stored yet)
	expect(page.url()).toContain('page=Home');
	expect(await page.locator('#current-user').textContent()).toBe('joe');

	// ── 6. Session persistence: onAuthenticate restores user on next request
	// Tests: user data serialised in session by updateSessionUser() is read back
	// by onAuthenticate() on each subsequent request.
	await page.goto(HOME);
	expect(await page.locator('#current-user').textContent()).toBe('joe');

	// ── 7. Authenticated (Joe) accesses Members page → allowed ───────────
	// Tests: doAuthorization() with <deny users="?"> allows authenticated users.
	await page.goto(MEMBERS);
	expect(page.url()).toContain('Members');
	expect(await page.locator('#user-info').textContent()).toBe('joe');

	// ── 8. Joe accesses Admin-only page → denied ─────────────────────────
	// Tests: doAuthorization() with <allow roles="Administrator"><deny users="*">
	// blocks a non-admin authenticated user.  Full chain:
	//   Admin → 401 → leave() → redirect to Login
	//   Login.onLoad: Joe is already authenticated → redirect to Home
	// Joe never reaches the Admin page; he ends up back at Home.
	await page.goto(ADMIN);
	expect(page.url()).not.toContain('Admin');
	// Session is intact — Joe is still logged in.
	expect(await page.locator('#current-user').textContent()).toBe('joe');

	// ── 9. Logout → session cleared, user reverts to Guest ─────────────────
	// Tests: TAuthManager.logout() destroys the session; the next request sees
	// no session data and gets a guest user.
	await logout(page);
	expect(await page.locator('#current-user').textContent()).toBe('Guest');

	// ── 10. ReturnUrl: visit protected page as guest → login → redirected back
	// Tests: leave() stores the requested URL in the session (setReturnUrl);
	// Login.php reads it via getReturnUrl() and redirects there after login.
	await page.goto(MEMBERS); // → 401 → leave() stores ReturnUrl → Login
	expect(page.url()).toContain('page=Login');

	await loginAs(page, 'Joe', 'demo');
	// Should have been redirected to the originally-requested Members page.
	expect(page.url()).toContain('Members');
	expect(await page.locator('#user-info').textContent()).toBe('joe');

	// ── 11. Login as Administrator (John) ──────────────────────────────────
	await logout(page);
	await loginAs(page, 'John', 'demo');
	expect(page.url()).toContain('page=Home');
	expect(await page.locator('#current-user').textContent()).toBe('john');

	// ── 12. John accesses Admin-only page → allowed ────────────────────────
	await page.goto(ADMIN);
	expect(page.url()).toContain('Admin');
	expect(await page.locator('#user-info').textContent()).toBe('john');

	// ── 13. switchUser: John switches active session to Joe ────────────────
	// Tests: TAuthManager.switchUser() calls updateSessionUser() with a new user,
	// changing the active session without requiring that user's password.
	await page.goto(ADMIN + '&action=switch&to=joe');
	await page.waitForLoadState('load');
	// AdminOnly.php redirects to Home after a successful switch.
	expect(page.url()).toContain('page=Home');
	expect(await page.locator('#current-user').textContent()).toBe('joe');

	// ── 14. Auto-login cookie: user restored from cookie after session loss ─
	// Tests: onAuthenticate() falls back to getUserFromCookie() when the session
	// holds no user data (AllowAutoLogin=true in application.xml).
	await logout(page);
	await loginAs(page, 'Joe', 'demo', true /* remember = 30-day cookie */);
	expect(page.url()).toContain('page=Home');

	// Simulate session loss: delete the PHPSESSID cookie while keeping the
	// auto-login cookie that was set by login($expire>0).
	const allCookies = await page.context().cookies();
	const nonSessionCookies = allCookies.filter(
		(c) => c.name.toLowerCase() !== 'phpsessid'
	);
	await page.context().clearCookies();
	await page.context().addCookies(nonSessionCookies);

	// After session loss, onAuthenticate() should restore Joe from the cookie.
	await page.goto(HOME);
	expect(await page.locator('#current-user').textContent()).toBe('joe');

	// Clean up
	await logout(page);
	expect(await page.locator('#current-user').textContent()).toBe('Guest');
});
