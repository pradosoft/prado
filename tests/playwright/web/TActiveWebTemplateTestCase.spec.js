import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ActiveWebTemplateTest';

/**
 * The server drives the client-side template through callbacks: stamping,
 * updating stamped copies in place, replacing the template markup, and removing
 * copies. No markup travels for the updates — only the command and its data.
 */
test('TActiveWebTemplateTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Active Web Template Test Case');

	// The template renders inert and nothing is stamped yet
	await expect(page.locator('#listBody .row')).toHaveCount(0);

	// ── Server-side stamping ──
	await page.locator('#ctl0_Content_btnStamp').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .row')).toHaveCount(1);
	await expect(page.locator('#listBody .name')).toHaveText('Ada');
	await expect(page.locator('#listBody .link')).toHaveAttribute('href', '/user/7');

	// Each stamped copy carries its instance UID
	await expect(page.locator('#listBody .row')).toHaveAttribute('data-prado-instance', /^wt\d+$/);

	// ── Server-side repeat ──
	await page.locator('#ctl0_Content_btnRepeat').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .row')).toHaveCount(3);
	await expect(page.locator('#listBody .name')).toHaveText(['Ada', 'Grace', 'Alan']);

	// ── In-place update preserves state held in the copy ──
	// Type into the first row and give it a UID we can assert against later
	const firstInput = page.locator('#listBody .row').first().locator('.typed');
	await firstInput.fill('user typing');
	const firstUid = await page
		.locator('#listBody .row')
		.first()
		.getAttribute('data-prado-instance');

	await page.locator('#btnUpdateFirst').click();
	await h.waitForAjaxCalls();

	// The bound text changed; the typed value and the UID did not
	await expect(page.locator('#listBody .row').first().locator('.link')).toHaveText('Countess');
	await expect(firstInput).toHaveValue('user typing');
	expect(await page.locator('#listBody .row').first().getAttribute('data-prado-instance')).toBe(
		firstUid
	);
	// Siblings are untouched
	await expect(page.locator('#listBody .row').nth(1).locator('.link')).toHaveText('Admiral');

	// ── updateAll() merges into every copy ──
	await page.locator('#ctl0_Content_btnUpdateAll').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .link')).toHaveText(['Retired', 'Retired', 'Retired']);
	// Still the same nodes, so the typed value survives this too
	await expect(firstInput).toHaveValue('user typing');

	// ── setContent() replaces the markup and rebuilds the copies ──
	await page.locator('#ctl0_Content_btnSetContent').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .row.v2')).toHaveCount(3);
	await expect(page.locator('#listBody b.name')).toHaveText(['Ada', 'Grace', 'Alan']);
	// The rebuild kept each instance's UID
	expect(await page.locator('#listBody .row').first().getAttribute('data-prado-instance')).toBe(
		firstUid
	);
	// The rebuild discards state held in the old nodes, as documented
	await expect(page.locator('#listBody .typed')).toHaveCount(0);

	// ── removeInstance() drops one copy ──
	await page.locator('#btnRemoveFirst').click();
	await h.waitForAjaxCalls();
	await expect(page.locator('#listBody .row')).toHaveCount(2);
	await expect(page.locator('#listBody b.name')).toHaveText(['Grace', 'Alan']);
});
