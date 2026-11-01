import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=TeleportTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Teleport Test Case');
	return h;
}

/**
 * The content follows the page form, outside the transformed ancestor of its template position.
 */
test('TTeleportTestCase: the content renders after the page form', async ({ page }) => {
	await open(page);
	const layout = await page.evaluate((b) => {
		const form = document.getElementById('PRADO_PAGESTATE').form;
		const layer = document.getElementById(`${b}layer`);
		const ids = [];
		for (let el = form.nextElementSibling; el && el.hasAttribute('data-prado-teleport'); el = el.nextElementSibling)
			ids.push(el.id);
		const toast = document.getElementById('toast').getBoundingClientRect();
		return {
			ids,
			inForm: form.contains(layer),
			inTransformed: document.getElementById('transformed').contains(layer),
			scriptAfter: !!layer.parentNode.querySelector(':scope > form ~ script'),
			toastAtViewportCorner: Math.round(toast.right) === document.documentElement.clientWidth && Math.round(toast.bottom) === window.innerHeight,
		};
	}, B);
	expect(layout).toEqual({
		ids: [`${B}layer`, `${B}noteLayer`, `${B}innerLayer`],
		inForm: false,
		inTransformed: false,
		scriptAfter: true,
		toastAtViewportCorner: true,
	});
});

/**
 * The dialog form exists; its fields belong to it, and the other fields to the page form.
 */
test('TTeleportTestCase: fields belong to the page form, dialog form fields to the dialog form', async ({ page }) => {
	await open(page);
	const owners = await page.evaluate((b) => {
		const owner = (id) => document.getElementById(id).form.id;
		return {
			dialogForm: document.getElementById('editDialogForm').method,
			name: owner(`${b}userName`),
			save: owner(`${b}save`),
			replacedField: owner(`${b}replacedField`),
			reason: owner('reason'),
			confirm: owner('confirm'),
			pageForm: document.getElementById('PRADO_PAGESTATE').form.id,
		};
	}, B);
	expect(owners).toEqual({
		dialogForm: 'dialog',
		name: owners.pageForm,
		save: owners.pageForm,
		replacedField: owners.pageForm,
		reason: 'editDialogForm',
		confirm: 'editDialogForm',
		pageForm: owners.pageForm,
	});
});

/**
 * A page form button in the teleported dialog posts back, with the teleported field.
 */
test('TTeleportTestCase: a postback submits the teleported fields', async ({ page }) => {
	await open(page);
	await page.locator('#openEdit').click();
	const dialog = page.getByRole('dialog', { name: 'Edit name' });
	await expect(dialog).toBeVisible();
	await dialog.getByLabel('Name').fill('Ada');
	await Promise.all([page.waitForNavigation(), dialog.getByRole('button', { name: 'Save' }).click()]);
	await expect(page.locator(`#${B}postbacks`)).toHaveText('1');
	await expect(page.locator(`#${B}savedName`)).toHaveText('Ada');
	await expect(page.locator(`#${B}userName`)).toHaveValue('Ada');
});

/**
 * The native dialog form closes the modal dialog without a request, after its constraint check.
 */
test('TTeleportTestCase: the dialog form closes the dialog without a request', async ({ page }) => {
	await open(page);
	const requests = [];
	page.on('request', (r) => { if (r.method() === 'POST') requests.push(r.url()); });
	await page.evaluate(() => { window.__marker = 1; });
	const opener = page.locator('#openEdit');
	await opener.click();
	const dialog = page.getByRole('dialog', { name: 'Edit name' });

	await page.locator('#confirm').click();
	await expect(dialog).toBeVisible();
	expect(await page.evaluate(() => document.getElementById('reason').matches(':invalid'))).toBe(true);

	await page.locator('#reason').fill('typo');
	await page.locator('#confirm').click();
	await expect(dialog).toBeHidden();
	expect(await page.evaluate((b) => document.getElementById(`${b}editDialog`).returnValue, B)).toBe('confirm');

	// WebKit does not focus a clicked button, so the keyboard opens the dialog
	await opener.focus();
	await page.keyboard.press('Enter');
	await page.locator('#cancel').click();
	await expect(dialog).toBeHidden();
	const state = await page.evaluate((b) => ({
		returnValue: document.getElementById(`${b}editDialog`).returnValue,
		marker: window.__marker,
		focused: document.activeElement.id,
	}), B);
	expect(state).toEqual({ returnValue: 'cancel', marker: 1, focused: 'openEdit' });
	expect(requests).toEqual([]);
});

/**
 * Escape closes the modal dialog and the focus returns to the opener.
 */
test('TTeleportTestCase: the modal dialog takes the focus and Escape returns it', async ({ page }) => {
	await open(page);
	await page.locator('#openEdit').focus();
	await page.keyboard.press('Enter');
	const dialog = page.getByRole('dialog', { name: 'Edit name' });
	await expect(dialog).toBeVisible();
	expect(await page.evaluate(() => document.activeElement.closest('dialog') !== null)).toBe(true);
	await page.keyboard.press('Escape');
	await expect(dialog).toBeHidden();
	expect(await page.evaluate(() => document.activeElement.id)).toBe('openEdit');
});

/**
 * Active panels inside the content update by ID; a morphed panel keeps its nodes, and new fields join the page form.
 */
test('TTeleportTestCase: callback updates inside the content', async ({ page }) => {
	await open(page);
	await page.evaluate(() => {
		window.__morphed = document.getElementById('morphedCount');
		window.__replaced = document.getElementById('replacedCount');
	});
	await page.locator(`#${B}refresh`).click();
	await expect(page.locator('#replacedCount')).toHaveText('1');
	await expect(page.locator('#morphedCount')).toHaveText('1');
	const state = await page.evaluate((b) => {
		const form = document.getElementById('PRADO_PAGESTATE').form;
		return {
			sameMorphed: document.getElementById('morphedCount') === window.__morphed,
			sameReplaced: document.getElementById('replacedCount') === window.__replaced,
			replacedField: document.getElementById(`${b}replacedField`).value,
			replacedOwner: document.getElementById(`${b}replacedField`).form === form,
			morphedOwner: document.getElementById(`${b}morphedField`).form === form,
			inLayer: document.getElementById(`${b}layer`).contains(document.getElementById('replacedCount')),
		};
	}, B);
	expect(state).toEqual({ sameMorphed: true, sameReplaced: false, replacedField: 'replaced 1', replacedOwner: true, morphedOwner: true, inLayer: true });
});

/**
 * A callback posts the fields of a dialog form: the TActiveDialog close callback reads its note.
 */
test('TTeleportTestCase: a TActiveDialog close callback posts the dialog form field', async ({ page }) => {
	await open(page);
	await page.locator('#openNote').click();
	const dialog = page.getByRole('dialog', { name: 'Add note' });
	await expect(dialog).toBeVisible();
	await dialog.getByLabel('Note').fill('remember');
	await page.locator('#noteOk').click();
	await expect(dialog).toBeHidden();
	await expect(page.locator(`#${B}closedWith`)).toHaveText('remember');
	expect(await page.evaluate((b) => document.getElementById(`${b}noteDialog`).returnValue, B)).toBe('ok');
});

/**
 * An ancestor's update replaces the content after the form, and adds a teleport that was hidden.
 */
test('TTeleportTestCase: an ancestor callback update replaces the content after the form', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}rerender`).click();
	await expect(page.locator('#outerCount')).toHaveText('1');
	await expect(page.locator('#innerCount')).toHaveText('1');
	await expect(page.locator('#lateCount')).toHaveText('1');
	const state = await page.evaluate((b) => {
		const form = document.getElementById('PRADO_PAGESTATE').form;
		const ids = [];
		for (let el = form.nextElementSibling; el && el.hasAttribute('data-prado-teleport'); el = el.nextElementSibling)
			ids.push(el.id);
		return {
			ids,
			innerCopies: document.querySelectorAll('#innerCount').length,
			innerInOuter: document.getElementById(`${b}outer`).contains(document.getElementById('innerCount')),
			innerFieldOwner: document.getElementById(`${b}innerField`).form === form,
		};
	}, B);
	expect(state).toEqual({
		ids: [`${B}layer`, `${B}noteLayer`, `${B}innerLayer`, `${B}lateLayer`],
		innerCopies: 1,
		innerInOuter: false,
		innerFieldOwner: true,
	});

	await page.locator(`#${B}rerender`).click();
	await expect(page.locator('#lateCount')).toHaveText('2');
	expect(await page.evaluate(() => document.querySelectorAll('#lateCount').length)).toBe(1);
});
