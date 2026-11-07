import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ConfirmButtonTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Confirm Button Test Case');
	return h;
}

/** @return {Promise<{open: boolean, modal: boolean}>} the state of a confirmation dialog */
function dialogState(page, id) {
	return page.locator(`#${B}${id}_confirm`).evaluate((el) => ({ open: el.open, modal: el.matches(':modal') }));
}

/**
 * The button stays in its paragraph; the dialog renders after the page form.
 */
test('ConfirmButtonTestCase: placement', async ({ page }) => {
	await open(page);
	expect(await page.locator(`#${B}remove`).evaluate((el) => el.parentElement.id)).toBe('para');
	await expect(page.locator('#para')).toContainText('stays in this paragraph.');
	expect(await page.locator(`#${B}remove_confirm`).evaluate((el) => el.closest('form'))).toBeNull();
});

/**
 * The button opens a modal alertdialog named by its heading and described by the
 * message; Cancel takes the focus, closes it without a request, and returns the
 * focus to the button.
 */
test('ConfirmButtonTestCase: cancel', async ({ page }) => {
	await open(page);
	await page.evaluate(() => { window.marker = 1; });
	const button = page.getByRole('button', { name: 'Remove' });
	await expect(button).toHaveAttribute('aria-haspopup', 'dialog');
	await button.focus();
	await page.keyboard.press('Enter');
	expect(await dialogState(page, 'remove')).toEqual({ open: true, modal: true });

	const dialog = page.getByRole('alertdialog', { name: 'Confirm' });
	await expect(dialog).toHaveAccessibleDescription('Remove this item?');
	expect(await page.evaluate(() => document.activeElement.textContent)).toBe('Cancel');

	await page.keyboard.press('Enter');
	expect((await dialogState(page, 'remove')).open).toBe(false);
	const state = await page.evaluate(() => ({ marker: window.marker, focused: document.activeElement.id }));
	expect(state).toEqual({ marker: 1, focused: `${B}remove` });
	await expect(page.locator(`#${B}status`)).toHaveText('waiting');
});

/**
 * Esc closes the dialog without posting back.
 */
test('ConfirmButtonTestCase: Esc', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Remove' }).click();
	await page.keyboard.press('Escape');
	expect((await dialogState(page, 'remove')).open).toBe(false);
	await expect(page.locator(`#${B}status`)).toHaveText('waiting');
});

/**
 * The OK button posts back as the TConfirmButton, from after the page form.
 */
test('ConfirmButtonTestCase: confirm posts back', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Remove', exact: true }).click();
	await page.getByRole('alertdialog').getByRole('button', { name: 'Remove' }).click();
	await expect(page.locator(`#${B}status`)).toHaveText('Removed');
});

/**
 * A failed client validation closes the dialog so the message shows; a valid
 * field posts back.
 */
test('ConfirmButtonTestCase: validation', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Archive' }).click();
	await page.getByRole('alertdialog').getByRole('button', { name: 'OK' }).click();
	await expect(page.locator(`#${B}reasonRequired`)).toBeVisible();
	await expect.poll(async () => (await dialogState(page, 'archive')).open).toBe(false);
	await expect(page.locator(`#${B}status`)).toHaveText('waiting');

	await page.getByLabel('Reason').fill('old');
	await page.getByRole('button', { name: 'Archive' }).click();
	await page.getByRole('alertdialog').getByRole('button', { name: 'OK' }).click();
	await expect(page.locator(`#${B}status`)).toHaveText('Archived: old');
});
