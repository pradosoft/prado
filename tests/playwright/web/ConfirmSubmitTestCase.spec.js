import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ConfirmSubmitTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Confirm Submit Test Case');
	await page.evaluate(() => { window.marker = 1; });
	return h;
}

const dialog = (page) => page.getByRole('alertdialog', { name: 'Confirm' });
const status = (page) => page.locator(`#${B}status`);
const isOpen = (page) => page.locator(`#${B}confirm_confirm`).evaluate((el) => el.open);
const ok = (page) => dialog(page).getByRole('button', { name: 'Apply' }).click();
const cancel = (page) => dialog(page).getByRole('button', { name: 'Cancel' }).click();

/** Expects no request since `open`: the page keeps its marker and status. */
async function expectNoRequest(page) {
	expect(await page.evaluate(() => window.marker)).toBe(1);
	await expect(status(page)).toHaveText('waiting');
}

/**
 * The wrapper stays in its paragraph; the dialog renders after the page form.
 */
test('ConfirmSubmitTestCase: placement', async ({ page }) => {
	await open(page);
	expect(await page.locator(`#${B}confirm`).evaluate((el) => [el.tagName, el.parentElement.id])).toEqual(['SPAN', 'para']);
	await expect(page.locator('#para')).toContainText('stays in this paragraph.');
	expect(await page.locator(`#${B}confirm_confirm`).evaluate((el) => el.closest('form'))).toBeNull();
});

/**
 * Each click control asks first: Cancel drops the action, and OK runs it. The
 * dialog is a modal alertdialog described by the message, with focus on Cancel.
 */
for (const [name, locate, expected] of [
	['link button', (page) => page.getByText('Link action'), 'Acted: Link action'],
	['submit button', (page) => page.getByRole('button', { name: 'Submit action' }), 'Acted: Submit action'],
	['image button', (page) => page.locator(`#${B}image`), 'Acted: image'],
]) {
	test(`ConfirmSubmitTestCase: ${name}`, async ({ page }) => {
		await open(page);
		await locate(page).click();
		await expect(dialog(page)).toBeVisible();
		await expect(dialog(page)).toHaveAccessibleDescription('Apply this change?');
		expect(await page.evaluate(() => document.activeElement.textContent)).toBe('Cancel');
		await cancel(page);
		await expectNoRequest(page);
		expect(await isOpen(page)).toBe(false);

		await locate(page).click();
		await ok(page);
		await expect(status(page)).toHaveText(expected);
	});
}

/**
 * Esc drops the action and returns the focus to the control.
 */
test('ConfirmSubmitTestCase: Esc and focus return', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Submit action' }).focus();
	await page.keyboard.press('Enter');
	await expect(dialog(page)).toBeVisible();
	await page.keyboard.press('Escape');
	expect(await isOpen(page)).toBe(false);
	expect(await page.evaluate(() => document.activeElement.id)).toBe(`${B}submit`);
	await expectNoRequest(page);
});

/**
 * Cancel leaves an AutoPostBack checkbox as it was; OK checks it and posts back.
 */
test('ConfirmSubmitTestCase: AutoPostBack checkbox', async ({ page }) => {
	await open(page);
	const box = page.getByRole('checkbox', { name: 'Auto check' });
	await box.dispatchEvent('click');
	await expect(dialog(page)).toBeVisible();
	expect(await box.isChecked()).toBe(false);
	await cancel(page);
	expect(await box.isChecked()).toBe(false);
	await expectNoRequest(page);

	await box.dispatchEvent('click');
	await ok(page);
	await expect(status(page)).toHaveText('Changed: auto');
	await expect(page.getByRole('checkbox', { name: 'Auto check' })).toBeChecked();
});

/**
 * Cancel restores an AutoPostBack list; OK posts the new value back.
 */
test('ConfirmSubmitTestCase: AutoPostBack list', async ({ page }) => {
	await open(page);
	const list = page.getByRole('combobox', { name: 'Pick' });
	await list.focus();
	await list.selectOption('b');
	await expect(dialog(page)).toBeVisible();
	await cancel(page);
	await expect(list).toHaveValue('a');
	await expectNoRequest(page);

	await list.focus();
	await list.selectOption('b');
	await ok(page);
	await expect(status(page)).toHaveText('Changed: pick');
	await expect(page.getByRole('combobox', { name: 'Pick' })).toHaveValue('b');
});

/**
 * Cancel restores an AutoPostBack text box, and Enter asks as well.
 */
test('ConfirmSubmitTestCase: AutoPostBack text box', async ({ page }) => {
	await open(page);
	const text = page.getByRole('textbox', { name: 'Auto text' });
	await text.fill('first');
	await text.blur();
	await expect(dialog(page)).toBeVisible();
	await cancel(page);
	await expect(text).toHaveValue('');
	await expectNoRequest(page);

	await text.fill('second');
	await text.press('Enter');
	await expect(dialog(page)).toBeVisible();
	await ok(page);
	await expect(status(page)).toHaveText('Changed: autoText');
	await expect(page.getByRole('textbox', { name: 'Auto text' })).toHaveValue('second');
});

/**
 * Controls without AutoPostBack, and controls outside the wrapper, do not ask.
 */
test('ConfirmSubmitTestCase: what does not ask', async ({ page }) => {
	await open(page);
	await page.getByRole('checkbox', { name: 'Plain check' }).check();
	await page.getByRole('textbox', { name: 'Plain text' }).fill('x');
	await page.getByRole('textbox', { name: 'Plain text' }).blur();
	expect(await isOpen(page)).toBe(false);
	await page.getByRole('button', { name: 'Outside action' }).click();
	await expect(status(page)).toHaveText('Acted: Outside action');
});

/**
 * OK resumes an active control's callback, with no page load.
 */
test('ConfirmSubmitTestCase: callback', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Active action' }).click();
	await expect(dialog(page)).toBeVisible();
	await cancel(page);
	await expect(page.locator(`#${B}activeStatus`)).toHaveText('waiting');

	await page.getByRole('button', { name: 'Active action' }).click();
	await ok(page);
	await expect(page.locator(`#${B}activeStatus`)).toHaveText('Called back');
	expect(await page.evaluate(() => window.marker)).toBe(1);
});

/**
 * Validation runs after OK: a failure shows the message with no postback.
 */
test('ConfirmSubmitTestCase: validation', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Archive action' }).click();
	await ok(page);
	await expect(page.locator(`#${B}reasonRequired`)).toBeVisible();
	expect(await isOpen(page)).toBe(false);
	await expectNoRequest(page);

	await page.getByLabel('Reason').fill('old');
	await page.getByRole('button', { name: 'Archive action' }).click();
	await ok(page);
	await expect(status(page)).toHaveText('Acted: Archive action');
});

/**
 * A plain link follows after OK, not after Cancel.
 */
test('ConfirmSubmitTestCase: hyperlink', async ({ page }) => {
	await open(page);
	await page.getByRole('link', { name: 'Away link' }).click();
	await cancel(page);
	expect(new URL(page.url()).hash).toBe('');
	await page.getByRole('link', { name: 'Away link' }).click();
	await ok(page);
	await expect.poll(() => new URL(page.url()).hash).toBe('#away');
});
