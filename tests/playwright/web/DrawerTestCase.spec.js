import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=DrawerTest';
const B = 'ctl0_Content_';
const WIDE = { width: 1280, height: 800 };
const NARROW = { width: 400, height: 800 };

async function open(page, viewport = WIDE) {
	await page.setViewportSize(viewport);
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Drawer Test Case');
	return h;
}

/** @return {Promise<{open: boolean, modal: boolean, visible: boolean}>} the state of a drawer */
function state(page, id) {
	return page.locator(`#${B}${id}`).evaluate((el) => ({
		open: el.open,
		modal: el.matches(':modal'),
		visible: el.checkVisibility(),
	}));
}

const focused = (page) => page.evaluate(() => document.activeElement.id);
const box = (page, id) => page.locator(`#${B}${id}`).boundingBox();

/** Opens a drawer from its invoker with the keyboard; WebKit does not focus a clicked button. */
async function openFrom(page, invoker) {
	await page.locator(`#${B}${invoker}`).focus();
	await page.keyboard.press('Enter');
}

/** Dispatches a touch gesture on an element, with `touches` set on plain events. */
function swipe(page, selector, from, to, duration = 1000) {
	return page.locator(selector).evaluate((el, [a, b, ms]) => {
		const fire = (type, point, time) => {
			const event = new Event(type, { bubbles: true, cancelable: true });
			event.touches = point ? [{ clientX: point[0], clientY: point[1] }] : [];
			Object.defineProperty(event, 'timeStamp', { value: time });
			el.dispatchEvent(event);
			return event.defaultPrevented;
		};
		fire('touchstart', a, 0);
		const prevented = fire('touchmove', b, ms);
		fire('touchend', null, ms * 2);
		return prevented;
	}, [from, to, duration]);
}

/**
 * Wide: the drawer shows docked on first paint in the page flow, takes no focus,
 * and hides its invokers; a click on the page and Esc leave it open. It keeps the
 * dialog role, with its navigation landmark inside.
 */
test('DrawerTestCase: docked when wide', async ({ page }) => {
	await open(page);
	expect(await state(page, 'nav')).toEqual({ open: true, modal: false, visible: true });
	expect(await focused(page)).not.toBe(`${B}nav`);
	await expect(page.locator('#home')).not.toBeFocused();
	await expect(page.locator(`#${B}openNav`)).toBeHidden();
	await expect(page.locator(`#${B}nav .dialog-close`)).toBeHidden();
	const [drawer, main] = await Promise.all([box(page, 'nav'), page.locator('.drawer-main').boundingBox()]);
	expect(main.x).toBeGreaterThanOrEqual(drawer.x + drawer.width);
	await expect(page.getByRole('dialog', { name: 'Site menu' }).getByRole('navigation', { name: 'Main' })).toBeVisible();

	await page.locator('#outside').click();
	await page.keyboard.press('Escape');
	expect((await state(page, 'nav')).open).toBe(true);
});

/**
 * Narrow: the drawer is closed; the invoker opens it modally at the page edge, the
 * page behind is inert, and Esc closes it and returns the focus.
 */
test('DrawerTestCase: modal when narrow', async ({ page }) => {
	await open(page, NARROW);
	expect(await state(page, 'nav')).toEqual({ open: false, modal: false, visible: false });

	await openFrom(page, 'openNav');
	expect(await state(page, 'nav')).toEqual({ open: true, modal: true, visible: true });
	await expect.poll(async () => (await box(page, 'nav')).x).toBe(0);
	await expect(page.locator(`#${B}nav`)).toHaveAttribute('closedby', 'any');
	expect(await focused(page)).toBe('');
	await expect(page.getByRole('button', { name: 'Close' }).first()).toBeFocused();
	await expect(page.locator(`#${B}openNav`)).not.toBeInViewport({ ratio: 2 });
	expect(await page.evaluate(() => document.elementFromPoint(350, 400).id)).toBe(`${B}nav`);

	await page.keyboard.press('Escape');
	await expect.poll(async () => (await state(page, 'nav')).open).toBe(false);
	expect(await focused(page)).toBe(`${B}openNav`);
});

/**
 * Crossing DockAt switches the state both ways, a modal drawer included.
 */
test('DrawerTestCase: resizing crosses DockAt', async ({ page }) => {
	await open(page);
	await page.setViewportSize(NARROW);
	await expect.poll(async () => (await state(page, 'nav')).open).toBe(false);
	await expect(page.locator(`#${B}openNav`)).toBeVisible();

	await openFrom(page, 'openNav');
	expect((await state(page, 'nav')).modal).toBe(true);
	await page.setViewportSize(WIDE);
	await expect.poll(() => state(page, 'nav')).toEqual({ open: true, modal: false, visible: true });
	await expect(page.locator(`#${B}nav`)).not.toHaveAttribute('closedby');
});

/**
 * Without script: docked when wide, and inline in the page flow when narrow, with
 * the invokers hidden. The other drawers still open from their invokers.
 */
test('DrawerTestCase: without script', async ({ browser }) => {
	const context = await browser.newContext({ javaScriptEnabled: false, viewport: NARROW });
	const page = await context.newPage();
	await page.goto(`${GENERIC_BASE_URL}${PAGE_URL}`);
	await expect(page.locator(`#${B}nav`)).toBeVisible();
	await expect(page.locator('#home')).toBeVisible();
	await expect(page.locator(`#${B}openNav`)).toBeHidden();
	await expect(page.locator(`#${B}nav .dialog-close`)).toBeHidden();
	await expect(page.locator(`#${B}openFilters`)).toBeVisible();

	await page.setViewportSize(WIDE);
	await expect(page.locator(`#${B}nav`)).toBeVisible();
	await expect(page.locator(`#${B}openNav`)).toBeHidden();
	await context.close();
});

/**
 * An End drawer opens from the invoker at the inline end, posts back its field, and
 * closes with its close button without script.
 */
test('DrawerTestCase: End placement, close button, and postback', async ({ page }) => {
	await open(page);
	await openFrom(page, 'openFilters');
	await expect.poll(async () => {
		const drawer = await box(page, 'filters');
		return Math.round(drawer.x + drawer.width);
	}).toBe(WIDE.width);

	await page.getByRole('dialog', { name: 'Filters' }).getByRole('button', { name: 'Close' }).focus();
	await page.keyboard.press('Enter');
	expect((await state(page, 'filters')).open).toBe(false);
	expect(await focused(page)).toBe(`${B}openFilters`);

	await openFrom(page, 'openFilters');
	await page.getByLabel('Term').fill('red');
	await page.getByRole('button', { name: 'Apply' }).click();
	await expect(page.locator(`#${B}status`)).toHaveText('Applied: red');
});

/**
 * A click on the backdrop closes a modal drawer, in WebKit through the client
 * control because it lacks `closedby`; ClosedBy None keeps it open.
 */
test('DrawerTestCase: backdrop click and ClosedBy None', async ({ page }) => {
	await open(page);
	await openFrom(page, 'openFilters');
	await page.mouse.click(50, 400);
	await expect.poll(async () => (await state(page, 'filters')).open).toBe(false);

	await openFrom(page, 'openLocked');
	await page.mouse.click(1200, 400);
	await page.keyboard.press('Escape');
	expect((await state(page, 'locked')).open).toBe(true);
	await page.getByRole('dialog', { name: 'Locked' }).getByRole('button', { name: 'Close' }).click();
	expect((await state(page, 'locked')).open).toBe(false);
});

/**
 * A swipe toward the edge closes the drawer past a third of its size; a short slow
 * one snaps back; ClosedBy None does not swipe; a docked drawer does not swipe.
 */
test('DrawerTestCase: swipe', async ({ page }) => {
	await open(page, NARROW);
	await openFrom(page, 'openNav');
	expect(await swipe(page, `#${B}nav`, [200, 300], [170, 300])).toBe(true);
	expect((await state(page, 'nav')).open).toBe(true);
	expect(await swipe(page, `#${B}nav`, [300, 300], [50, 300])).toBe(true);
	await expect.poll(async () => (await state(page, 'nav')).open).toBe(false);

	await openFrom(page, 'openLocked');
	expect(await swipe(page, `#${B}locked`, [300, 300], [50, 300])).toBe(false);
	expect((await state(page, 'locked')).open).toBe(true);
	await page.getByRole('dialog', { name: 'Locked' }).getByRole('button', { name: 'Close' }).click();

	await page.setViewportSize(WIDE);
	await expect.poll(async () => (await state(page, 'nav')).open).toBe(true);
	expect(await swipe(page, `#${B}nav`, [200, 100], [10, 100])).toBe(false);
	expect((await state(page, 'nav')).open).toBe(true);
});

/**
 * A bottom sheet closes on a downward swipe from its scrolled list only when the
 * list is at the top.
 */
test('DrawerTestCase: bottom sheet swipe respects scrolled content', async ({ page }) => {
	await open(page, NARROW);
	await openFrom(page, 'openSheet');
	await expect.poll(async () => {
		const sheet = await box(page, 'sheet');
		return Math.round(sheet.y + sheet.height);
	}).toBe(NARROW.height);

	await page.locator('#sheetList').evaluate((el) => { el.scrollTop = 200; });
	expect(await swipe(page, '#sheetList', [100, 100], [100, 500])).toBe(false);
	expect((await state(page, 'sheet')).open).toBe(true);

	await page.locator('#sheetList').evaluate((el) => { el.scrollTop = 0; });
	expect(await swipe(page, '#sheetList', [100, 100], [100, 500])).toBe(true);
	await expect.poll(async () => (await state(page, 'sheet')).open).toBe(false);
});

/**
 * Real touch input closes the drawer, and a vertical drag in the bottom sheet's
 * list scrolls it. Chromium alone exposes touch input through its protocol.
 */
test('DrawerTestCase: real touch input', async ({ browser, browserName }) => {
	test.skip(browserName !== 'chromium', 'touch input needs the Chromium protocol');
	const context = await browser.newContext({ hasTouch: true, viewport: NARROW });
	const page = await context.newPage();
	await page.goto(`${GENERIC_BASE_URL}${PAGE_URL}`);
	const cdp = await context.newCDPSession(page);
	const drag = async (from, to) => {
		await cdp.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: from[0], y: from[1] }] });
		for (let step = 1; step <= 6; step++) {
			const x = from[0] + ((to[0] - from[0]) * step) / 6;
			const y = from[1] + ((to[1] - from[1]) * step) / 6;
			await cdp.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x, y }] });
		}
		await cdp.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
	};

	await openFrom(page, 'openNav');
	await expect.poll(async () => (await box(page, 'nav')).x).toBe(0);
	await drag([280, 400], [30, 400]);
	await expect.poll(async () => (await state(page, 'nav')).open).toBe(false);

	await openFrom(page, 'openSheet');
	await expect.poll(async () => {
		const sheet = await box(page, 'sheet');
		return Math.round(sheet.y + sheet.height);
	}).toBe(NARROW.height);
	const list = await page.locator('#sheetList').boundingBox();
	const center = list.y + list.height / 2;
	await page.locator('#sheetList').evaluate((el) => { el.scrollTop = 100; });
	await drag([100, center + 30], [100, center - 30]);
	await expect.poll(() => page.locator('#sheetList').evaluate((el) => el.scrollTop)).toBeGreaterThan(100);
	expect((await state(page, 'sheet')).open).toBe(true);
	await context.close();
});
