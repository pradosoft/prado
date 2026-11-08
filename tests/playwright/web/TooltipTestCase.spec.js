import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=TooltipTest';
const B = 'ctl0_Content_';

async function open(page) {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Tooltip Test Case');
	return h;
}

/** @return {Promise<boolean>} whether the tooltip is showing */
function isOpen(page, id) {
	return page.locator(`#${B}${id}`).evaluate((el) => el.matches(':popover-open'));
}

/**
 * The tooltip describes its target, through aria-describedby set by the client control.
 */
test('TooltipTestCase: describes the target', async ({ page }) => {
	await open(page);
	const box = page.getByRole('textbox', { name: 'Name' });
	await expect(box).toHaveAttribute('aria-describedby', `${B}nameTip`);
	await expect(box).toHaveAccessibleDescription('Your full name');
});

/**
 * Hover shows the tooltip above the target after the delay; the pointer moves onto
 * the tooltip and it stays (WCAG 1.4.13 hoverable); leaving both hides it.
 */
test('TooltipTestCase: hover, hoverable, and hide', async ({ page }) => {
	await open(page);
	const box = page.getByRole('textbox', { name: 'Name' });
	await box.hover();
	await expect.poll(() => isOpen(page, 'nameTip')).toBe(true);
	const [target, tip] = await Promise.all([box.boundingBox(), page.locator(`#${B}nameTip`).boundingBox()]);
	expect(tip.y + tip.height).toBeLessThanOrEqual(target.y);

	await page.mouse.move(tip.x + tip.width / 2, tip.y + tip.height / 2, { steps: 4 });
	await page.waitForTimeout(400);
	expect(await isOpen(page, 'nameTip')).toBe(true);

	await page.locator('#outside').hover();
	await expect.poll(() => isOpen(page, 'nameTip')).toBe(false);
});

/**
 * Focus shows the tooltip; Esc hides it and keeps the focus on the target.
 */
test('TooltipTestCase: focus and Esc', async ({ page }) => {
	await open(page);
	await page.getByRole('textbox', { name: 'Name' }).focus();
	await expect.poll(() => isOpen(page, 'nameTip')).toBe(true);
	await page.keyboard.press('Escape');
	expect(await isOpen(page, 'nameTip')).toBe(false);
	expect(await page.evaluate(() => document.activeElement.id)).toBe(`${B}userName`);

	await page.keyboard.press('Tab');
	expect(await isOpen(page, 'nameTip')).toBe(false);
});

/**
 * In a modal dialog, Esc hides the tooltip and leaves the dialog open; a second
 * Esc closes the dialog.
 */
test('TooltipTestCase: Esc in a modal dialog', async ({ page }) => {
	await open(page);
	await page.getByRole('button', { name: 'Edit' }).focus();
	await page.keyboard.press('Enter');
	await page.getByRole('textbox', { name: 'Code' }).focus();
	await expect.poll(() => isOpen(page, 'codeTip')).toBe(true);

	await page.keyboard.press('Escape');
	expect(await isOpen(page, 'codeTip')).toBe(false);
	expect(await page.locator(`#${B}editDialog`).evaluate((el) => el.open)).toBe(true);

	await page.keyboard.press('Escape');
	expect(await page.locator(`#${B}editDialog`).evaluate((el) => el.open)).toBe(false);
});
