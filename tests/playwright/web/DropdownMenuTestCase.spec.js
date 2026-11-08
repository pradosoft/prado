import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=DropdownMenuTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Dropdown Menu Test Case');
	return h;
}

/** @return {Promise<boolean>} whether the menu is showing */
function isOpen(page) {
	return page.locator(`#${B}actions`).evaluate((el) => el.matches(':popover-open'));
}

const focused = (page) => page.evaluate(() => document.activeElement.id);

/**
 * The menu button and the menu expose the ARIA menu button pattern; the enabled
 * links and buttons are the menu items.
 */
test('DropdownMenuTestCase: roles', async ({ page }) => {
	await open(page);
	const button = page.getByRole('button', { name: 'Actions' });
	await expect(button).toHaveAttribute('aria-haspopup', 'menu');
	await button.click();
	const menu = page.getByRole('menu');
	await expect(menu).toBeVisible();
	await expect(menu.getByRole('menuitem')).toHaveText(['Duplicate', 'Archive', 'Export']);
	await expect(menu.getByRole('button', { name: 'Delete' })).toBeDisabled();
});

/**
 * Enter opens the menu on the first item; the arrows, Home, End, and a character
 * move the focus; Esc closes it and returns the focus to the menu button. The
 * menu shows below the button, aligned with its start.
 */
test('DropdownMenuTestCase: keyboard', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Actions' }).focus();
	await page.keyboard.press('Enter');
	await expect.poll(() => focused(page)).toBe(`${B}duplicate`);

	const [button, menu] = await Promise.all([
		page.locator(`#${B}actions_button`).boundingBox(),
		page.locator(`#${B}actions`).boundingBox(),
	]);
	expect(menu.y).toBeGreaterThanOrEqual(button.y + button.height);
	expect(Math.abs(menu.x - button.x)).toBeLessThan(8);

	await page.keyboard.press('ArrowDown');
	expect(await focused(page)).toBe(`${B}archive`);
	await page.keyboard.press('ArrowDown');
	expect(await focused(page)).toBe(`${B}export`);
	await page.keyboard.press('ArrowDown');
	expect(await focused(page)).toBe(`${B}duplicate`);
	await page.keyboard.press('End');
	expect(await focused(page)).toBe(`${B}export`);
	await page.keyboard.press('a');
	expect(await focused(page)).toBe(`${B}archive`);

	await page.keyboard.press('Escape');
	expect(await isOpen(page)).toBe(false);
	expect(await focused(page)).toBe(`${B}actions_button`);
});

/**
 * Up Arrow on the menu button opens the menu on its last item; Tab closes it.
 */
test('DropdownMenuTestCase: Up Arrow and Tab', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Actions' }).focus();
	await page.keyboard.press('ArrowUp');
	await expect.poll(() => focused(page)).toBe(`${B}export`);
	await page.keyboard.press('Tab');
	expect(await isOpen(page)).toBe(false);
});

/**
 * Activating a postback item closes the menu and posts back.
 */
test('DropdownMenuTestCase: an item posts back', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Actions' }).focus();
	await page.keyboard.press('ArrowDown');
	await page.keyboard.press('ArrowDown');
	await expect.poll(() => focused(page)).toBe(`${B}archive`);
	await page.keyboard.press('Enter');
	await expect(page.locator(`#${B}status`)).toHaveText('Clicked: Archive');
	expect(await isOpen(page)).toBe(false);
});
