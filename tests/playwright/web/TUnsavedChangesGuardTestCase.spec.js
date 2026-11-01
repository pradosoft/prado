import { test, expect } from '@playwright/test';
import { GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=UnsavedChangesTest';
const B = 'ctl0_Content_';

/**
 * Opens the page and records each dialog's type; a beforeunload dialog is
 * dismissed (stay) or accepted (leave) by `dialogs.answer`.
 */
async function open(page) {
	const dialogs = { types: [], answer: 'dismiss' };
	page.on('dialog', async (dialog) => {
		dialogs.types.push(dialog.type());
		if (dialogs.answer === 'accept') {
			await dialog.accept();
		} else {
			await dialog.dismiss();
		}
	});
	await page.goto(GENERIC_BASE_URL + PAGE_URL);
	await expect(page.locator('h1')).toHaveText('Unsaved Changes Test Case');
	await page.waitForFunction((id) => !!(window.Prado && Prado.Registry[id]), `${B}guard`);
	return dialogs;
}

/** Types into a field through the keyboard, which gives the page user activation. */
async function typeInto(page, id, text, clear = false) {
	const field = page.locator(`#${B}${id}`);
	await field.click();
	if (clear) {
		await field.press('ControlOrMeta+a');
		await page.keyboard.press('Backspace');
	}
	if (text) {
		await page.keyboard.type(text);
	}
}

function form(page) {
	return page.locator('form');
}

async function expectDirty(page, dirty) {
	if (dirty) {
		await expect(form(page)).toHaveAttribute('data-prado-unsaved', '');
	} else {
		await expect(form(page)).not.toHaveAttribute('data-prado-unsaved', '');
	}
}

/** Clicks the leave link; with a dismissed warning the page stays. */
async function leave(page) {
	await page.locator('#leave').click();
}

/**
 * A changed field is marked; undoing the change clears the mark and the status.
 */
test('TUnsavedChangesGuardTestCase: marks a changed field and clears it on undo', async ({ page }) => {
	await open(page);
	const name = page.locator(`#${B}name`);
	const status = page.getByRole('status');
	await expectDirty(page, false);
	await expect(status).toHaveText('');

	await typeInto(page, 'name', 'x');
	await expect(name).toHaveAttribute('data-prado-dirty', '');
	await expect(name).toHaveClass(/\bchanged\b/);
	await expectDirty(page, true);
	await expect(status).toHaveText('You have unsaved changes.');

	await page.keyboard.press('Backspace');
	await expect(name).not.toHaveAttribute('data-prado-dirty', '');
	await expect(name).not.toHaveClass(/\bchanged\b/);
	await expectDirty(page, false);
	await expect(status).toHaveText('');
});

/**
 * Checkbox, radio, select, list box, textarea, and file input mark their changes.
 */
test('TUnsavedChangesGuardTestCase: each field type marks its change', async ({ page }) => {
	await open(page);
	await typeInto(page, 'notes', ' more');
	await page.locator(`#${B}agree`).check();
	await page.locator(`#${B}size_c1`).check();
	await page.locator(`#${B}color`).selectOption('g');
	await page.locator(`#${B}tags`).selectOption(['a', 'b']);
	await page.locator(`#${B}upload`).setInputFiles({ name: 'a.txt', mimeType: 'text/plain', buffer: Buffer.from('a') });
	const marked = await page.evaluate(() => [...document.querySelectorAll('[data-prado-dirty]')].map((el) => el.id));
	expect(marked.sort()).toEqual([
		`${B}agree`, `${B}color`, `${B}notes`, `${B}size_c1`, `${B}tags`, `${B}upload`,
	].sort());

	await page.locator(`#${B}size_c0`).check();
	await page.locator(`#${B}agree`).uncheck();
	await page.locator(`#${B}color`).selectOption('r');
	await page.locator(`#${B}tags`).selectOption(['a']);
	await page.locator(`#${B}upload`).setInputFiles([]);
	await page.locator(`#${B}notes`).fill('line');
	await expectDirty(page, false);
});

/**
 * A field under data-prado-dirty-ignore, set by Dataset.prado-dirty-ignore, is not watched.
 */
test('TUnsavedChangesGuardTestCase: an ignored field stays clean', async ({ page }) => {
	await open(page);
	await expect(page.locator(`#${B}search`)).toHaveAttribute('data-prado-dirty-ignore', 'true');
	await typeInto(page, 'search', 'query');
	await expect(page.locator(`#${B}search`)).not.toHaveAttribute('data-prado-dirty', '');
	await expectDirty(page, false);
});

/**
 * Leaving a dirty page asks first; leaving a clean page does not.
 */
test('TUnsavedChangesGuardTestCase: warns on leave only while dirty', async ({ page }) => {
	const dialogs = await open(page);
	await typeInto(page, 'name', 'x');
	await leave(page);
	await expect.poll(() => dialogs.types).toEqual(['beforeunload']);
	await expect(page.locator('h1')).toHaveText('Unsaved Changes Test Case');

	dialogs.answer = 'accept';
	await leave(page);
	await expect(page.locator('#result')).toHaveText('redirect-ok');
	expect(dialogs.types).toEqual(['beforeunload', 'beforeunload']);
});

test('TUnsavedChangesGuardTestCase: a clean page leaves without a warning', async ({ page }) => {
	const dialogs = await open(page);
	await typeInto(page, 'name', 'x');
	await page.keyboard.press('Backspace');
	await leave(page);
	await expect(page.locator('#result')).toHaveText('redirect-ok');
	expect(dialogs.types).toEqual([]);
});

/**
 * Prado postbacks leave without a warning, and the posted values are the new baseline.
 */
for (const [label, act] of [
	['a submit button', (page) => page.locator(`#${B}submit`).click()],
	['a link button', (page) => page.locator(`#${B}linkSubmit`).click()],
	['an auto-postback checkbox', (page) => page.locator(`#${B}auto`).click()],
]) {
	test(`TUnsavedChangesGuardTestCase: ${label} posts back without a warning`, async ({ page }) => {
		const dialogs = await open(page);
		await typeInto(page, 'name', 'x');
		await Promise.all([page.waitForNavigation(), act(page)]);
		await expect(page.locator(`#${B}name`)).toHaveValue('Annx');
		expect(dialogs.types).toEqual([]);
		await expectDirty(page, false);
		await expect(page.getByRole('status')).toHaveText('');
	});
}

/**
 * A postback whose validation failed saved nothing, so the page renders dirty.
 */
test('TUnsavedChangesGuardTestCase: a failed postback renders dirty', async ({ page }) => {
	const dialogs = await open(page);
	await typeInto(page, 'name', '', true);
	await Promise.all([page.waitForNavigation(), page.locator(`#${B}submit`).click()]);
	await expect(page.locator(`#${B}nameRequired`)).toHaveText('Name is required.');
	await expect(page.locator(`#${B}saves`)).toHaveText('0');
	await expectDirty(page, true);
	await expect(page.getByRole('status')).toHaveText('You have unsaved changes.');
	await page.locator(`#${B}callbackOnly`).click();
	await page.waitForLoadState('networkidle');
	await expectDirty(page, true);
	await page.locator(`#${B}notes`).click();
	await leave(page);
	await expect.poll(() => dialogs.types).toEqual(['beforeunload']);
});

/**
 * A callback does not warn. One that saves marks the guard clean; one that does not keeps it dirty.
 */
test('TUnsavedChangesGuardTestCase: callbacks do not warn and markClean resets the baseline', async ({ page }) => {
	const dialogs = await open(page);
	await typeInto(page, 'name', 'x');
	await page.locator(`#${B}callbackOnly`).click();
	await page.waitForLoadState('networkidle');
	expect(dialogs.types).toEqual([]);
	await expectDirty(page, true);

	await page.locator(`#${B}callbackSave`).click();
	await expect(page.locator(`#${B}saves`)).toHaveText('1');
	await expectDirty(page, false);
	await expect(page.locator(`#${B}name`)).not.toHaveAttribute('data-prado-dirty', '');
	expect(await page.locator(`#${B}name`).evaluate((el) => el.defaultValue)).toBe('Annx');

	await leave(page);
	await expect(page.locator('#result')).toHaveText('redirect-ok');
	expect(dialogs.types).toEqual([]);
});

/**
 * A callback that re-renders a changed field gives it the server's value as its default.
 */
test('TUnsavedChangesGuardTestCase: a re-rendered field takes the server value as its baseline', async ({ page }) => {
	await open(page);
	await typeInto(page, 'server', 'typed');
	await expectDirty(page, true);
	await page.locator(`#${B}refresh`).click();
	await expect(page.locator(`#${B}server`)).toHaveValue(/^server \d+$/);
	await expectDirty(page, false);
	await expect(page.locator(`#${B}server`)).not.toHaveAttribute('data-prado-dirty', '');
});

/**
 * A callback that redirects leaves without a warning.
 */
test('TUnsavedChangesGuardTestCase: a callback redirect leaves without a warning', async ({ page }) => {
	const dialogs = await open(page);
	await typeInto(page, 'name', 'x');
	await page.locator(`#${B}redirect`).click();
	await expect(page.locator('#result')).toHaveText('redirect-ok');
	expect(dialogs.types).toEqual([]);
});

/**
 * Accessibility: the status region is empty while clean and names the state while
 * dirty; the field indicator is an outline, which does not rely on color alone.
 */
test('TUnsavedChangesGuardTestCase: accessibility of the status and the field mark', async ({ page }) => {
	await open(page);
	const status = page.getByRole('status');
	await expect(status).toHaveCount(1);
	await expect(status).toHaveText('');
	await typeInto(page, 'name', 'x');
	await expect(status).toHaveText('You have unsaved changes.');
	const outline = await page.locator(`#${B}name`).evaluate((el) => getComputedStyle(el).outlineStyle);
	expect(outline).toBe('dashed');
	await expect(page.getByRole('textbox', { name: 'Name' })).toHaveValue('Annx');
});
