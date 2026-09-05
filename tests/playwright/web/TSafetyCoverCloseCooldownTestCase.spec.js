import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SafetyCoverTest';

// A real click on the cover during the close animation must be ignored — the
// cover does not reopen mid-animation — and it becomes reopenable again once the
// close cooldown (AnimationDuration + ResetDelay) elapses. mouseoutCover uses the
// default AnimationDuration (250ms) and ResetDelay (0), and OpenDelay=200.
test('TSafetyCoverCloseCooldownTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Safety Cover Test Case');

	const id = 'ctl0_Content_mouseoutCover';
	const overlay = page.locator(`#${id}_overlay`);
	const slider = page.locator(`#${id}_slider`);

	// Open the cover.
	await overlay.click();
	await expect(slider).toHaveClass(/safety-cover-open/, { timeout: 2000 });

	// Begin closing, then click the cover DURING the close animation.
	await page.evaluate((cid) => Prado.Registry[cid].close(), id);
	await overlay.click({ force: true });

	// The click is ignored: no reopen. Wait past OpenDelay so a reopen, if it had
	// started, would have shown by now.
	await page.waitForTimeout(500);
	await expect(slider).not.toHaveClass(/safety-cover-open/);

	// After the cooldown, a click reopens the cover normally.
	await overlay.click();
	await expect(slider).toHaveClass(/safety-cover-open/, { timeout: 2000 });
});
