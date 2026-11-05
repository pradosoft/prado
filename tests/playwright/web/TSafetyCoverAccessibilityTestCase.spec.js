import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SafetyCoverTest';

// Accessibility: the guard is a keyboard-operable button, and the guarded content
// is genuinely unreachable (not just mouse-blocked) while the cover is closed.
// Uses mouseoutCover (Slide + Up, AutoCloseDelay 30000 so it stays open for the test).
test('TSafetyCoverAccessibilityTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Safety Cover Test Case');

	const id = 'ctl0_Content_mouseoutCover';
	const guard = page.locator(`#${id}_overlay`);
	const button = '#mouseoutButton';
	const result = page.locator('#mouseoutResult');

	// ── The guard is exposed to AT as a button with a name and collapsed state.
	//    By default it is labelled by the visible face, so the accessible name
	//    matches the visible text (WCAG 2.5.3 Label in Name) ──
	await expect(guard).toHaveAttribute('role', 'button');
	await expect(guard).toHaveAttribute('tabindex', '0');
	await expect(guard).toHaveAttribute('aria-labelledby', `${id}_face`);
	await expect(guard).toHaveAccessibleName('Unlock');
	await expect(guard).toHaveAttribute('aria-expanded', 'false');
	await expect(guard).toHaveAttribute('aria-controls', `${id}_content`);

	// ── While CLOSED, the guarded button cannot be focused (it is inert), so
	//    keyboard and AT users cannot reach it behind the cover ──
	const focusedWhileClosed = await page.evaluate(() => {
		const b = document.getElementById('mouseoutButton');
		b.focus();
		return document.activeElement === b;
	});
	expect(focusedWhileClosed, 'guarded button must not be focusable while closed').toBe(false);
	await expect(result).toHaveText('');

	// ── The cover opens from the KEYBOARD: focus the guard, press Enter ──
	await guard.focus();
	expect(await page.evaluate((gid) => document.activeElement === document.getElementById(gid), `${id}_overlay`)).toBe(true);
	await page.keyboard.press('Enter');
	await expect(page.locator(`#${id}_slider`)).toHaveClass(/safety-cover-open/, { timeout: 2000 });
	await expect(guard).toHaveAttribute('aria-expanded', 'true');
	// Open: the guard leaves the tab order (no tabbable no-op button) ──
	await expect(guard).toHaveAttribute('tabindex', '-1');

	// ── A keyboard open moves focus into the revealed content ──
	await expect
		.poll(() => page.evaluate(() => document.activeElement && document.activeElement.id), { timeout: 2000 })
		.toBe('mouseoutButton');

	// ── Now reachable: activating the button from the keyboard works ──
	await page.locator(button).press('Enter');
	await expect(result).toHaveText('clicked');

	// ── Closing re-guards: the button is inert again and the state collapses ──
	await page.evaluate((rid) => Prado.Registry[rid].close(), id);
	await expect(guard).toHaveAttribute('aria-expanded', 'false');
	await expect(guard).toHaveAttribute('tabindex', '0'); // back in the tab order
	const focusedAfterClose = await page.evaluate(() => {
		const b = document.getElementById('mouseoutButton');
		b.focus();
		return document.activeElement === b;
	});
	expect(focusedAfterClose, 'guarded button must not be focusable after re-guarding').toBe(false);
});
