import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SafetyCoverTest';

// Read the two-layer state: the open flag, the guard's pointer-events (auto when
// blocking, none when open), and the face's opacity (the animated visible skin).
const state = (page, id) =>
	page.evaluate((cid) => {
		const slider = document.getElementById(`${cid}_slider`);
		const guard = document.getElementById(`${cid}_overlay`);
		const face = document.getElementById(`${cid}_face`);
		return {
			open: slider.classList.contains('safety-cover-open'),
			guardPointerEvents: getComputedStyle(guard).pointerEvents,
			faceOpacity: getComputedStyle(face).opacity,
		};
	}, id);

// Wait until the cover fully guards: closed, the guard intercepts pointer events,
// and the face is fully opaque (settled back over the content).
async function expectGuarding(page, id, timeout = 4000) {
	await expect
		.poll(() => state(page, id), { timeout })
		.toEqual({ open: false, guardPointerEvents: 'auto', faceOpacity: '1' });
}

// Wait until the cover is open: the guard lets clicks through to the content.
async function expectOpen(page, id, timeout = 3000) {
	await expect
		.poll(() => state(page, id).then((s) => s.open && s.guardPointerEvents === 'none'), { timeout })
		.toBe(true);
}

// The page has two independent controls, each demonstrating ONE close path with
// a single open/close cycle, so neither ever reopens:
//   autoCover     Collapse + Forward + OverlayFade, closes on the auto-timeout
//   mouseoutCover Slide + Up, closes when the pointer leaves the panel
test('TSafetyCoverTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Safety Cover Test Case');

	const auto = 'ctl0_Content_autoCover';
	const mouseout = 'ctl0_Content_mouseoutCover';

	// ── Rendered effect/direction/fade classes on each control ──
	await expect(page.locator(`#${auto}`)).toHaveClass(/safety-cover-collapse/);
	await expect(page.locator(`#${auto}`)).toHaveClass(/safety-cover-right/); // Forward → Right in LTR
	await expect(page.locator(`#${auto}`)).toHaveClass(/safety-cover-fade/);
	await expect(page.locator(`#${mouseout}`)).toHaveClass(/safety-cover-slide/);
	await expect(page.locator(`#${mouseout}`)).toHaveClass(/safety-cover-up/);
	await expect(page.locator(`#${mouseout}`)).not.toHaveClass(/safety-cover-fade/);

	// ── OpenDelay and AnimationDuration drive their custom properties ──
	await expect(page.locator(`#${auto}`).evaluate((el) => el.style.getPropertyValue('--safety-cover-open-delay'))).resolves.toBe('200ms');
	await expect(page.locator(`#${auto}`).evaluate((el) => el.style.getPropertyValue('--safety-cover-animation-duration'))).resolves.toBe('250ms');

	// ── OverlayColor renders on the visible face, not the transparent guard ──
	await expect(page.locator(`#${auto}_face`)).toHaveCSS('background-color', 'rgb(0, 0, 255)');
	await expect(page.locator(`#${auto}_overlay`)).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');

	// ── The content isolates its stacking so a high-z-index descendant cannot
	//    paint above the cover (part of the CSS contract) ──
	await expect(page.locator(`#${auto}_content`)).toHaveCSS('isolation', 'isolate');

	// ── Both wrappers register under their ClientIDs ──
	expect(await page.evaluate((id) => typeof Prado.Registry[id] === 'object', auto)).toBe(true);
	expect(await page.evaluate((id) => typeof Prado.Registry[id] === 'object', mouseout)).toBe(true);

	// ── auto-close control: open (face fades to transparent), content reachable,
	//    then it re-guards on its own after AutoCloseDelay ──
	await expectGuarding(page, auto);
	await page.locator(`#${auto}_overlay`).click();
	await expectOpen(page, auto);
	await expect(page.locator(`#${auto}_face`)).toHaveCSS('opacity', '0'); // OverlayFade
	await page.locator('#autoButton').click();
	await expect(page.locator('#autoResult')).toHaveText('clicked');
	await expectGuarding(page, auto, 6000);

	// ── mouse-out control: open, content reachable, then the pointer leaves and it
	//    closes. The payoff of the two-layer design: the guard blocks the content
	//    the INSTANT close begins and stays blocking through the whole face
	//    animation, so the button is never reachable while the cover returns. ──
	await expectGuarding(page, mouseout);
	await page.locator(`#${mouseout}_overlay`).click();
	await expectOpen(page, mouseout);
	await page.locator('#mouseoutButton').click();
	await expect(page.locator('#mouseoutResult')).toHaveText('clicked');

	await page.mouse.move(0, 0);
	// Wait for close to begin (open class drops), then sample the topmost
	// hit-testable element over the button through the face's return animation.
	await expect(page.locator(`#${mouseout}_slider`)).not.toHaveClass(/safety-cover-open/, { timeout: 2000 });
	const topmost = await page.evaluate(async (cid) => {
		const btn = document.getElementById('mouseoutButton');
		const r = btn.getBoundingClientRect();
		const cx = r.left + r.width / 2, cy = r.top + r.height / 2;
		const seen = [];
		for (let i = 0; i < 14; i++) {
			const el = document.elementFromPoint(cx, cy);
			seen.push(el ? el.id || el.className : 'null');
			await new Promise((res) => setTimeout(res, 20));
		}
		return seen;
	}, mouseout);
	// The guarded button is never topmost while the cover is returning.
	expect(topmost.includes('mouseoutButton'), `button exposed during close: ${topmost.join(', ')}`).toBe(false);

	await expectGuarding(page, mouseout);
});
