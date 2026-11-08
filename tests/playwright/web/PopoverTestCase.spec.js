import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=PopoverTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Popover Test Case');
	return h;
}

/** @return {Promise<boolean>} whether the popover is showing */
function isOpen(page, id) {
	return page.locator(`#${B}${id}`).evaluate((el) => el.matches(':popover-open'));
}

/** @return {Promise<{dx: number, dy: number}>} the offset of the popover from its invoker */
function offset(page, invokerId, id) {
	return page.evaluate(([a, b]) => {
		const invoker = document.getElementById(a).getBoundingClientRect();
		const popover = document.getElementById(b).getBoundingClientRect();
		return { dx: popover.left - invoker.right, dy: popover.top - invoker.bottom };
	}, [invokerId, `${B}${id}`]);
}

/**
 * The ButtonText invoker opens the popover below it without script or a request;
 * Esc closes it and returns the focus to the invoker. The keyboard opens it,
 * because WebKit does not focus a clicked button.
 */
test('PopoverTestCase: built-in invoker, placement, and Esc', async ({ page }) => {
	await open(page);
	await page.evaluate(() => { window.marker = 1; });

	await page.getByRole('button', { name: 'Help' }).focus();
	await page.keyboard.press('Enter');
	expect(await isOpen(page, 'help')).toBe(true);
	await expect(page.getByText('Passwords take 12 or more characters.')).toBeVisible();
	expect((await offset(page, `${B}help_button`, 'help')).dy).toBeGreaterThanOrEqual(0);

	await page.keyboard.press('Escape');
	expect(await isOpen(page, 'help')).toBe(false);
	const state = await page.evaluate(() => ({ marker: window.marker, focused: document.activeElement.id }));
	expect(state).toEqual({ marker: 1, focused: `${B}help_button` });
});

/**
 * A TButton with CommandFor toggles the popover, which shows at its inline end.
 */
test('PopoverTestCase: TButton invoker and End placement', async ({ page }) => {
	await open(page);
	const button = page.getByRole('button', { name: 'Filters' });
	await button.click();
	expect(await isOpen(page, 'filters')).toBe(true);
	expect((await offset(page, `${B}openFilters`, 'filters')).dx).toBeGreaterThanOrEqual(0);
	await button.click();
	expect(await isOpen(page, 'filters')).toBe(false);
});

/**
 * An auto popover closes on a click outside it; a manual popover stays open.
 */
test('PopoverTestCase: light dismiss of auto, not manual', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Help' }).click();
	await page.locator('#outside').click();
	expect(await isOpen(page, 'help')).toBe(false);

	await page.getByRole('button', { name: 'Pinned' }).click();
	await page.locator('#outside').click();
	expect(await isOpen(page, 'pinned')).toBe(true);
	await page.getByRole('button', { name: 'Pinned' }).click();
	expect(await isOpen(page, 'pinned')).toBe(false);
});
