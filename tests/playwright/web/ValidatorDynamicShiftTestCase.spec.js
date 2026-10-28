import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ValidatorDynamicShiftTest';
const BASE = 'ctl0_Content_';

/**
 * Loads the page and fails the amount's validation, showing its Dynamic message
 * inline before the Save button.
 */
async function showAmountMessage(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await page.locator(`#${BASE}amount`).fill('20');
	await page.locator(`#${BASE}save`).click();
	await expect(page.locator(`#${BASE}amountRange`)).toBeVisible();
	await expect(page.locator(`#${BASE}status`)).toHaveText('none');
}

/**
 * The text box's change event fires on blur when the mouse goes down on Save.
 * Hiding the Dynamic message moves Save left. The re-validation waits for the
 * release, so the click lands on Save and the form posts.
 */
test('ValidatorDynamicShiftTestCase: fixing the value and clicking Save submits', async ({ page }) => {
	await showAmountMessage(page);

	await page.locator(`#${BASE}amount`).fill('5');
	// The message stays shown until the change event
	await expect(page.locator(`#${BASE}amountRange`)).toBeVisible();
	await page.locator(`#${BASE}save`).click();

	await expect(page.locator(`#${BASE}status`)).toHaveText('saved 5');
	await expect(page.locator(`#${BASE}amountRange`)).toBeHidden();
});

/**
 * A value that is still invalid keeps the message and does not post.
 */
test('ValidatorDynamicShiftTestCase: an invalid value keeps the message', async ({ page }) => {
	await showAmountMessage(page);

	await page.locator(`#${BASE}amount`).fill('30');
	await page.locator(`#${BASE}save`).click();

	await expect(page.locator(`#${BASE}amountRange`)).toBeVisible();
	await expect(page.locator(`#${BASE}amount`)).toHaveAttribute('aria-invalid', 'true');
	await expect(page.locator(`#${BASE}status`)).toHaveText('none');
});

/**
 * A press elsewhere re-validates after the release and hides the message
 * without posting.
 */
test('ValidatorDynamicShiftTestCase: clicking elsewhere hides the message after release', async ({ page }) => {
	await showAmountMessage(page);

	await page.locator(`#${BASE}amount`).fill('5');
	await page.locator('h1').click();

	await expect(page.locator(`#${BASE}amountRange`)).toBeHidden();
	await expect(page.locator(`#${BASE}amount`)).toHaveAttribute('aria-invalid', 'false');
	await expect(page.locator(`#${BASE}status`)).toHaveText('none');
});

/**
 * A Dynamic validation summary above a button collapses on re-validation and
 * moves the button up. The click still lands on the button.
 */
test('ValidatorDynamicShiftTestCase: a Dynamic summary above the button', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await page.locator(`#${BASE}saveTitle`).click();
	await expect(page.locator(`#${BASE}summary`)).toBeVisible();
	await expect(page.locator(`#${BASE}summary`)).toContainText('The title is required.');

	await page.locator(`#${BASE}title`).fill('Report');
	await page.locator(`#${BASE}saveTitle`).click();

	await expect(page.locator(`#${BASE}status`)).toHaveText('saved Report');
	await expect(page.locator(`#${BASE}summary`)).toBeHidden();
});
