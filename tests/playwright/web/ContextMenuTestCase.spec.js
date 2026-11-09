import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ContextMenuTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Context Menu Test Case');
	return h;
}

/** @return {Promise<boolean>} whether the menu is showing */
function isOpen(page) {
	return page.locator(`#${B}photoMenu`).evaluate((el) => el.matches(':popover-open'));
}

const focused = (page) => page.evaluate(() => document.activeElement.id);

/**
 * A right-click opens the menu at the pointer on its first item, in place of the
 * browser context menu; a click outside closes it. Where `contextmenu` fires while
 * the button is down, the menu opens just after the pointerup, so the test waits.
 */
test('ContextMenuTestCase: right-click', async ({ page }) => {
	await open(page);
	const photo = page.locator(`#${B}photo`);
	const box = await photo.boundingBox();
	await photo.click({ button: 'right', position: { x: 100, y: 40 } });
	await expect.poll(() => isOpen(page)).toBe(true);
	await expect(page.getByRole('menu')).toBeVisible();
	await expect(page.getByRole('menu').getByRole('menuitem')).toHaveText(['Rotate', 'Delete']);
	expect(await focused(page)).toBe(`${B}rotate`);

	const menu = await page.locator(`#${B}photoMenu`).boundingBox();
	expect(Math.abs(menu.x - (box.x + 100))).toBeLessThan(2);
	expect(Math.abs(menu.y - (box.y + 40))).toBeLessThan(2);

	await page.locator('#outside').click();
	expect(await isOpen(page)).toBe(false);
});

/**
 * Shift+F10 on the focused target opens the menu below it; Esc closes it and
 * returns the focus to the target.
 */
test('ContextMenuTestCase: keyboard', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}photo`).focus();
	await page.keyboard.press('Shift+F10');
	expect(await isOpen(page)).toBe(true);
	const [photo, menu] = await Promise.all([
		page.locator(`#${B}photo`).boundingBox(),
		page.locator(`#${B}photoMenu`).boundingBox(),
	]);
	expect(Math.abs(menu.y - (photo.y + photo.height))).toBeLessThan(2);
	await page.keyboard.press('ArrowDown');
	expect(await focused(page)).toBe(`${B}remove`);

	await page.keyboard.press('Escape');
	expect(await isOpen(page)).toBe(false);
	expect(await focused(page)).toBe(`${B}photo`);
});

/**
 * Activating an item closes the menu and posts back.
 */
test('ContextMenuTestCase: an item posts back', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}photo`).focus();
	await page.keyboard.press('Shift+F10');
	await page.keyboard.press('End');
	await page.keyboard.press('Enter');
	await expect(page.locator(`#${B}status`)).toHaveText('Clicked: Delete');
});
