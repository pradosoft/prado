import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=ViewTransitionTest';
const PAGE_LEVEL_URL = 'web/index.php?page=ViewTransitionPageTest';
const B = 'ctl0_Content_';

/**
 * Counts the calls of document.startViewTransition in window.__transitions.
 */
async function spy(page) {
	await page.addInitScript(() => {
		window.__transitions = 0;
		const start = Document.prototype.startViewTransition;
		if (start) {
			Document.prototype.startViewTransition = function (...args) {
				window.__transitions++;
				return start.apply(this, args);
			};
		}
	});
}

async function open(page, url = PAGE_URL, heading = 'View Transition Test Case') {
	await spy(page);
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(url);
	await h.assertSourceContains(heading);
	return h;
}

const supported = (page) => page.evaluate(() => typeof document.startViewTransition === 'function');
const transitions = (page) => page.evaluate(() => window.__transitions);

async function refresh(page, count) {
	await page.locator(`#${B}refresh`).click();
	await expect(page.locator(`#${B}count`)).toHaveText(String(count));
}

/**
 * One response re-renders the panel and sets a label: one transition applies both,
 * the inline script of the panel runs once, onSuccess sees the old page and
 * onComplete the updated one.
 */
test('TViewTransitionTestCase: a ViewTransition control applies the whole response in one transition', async ({ page }) => {
	await open(page);
	test.skip(!(await supported(page)), 'no document.startViewTransition');
	await expect(page.locator(`#${B}results`)).toHaveAttribute('style', /view-transition-name:\s*results/);
	await refresh(page, 1);
	await expect(page.locator(`#${B}status`)).toHaveText('posted ');
	await expect.poll(() => page.evaluate(() => window.completeSaw)).toBe('1');
	expect(await page.evaluate(() => ({
		transitions: window.__transitions,
		panelRuns: window.panelRuns,
		successSaw: window.successSaw,
	}))).toEqual({ transitions: 1, panelRuns: 2, successSaw: '0' });
});

/**
 * A response that re-renders only a control without ViewTransition applies its update directly.
 */
test('TViewTransitionTestCase: a control without ViewTransition updates without a transition', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}plain`).click();
	await expect(page.locator('#otherMarker')).toHaveText('other 1');
	expect(await transitions(page)).toBe(0);
});

/**
 * TCallbackClientScript::setViewTransition() in a handler transitions a response that re-renders nothing.
 */
test('TViewTransitionTestCase: a callback handler turns the transition on', async ({ page }) => {
	await open(page);
	test.skip(!(await supported(page)), 'no document.startViewTransition');
	await page.locator(`#${B}handler`).click();
	await expect(page.locator(`#${B}status`)).toHaveText('handler');
	expect(await transitions(page)).toBe(1);
});

/**
 * A callback queued while the previous response waits for its transition posts the updated inputs.
 */
test('TViewTransitionTestCase: the next callback posts the inputs of the updated page', async ({ page }) => {
	await open(page);
	test.skip(!(await supported(page)), 'no document.startViewTransition');
	await page.evaluate((id) => {
		const button = document.getElementById(id);
		button.click();
		button.click();
	}, `${B}refresh`);
	await expect(page.locator(`#${B}count`)).toHaveText('2');
	await expect(page.locator(`#${B}status`)).toHaveText('posted 1');
	expect(await transitions(page)).toBe(2);
});

/**
 * prefers-reduced-motion: reduce applies the update at once, and onComplete still sees it.
 */
test('TViewTransitionTestCase: prefers-reduced-motion applies the update directly', async ({ page }) => {
	await page.emulateMedia({ reducedMotion: 'reduce' });
	await open(page);
	await refresh(page, 1);
	await expect(page.locator(`#${B}status`)).toHaveText('posted ');
	expect(await page.evaluate(() => ({ transitions: window.__transitions, completeSaw: window.completeSaw, panelRuns: window.panelRuns })))
		.toEqual({ transitions: 0, completeSaw: '1', panelRuns: 2 });
});

/**
 * Without document.startViewTransition the update applies at once.
 */
test('TViewTransitionTestCase: without view transition support the update applies directly', async ({ page }) => {
	await page.addInitScript(() => { delete Document.prototype.startViewTransition; });
	await open(page);
	expect(await supported(page)).toBe(false);
	await refresh(page, 1);
	await refresh(page, 2);
	await expect(page.locator(`#${B}status`)).toHaveText('posted 1');
	expect(await page.evaluate(() => window.completeSaw)).toBe('2');
});

/**
 * A failing client function inside the transition is a callback exception; the
 * other actions apply, and the page keeps taking callbacks.
 */
test('TViewTransitionTestCase: an action error surfaces as a callback exception and the page keeps working', async ({ page }) => {
	await open(page);
	page.on('pageerror', (e) => { throw e; });
	await page.locator(`#${B}broken`).click();
	await expect(page.locator(`#${B}status`)).toHaveText('after broken');
	await expect.poll(() => page.evaluate(() => window.exceptions)).toBe(1);
	await refresh(page, 1);
	await expect(page.locator(`#${B}status`)).toHaveText('posted ');
});

/**
 * Accessibility: the keyboard user keeps focus on the control that sent the callback.
 */
test('TViewTransitionTestCase: focus stays on the button that sent the callback', async ({ page }) => {
	await open(page);
	await page.locator(`#${B}refresh`).focus();
	await page.keyboard.press('Enter');
	await expect(page.locator(`#${B}count`)).toHaveText('1');
	await expect(page.locator(`#${B}refresh`)).toBeFocused();
	await page.keyboard.press('Enter');
	await expect(page.locator(`#${B}count`)).toHaveText('2');
});

/**
 * TPage.CallbackViewTransition transitions every response with client actions.
 */
test('TViewTransitionTestCase: CallbackViewTransition on the page transitions every response with actions', async ({ page }) => {
	await open(page, PAGE_LEVEL_URL, 'View Transition Page Test Case');
	test.skip(!(await supported(page)), 'no document.startViewTransition');
	await page.locator(`#${B}update`).click();
	await expect(page.locator(`#${B}status`)).toHaveText('updated');
	expect(await transitions(page)).toBe(1);
	const response = page.waitForResponse((r) => r.request().method() === 'POST');
	await page.locator(`#${B}nothing`).click();
	const text = await (await response).text();
	expect(text).not.toContain('X-PRADO-VIEWTRANSITION');
	expect(await transitions(page)).toBe(1);
});
