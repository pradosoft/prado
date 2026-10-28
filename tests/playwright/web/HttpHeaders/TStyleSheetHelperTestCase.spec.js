import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../../helpers.js';

/**
 * Functional tests for the CSP nonce and Subresource Integrity of styles
 * ({@see TStyleSheetHelper}).
 *
 * StyleCspPage requires the per-request nonce for style elements
 * (`style-src-elem 'self' NONCE; style-src-attr 'unsafe-inline'`). TStyleSheet CSS,
 * registerStyleSheet() CSS, and CSS registered in a callback carry the nonce and
 * apply; a raw `<style>` without it is blocked; style attributes still apply.
 *
 * StyleSriPage pins an SRI value for a cross-origin stylesheet through
 * TIntegrityManager and renders it with TStyleSheetHelper::renderStyleSheetFile().
 */
const BASE_URL = GENERIC_BASE_URL + 'HttpHeaders/index.php';
const CSP_PAGE = BASE_URL + '?page=StyleCspPage';
const SRI_PAGE = BASE_URL + '?page=StyleSriPage';

/**
 * Records the CSP violations of the page; an init script runs before the page's
 * policy applies to its own scripts.
 */
async function recordViolations(page) {
	await page.addInitScript(() => {
		window.__cspViolations = [];
		document.addEventListener('securitypolicyviolation', (e) => {
			window.__cspViolations.push(e.violatedDirective + ' ' + (e.sample || e.blockedURI || ''));
		});
	});
}

const color = (page, id) => page.locator(`#${id}`).evaluate((el) => getComputedStyle(el).color);

test.describe('TStyleSheetHelperTestCase', () => {

	test('nonced style elements apply and an unnonced one is blocked', async ({ page }) => {
		await recordViolations(page);
		const response = await page.goto(CSP_PAGE, { waitUntil: 'load' });
		const csp = response.headers()['content-security-policy'];
		expect(csp).toMatch(/style-src-elem 'self' 'nonce-[^']+'/);
		expect(csp).toContain("style-src-attr 'unsafe-inline'");

		expect(await color(page, 'stylesheet-marker')).toBe('rgb(0, 128, 0)');
		expect(await color(page, 'registered-marker')).toBe('rgb(0, 0, 255)');
		expect(await color(page, 'attr-marker')).toBe('rgb(1, 2, 3)');
		expect(await color(page, 'raw-marker')).not.toBe('rgb(255, 0, 0)');

		const nonce = csp.match(/'nonce-([^']+)'/)[1];
		const styleNonces = await page.locator('style').evaluateAll((els) => els.map((el) => el.nonce));
		expect(styleNonces.filter((n) => n === nonce).length).toBeGreaterThanOrEqual(2);

		const violations = await page.evaluate(() => window.__cspViolations);
		expect(violations.filter((v) => v.startsWith('style-src-elem'))).toHaveLength(1);
		expect(violations.filter((v) => !v.startsWith('style-src-elem'))).toEqual([]);
	});

	test('CSS registered in a callback carries the page nonce and applies', async ({ page }) => {
		await recordViolations(page);
		await page.goto(CSP_PAGE, { waitUntil: 'load' });
		expect(await color(page, 'callback-marker')).not.toBe('rgb(128, 0, 128)');

		await page.locator('input[type=submit][value="Add style"]').click();
		await expect(page.locator('span', { hasText: 'styled' })).toHaveCount(1);
		await expect.poll(() => color(page, 'callback-marker')).toBe('rgb(128, 0, 128)');

		const violations = await page.evaluate(() => window.__cspViolations);
		expect(violations.filter((v) => !v.startsWith('style-src-elem'))).toEqual([]);
		expect(violations.filter((v) => v.startsWith('style-src-elem'))).toHaveLength(1);
	});

	test('matching stylesheet integrity applies the stylesheet', async ({ page }) => {
		await page.goto(SRI_PAGE + '&action=correct', { waitUntil: 'load' });
		const link = page.locator('link[href*="sri-style.php"]');
		await expect(link).toHaveAttribute('integrity', /^sha384-/);
		await expect(link).toHaveAttribute('crossorigin', 'anonymous');
		expect(await color(page, 'sri-marker')).toBe('rgb(0, 128, 0)');
		expect(await page.evaluate(() => window.__cspViolations)).toEqual([]);
	});

	test('tampered stylesheet integrity blocks the stylesheet and is not a CSP violation', async ({ page }) => {
		await page.goto(SRI_PAGE + '&action=wrong', { waitUntil: 'load' });
		expect(await color(page, 'sri-marker')).not.toBe('rgb(0, 128, 0)');
		expect(await page.evaluate(() => window.__cspViolations)).toEqual([]);
	});
});
