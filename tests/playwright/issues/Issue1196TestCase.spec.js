import { test, expect } from '@playwright/test';
import { genericHelper } from '../helpers.js';

/**
 * Regression test for issue #1196:
 * TDatePicker popup appears in wrong position when inside a positioned ancestor.
 *
 * The fix (commit 6bde847) computes the popup offset using getBoundingClientRect()
 * relative to the nearest non-static positioned ancestor instead of raw offsetTop/offsetLeft,
 * which produced incorrect results after the jQuery removal (commit 2c78ad5).
 *
 * Pickers use Mode="Clickable" so the trigger event is "click" (not "focus").
 * The focus-based trigger fires show() during pointerdown, before the click event
 * bubbles to body, which makes the calendar cover the cursor and causes mouseup
 * to hit the calDiv instead of the input — a headless-Chromium artifact.
 */

test('Issue1196 — popup appears near the input (no positioned parent)', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('issues/index.php?page=Issue1196');
	await h.assertSourceContains('Issue 1196 Test');

	const base = 'ctl0_Content_';
	const inputId = `${base}picker1`;

	await h.byId(inputId).click();
	await h.pause(200);

	// The calendar is appended as a sibling of the input, inside #plain-wrap
	const calDiv = page.locator('#plain-wrap .TDatePicker_default');
	await expect(calDiv).toBeVisible();

	const inputBox = await h.byId(inputId).boundingBox();
	const calBox   = await calDiv.boundingBox();

	expect(inputBox).not.toBeNull();
	expect(calBox).not.toBeNull();

	// Popup must be within 200px vertically of the input bottom edge
	const verticalDistance = Math.abs(calBox.y - (inputBox.y + inputBox.height));
	expect(verticalDistance).toBeLessThan(200);

	// Popup left edge must be within 100px of the input left edge
	const horizontalDistance = Math.abs(calBox.x - inputBox.x);
	expect(horizontalDistance).toBeLessThan(100);
});

test('Issue1196 — popup appears near the input inside a positioned container', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('issues/index.php?page=Issue1196');

	const base = 'ctl0_Content_';
	const inputId = `${base}picker2`;

	await h.byId(inputId).click();
	await h.pause(200);

	// The calendar is appended inside #positioned-wrap
	const calDiv = page.locator('#positioned-wrap .TDatePicker_default');
	await expect(calDiv).toBeVisible();

	const inputBox = await h.byId(inputId).boundingBox();
	const calBox   = await calDiv.boundingBox();

	expect(inputBox).not.toBeNull();
	expect(calBox).not.toBeNull();

	// Vertical: popup must be within 200px of the input bottom edge
	const verticalDistance = Math.abs(calBox.y - (inputBox.y + inputBox.height));
	expect(verticalDistance).toBeLessThan(200);

	// Horizontal: popup must be within 100px of the input left edge
	const horizontalDistance = Math.abs(calBox.x - inputBox.x);
	expect(horizontalDistance).toBeLessThan(100);
});

test('Issue1196 — button-mode popup appears near the button', async ({ page }) => {
	const h = genericHelper(page);
	await h.url('issues/index.php?page=Issue1196');

	const base = 'ctl0_Content_';
	const triggerId = `${base}picker3button`;
	const inputId   = `${base}picker3`;

	await h.byId(triggerId).click();
	await h.pause(200);

	// The calendar is appended inside #button-wrap
	const calDiv = page.locator('#button-wrap .TDatePicker_default');
	await expect(calDiv).toBeVisible();

	// Position is computed relative to the text input (picker3), not the trigger button.
	const inputBox = await h.byId(inputId).boundingBox();
	const calBox   = await calDiv.boundingBox();

	expect(inputBox).not.toBeNull();
	expect(calBox).not.toBeNull();

	const verticalDistance = Math.abs(calBox.y - (inputBox.y + inputBox.height));
	expect(verticalDistance).toBeLessThan(200);

	const horizontalDistance = Math.abs(calBox.x - inputBox.x);
	expect(horizontalDistance).toBeLessThan(100);
});
