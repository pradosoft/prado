import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=DialogInvokerTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Dialog Invoker Test Case');
	return h;
}

/** @return {Promise<{open: boolean, modal: boolean, returnValue: string}>} the state of a dialog */
function dialogState(page, id) {
	return page.locator(`#${B}${id}`).evaluate((el) => ({
		open: el.open,
		modal: el.matches(':modal'),
		returnValue: el.returnValue,
	}));
}

/**
 * The invoker buttons open the dialog modally and close it with their value,
 * without script and without a request. Closing returns the focus to the
 * invoker; the keyboard opens it, because WebKit does not focus a clicked button.
 */
test('DialogInvokerTestCase: show-modal and close', async ({ page }) => {
	await open(page);
	await page.evaluate(() => { window.marker = 1; });

	await page.getByRole('button', { name: 'Edit name' }).focus();
	await page.keyboard.press('Enter');
	expect(await dialogState(page, 'editDialog')).toEqual({ open: true, modal: true, returnValue: '' });
	await expect(page.getByRole('dialog', { name: 'Edit name' })).toBeVisible();

	await page.getByRole('button', { name: 'Cancel' }).click();
	expect(await dialogState(page, 'editDialog')).toEqual({ open: false, modal: false, returnValue: 'cancel' });
	const state = await page.evaluate(() => ({ marker: window.marker, focused: document.activeElement.id }));
	expect(state).toEqual({ marker: 1, focused: `${B}openEdit` });
	await expect(page.locator(`#${B}status`)).toHaveText('waiting');
});

/**
 * A modal dialog closes on Esc.
 */
test('DialogInvokerTestCase: Esc closes the modal dialog', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Edit name' }).click();
	await page.keyboard.press('Escape');
	expect((await dialogState(page, 'editDialog')).open).toBe(false);
});

/**
 * Save posts back with the dialog's field; a Save without a name reopens the
 * Modal dialog modally after the postback.
 */
test('DialogInvokerTestCase: a failed postback reopens the dialog modally', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Edit name' }).click();
	await page.getByRole('button', { name: 'Save' }).click();
	await expect(page.locator(`#${B}status`)).toHaveText('Name required');
	await expect.poll(() => dialogState(page, 'editDialog')).toEqual({ open: true, modal: true, returnValue: '' });

	await page.getByLabel('Name', { exact: true }).fill('Ann');
	await page.getByRole('button', { name: 'Save' }).click();
	await expect(page.locator(`#${B}status`)).toHaveText('Saved: Ann');
	expect((await dialogState(page, 'editDialog')).open).toBe(false);
});

/**
 * ClosedBy="Any" renders closedby="any": a click outside closes the dialog.
 */
test('DialogInvokerTestCase: light dismiss', async ({ page }) => {
	await open(page);
	await expect(page.locator(`#${B}notice`)).toHaveAttribute('closedby', 'any');
	test.skip(!(await page.evaluate(() => 'closedBy' in HTMLDialogElement.prototype)), 'closedby is not supported');

	await page.getByRole('button', { name: 'Show notice' }).click();
	expect((await dialogState(page, 'notice')).open).toBe(true);
	await page.mouse.click(5, 5);
	await expect.poll(async () => (await dialogState(page, 'notice')).open).toBe(false);
});
