import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=KeyShortcutsTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Key Shortcuts Test Case');
	await page.evaluate(() => window.localStorage.removeItem('prado:keyshortcuts:characterKeys'));
	return h;
}

const result = (page) => page.locator(`#${B}result`);

/**
 * KeyShortcuts renders aria-keyshortcuts for assistive technology and the
 * registry marker; the keyshortcuts package loads.
 */
test('KeyShortcutsTestCase: attributes and package', async ({ page }) => {
	await open(page);
	const save = page.locator(`#${B}save`);
	await expect(save).toHaveAttribute('aria-keyshortcuts', 'Control+S Meta+S');
	await expect(save).toHaveAttribute('data-prado-keyshortcuts', '');
	await expect(page.locator(`#${B}bold`)).toHaveAttribute('data-prado-keyshortcuts', `${B}toolbar`);
	expect(await page.evaluate(() => typeof Prado.KeyShortcuts.register)).toBe('function');
});

/**
 * Control+S and Meta+S click the save button, which posts back; the browser's
 * save dialog is prevented.
 */
test('KeyShortcutsTestCase: Control+S and Meta+S post back', async ({ page }) => {
	await open(page);
	await page.locator('h1').click();
	await page.keyboard.press('Control+s');
	await expect(page.locator(`#${B}saves`)).toHaveText('1');
	await page.locator(`#${B}search`).click();
	await page.keyboard.press('Meta+s');
	await expect(page.locator(`#${B}saves`)).toHaveText('2');
});

/**
 * "/" focuses the search field; inside the field "/" types a slash, and a
 * character key does not trigger a page-wide shortcut.
 */
test('KeyShortcutsTestCase: typing in a field is safe', async ({ page }) => {
	await open(page);
	const search = page.locator(`#${B}search`);
	await page.locator('h1').click();
	await page.keyboard.press('/');
	await expect(search).toBeFocused();
	await expect(search).toHaveValue('');
	await page.keyboard.type('a/i');
	await expect(search).toHaveValue('a/i');
	await expect(result(page)).toHaveText('none');
});

/**
 * A page-wide character key works until the user turns single-key shortcuts
 * off (WCAG 2.1.4); the choice persists across a reload.
 */
test('KeyShortcutsTestCase: character keys can be turned off', async ({ page }) => {
	await open(page);
	await page.locator('h1').click();
	await page.keyboard.press('i');
	await expect(result(page)).toHaveText('italic');

	await page.locator(`#${B}characterKeys`).uncheck();
	await page.locator(`#${B}rebind`).focus();
	await page.evaluate(() => { document.getElementById('ctl0_Content_result').textContent = 'cleared'; });
	await page.keyboard.press('i');
	await page.waitForTimeout(300);
	await expect(result(page)).toHaveText('cleared');

	await page.reload();
	expect(await page.evaluate(() => Prado.KeyShortcuts.getCharacterKeysEnabled())).toBe(false);
	await page.evaluate(() => window.localStorage.removeItem('prado:keyshortcuts:characterKeys'));
});

/**
 * A scoped shortcut works only while focus is inside its scope element.
 */
test('KeyShortcutsTestCase: scoped shortcut', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}refresh`).focus();
	await page.keyboard.press('b');
	await page.waitForTimeout(300);
	await expect(result(page)).toHaveText('none');
	await page.locator(`#${B}first`).focus();
	await page.keyboard.press('b');
	await expect(result(page)).toHaveText('bold');
});

/**
 * A shortcut changed in a callback updates the attribute through the page state
 * tracker; the old shortcut stops and the new one works.
 */
test('KeyShortcutsTestCase: shortcut changed in a callback', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}rebind`).click();
	await expect(result(page)).toHaveText('rebound');
	await expect(page.locator(`#${B}italic`)).toHaveAttribute('aria-keyshortcuts', 'Alt+Shift+I');
	await page.locator('h1').click();
	await page.keyboard.press('i');
	await page.waitForTimeout(300);
	await expect(result(page)).toHaveText('rebound');
	await page.keyboard.press('Alt+Shift+KeyI');
	await expect(result(page)).toHaveText('italic');
});

/**
 * A button replaced by a callback keeps its shortcut; a removed
 * button's shortcut stops.
 */
test('KeyShortcutsTestCase: callback replacement and removal', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}refresh`).click();
	await expect(page.locator(`#${B}renders`)).toHaveText('1');
	await page.locator('h1').click();
	await page.keyboard.press('Alt+Shift+KeyR');
	await expect(result(page)).toHaveText('inner 1');

	await page.locator(`#${B}hideInner`).click();
	// An invisible active control leaves a placeholder span with its ID.
	await expect(page.locator(`#${B}inner`)).not.toHaveAttribute('aria-keyshortcuts');
	await page.locator('h1').click();
	await page.evaluate(() => { document.getElementById('ctl0_Content_result').textContent = 'cleared'; });
	await page.keyboard.press('Alt+Shift+KeyR');
	await page.waitForTimeout(300);
	await expect(result(page)).toHaveText('cleared');
});
