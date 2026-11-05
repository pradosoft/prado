import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

const PAGE_URL = 'active-controls/index.php?page=TInPlaceTextBoxTest';
const base = 'ctl0_Content_';

/**
 * Escape restores the label text, hides the editor, returns focus to the
 * label, and posts no callback.
 */
test('TInPlaceTextBoxTestCase: Escape cancels the edit', async ({ page }) => {
	const h = genericHelper(page);
	await h.url(PAGE_URL);
	await h.assertSourceContains('TInPlaceTextBox Functional Test');

	const label = page.locator(`#${base}label1__label`);
	const editor = page.locator(`#${base}label1`);
	await expect(label).toHaveText('Label 1');
	await label.click();
	await expect(editor).toBeVisible();
	await expect(editor).toBeFocused();

	await page.keyboard.press('End');
	await page.keyboard.type(' changed');
	await expect(editor).toHaveValue('Label 1 changed');
	await page.keyboard.press('Escape');

	await expect(editor).toBeHidden();
	await expect(label).toBeVisible();
	await expect(label).toHaveText('Label 1');
	await expect(label).toBeFocused();
	await expect(page.locator(`#${base}status`)).toHaveText('Status:');
});

/**
 * MaxLength renders the maxlength attribute on the created input and
 * textarea, so the browser stops input at the limit.
 */
test('TInPlaceTextBoxTestCase: MaxLength limits the editor', async ({ page }) => {
	const h = genericHelper(page);
	await h.url(PAGE_URL);

	for (const id of ['limited', 'limitedMulti']) {
		const label = page.locator(`#${base}${id}__label`);
		const editor = page.locator(`#${base}${id}`);
		await label.click();
		await expect(editor).toBeVisible();
		await expect(editor).toHaveAttribute('maxlength', '5');
		await page.keyboard.press('ControlOrMeta+a');
		await page.keyboard.type('abcdefgh');
		await expect(editor).toHaveValue('abcde');
		await page.keyboard.press('Escape');
		await expect(editor).toBeHidden();
	}
});

/**
 * Enter in a single line editor commits the text through a callback and
 * returns focus to the label.
 */
test('TInPlaceTextBoxTestCase: Enter commits the edit', async ({ page }) => {
	const h = genericHelper(page);
	await h.url(PAGE_URL);

	const label = page.locator(`#${base}limited__label`);
	const editor = page.locator(`#${base}limited`);
	await label.click();
	await page.keyboard.press('ControlOrMeta+a');
	await page.keyboard.type('Long');
	await page.keyboard.press('Enter');
	await h.waitForAjaxCalls();

	await expect(page.locator(`#${base}status`)).toHaveText('Limited: Long');
	await expect(editor).toBeHidden();
	await expect(label).toHaveText('Long');
	await expect(label).toBeFocused();
});
