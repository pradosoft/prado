import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const REDIRECT_PAGE = GENERIC_BASE_URL + 'web/index.php?page=HttpRedirectTest';

test('THttpResponseRedirectTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);

	// ── 302 redirect: Playwright follows the redirect automatically ──
	// Capture all responses so we can inspect the intermediate 302.
	const responses = [];
	page.on('response', r => responses.push({ url: r.url(), status: r.status() }));

	await page.goto(REDIRECT_PAGE + '&action=redirect302');
	await page.waitForLoadState('networkidle');

	// The intermediate response must be a 302
	const redirect = responses.find(r => r.status === 302);
	expect(redirect, '302 response should be captured').toBeDefined();

	// After following the redirect we land on the target page
	expect(page.url()).toContain('HttpRedirectTarget');
	await h.assertSourceContains('Redirect Target');
	await h.assertSourceContains('redirect-ok');

	// ── Query params survive through constructUrl() ──
	responses.length = 0; // reset
	await page.goto(REDIRECT_PAGE + '&action=redirect302-labeled');
	await page.waitForLoadState('networkidle');

	expect(page.url()).toContain('HttpRedirectTarget');
	await h.assertSourceContains('redirect-ok');
	// The ?from= param should be forwarded
	await h.assertSourceContains('from:redirect302');

	// ── No redirect when no action param is given ──
	await page.goto(REDIRECT_PAGE);
	expect(page.url()).toContain('HttpRedirectTest');
	await h.assertSourceContains('THttpResponse Redirect Test');
});
