import { test, expect } from '@playwright/test';
import { PradoTestHelper, GENERIC_BASE_URL } from '../helpers.js';

const PAGE_URL = 'web/index.php?page=SafetyCoverMatrixTest';

// Each row: a Slide control whose overlay, once open, must translate toward the
// named edge. `axis`/`sign` are the expected computed-transform translate:
// y<0 up, y>0 down, x<0 left, x>0 right. `fade` is the expected OverlayFade.
// Forward/Backward are the logical directions; they resolve through the panel's
// content direction, so the LTR and RTL rows below prove the handedness flip.
const MATRIX = [
	// physical, fade off
	{ id: 'up_f0', axis: 'y', sign: -1, fade: false },
	{ id: 'down_f0', axis: 'y', sign: 1, fade: false },
	{ id: 'left_f0', axis: 'x', sign: -1, fade: false },
	{ id: 'right_f0', axis: 'x', sign: 1, fade: false },
	// physical, fade on
	{ id: 'up_f1', axis: 'y', sign: -1, fade: true },
	{ id: 'down_f1', axis: 'y', sign: 1, fade: true },
	{ id: 'left_f1', axis: 'x', sign: -1, fade: true },
	{ id: 'right_f1', axis: 'x', sign: 1, fade: true },
	// logical x handedness, fade off  (Forward → right in LTR, left in RTL)
	{ id: 'fwd_ltr_f0', axis: 'x', sign: 1, fade: false },
	{ id: 'bwd_ltr_f0', axis: 'x', sign: -1, fade: false },
	{ id: 'fwd_rtl_f0', axis: 'x', sign: -1, fade: false },
	{ id: 'bwd_rtl_f0', axis: 'x', sign: 1, fade: false },
	// logical x handedness, fade on
	{ id: 'fwd_ltr_f1', axis: 'x', sign: 1, fade: true },
	{ id: 'bwd_ltr_f1', axis: 'x', sign: -1, fade: true },
	{ id: 'fwd_rtl_f1', axis: 'x', sign: -1, fade: true },
	{ id: 'bwd_rtl_f1', axis: 'x', sign: 1, fade: true },
];

// Parse the translate (e = x, f = y) out of a computed `matrix(a,b,c,d,e,f)`.
function translateOf(transform) {
	const m = /matrix\(([^)]+)\)/.exec(transform);
	if (!m) {
		return { x: 0, y: 0 };
	}
	const p = m[1].split(',').map((n) => parseFloat(n));
	return { x: p[4], y: p[5] };
}

test('TSafetyCoverMatrixTestCase', async ({ page }) => {
	const h = new PradoTestHelper(page, GENERIC_BASE_URL);
	await h.url(PAGE_URL);
	await h.assertSourceContains('Safety Cover Matrix Test Case');

	for (const row of MATRIX) {
		const cid = `ctl0_Content_${row.id}`;

		// Click the guard to open, then wait for the open class (the face animates).
		await page.locator(`#${cid}_overlay`).click();
		await expect(page.locator(`#${cid}_slider`)).toHaveClass(/safety-cover-open/, { timeout: 2000 });
		await page.waitForTimeout(400); // let the slide transition settle

		// The face is the animated layer; read its settled transform + opacity.
		const { transform, opacity } = await page.locator(`#${cid}_face`).evaluate((el) => {
			const cs = getComputedStyle(el);
			return { transform: cs.transform, opacity: cs.opacity };
		});
		const t = translateOf(transform);

		const moved = row.axis === 'x' ? t.x : t.y;
		const still = row.axis === 'x' ? t.y : t.x;
		// Moves the right way along the expected axis...
		expect(Math.sign(moved), `${row.id}: expected ${row.axis} sign ${row.sign}, got translate x=${t.x} y=${t.y}`).toBe(row.sign);
		expect(Math.abs(moved), `${row.id}: expected motion on ${row.axis}`).toBeGreaterThan(10);
		// ...and does not drift on the other axis.
		expect(Math.abs(still), `${row.id}: unexpected motion off-axis (x=${t.x} y=${t.y})`).toBeLessThan(1);
		// Fade is independent: opacity 0 when on, 1 when off.
		expect(opacity, `${row.id}: OverlayFade=${row.fade} opacity`).toBe(row.fade ? '0' : '1');
	}

	// Helper: open a control and return its face's settled computed style.
	async function openAndRead(id) {
		const cid = `ctl0_Content_${id}`;
		await page.locator(`#${cid}_overlay`).click();
		await expect(page.locator(`#${cid}_slider`)).toHaveClass(/safety-cover-open/, { timeout: 2000 });
		await page.waitForTimeout(400);
		return page.locator(`#${cid}_face`).evaluate((el) => {
			const cs = getComputedStyle(el);
			return { clipPath: cs.clipPath, visibility: cs.visibility, opacity: cs.opacity };
		});
	}

	// Parse the four px insets out of `inset(t r b l)`.
	const insetOf = (clip) => {
		const m = /inset\(([^)]+)\)/.exec(clip);
		if (!m) {
			return null;
		}
		const p = m[1].split(/\s+/).map((n) => parseFloat(n));
		return { top: p[0], right: p[1], bottom: p[2] ?? p[0], left: p[3] ?? (p[1] ?? p[0]) };
	};

	// ── Collapse clips toward the named edge (content held in place) ──
	// Collapse Up clips 100% from the bottom (overlay collapses to the top edge).
	const cu = insetOf((await openAndRead('collapse_up')).clipPath);
	expect(cu, 'collapse_up should have an inset clip-path').not.toBeNull();
	expect(cu.bottom, 'collapse_up: bottom inset should be the panel height').toBeGreaterThan(10);
	expect(cu.top + cu.right + cu.left, 'collapse_up: other edges stay 0').toBeLessThan(1);
	// Collapse Right clips 100% from the left.
	const cr = insetOf((await openAndRead('collapse_right')).clipPath);
	expect(cr.left, 'collapse_right: left inset should be the panel width').toBeGreaterThan(10);
	expect(cr.top + cr.right + cr.bottom, 'collapse_right: other edges stay 0').toBeLessThan(1);

	// ── None with no fade snaps hidden via visibility; None+fade stays visible
	//    and drives opacity to 0 ──
	const np = await openAndRead('none_plain');
	expect(np.visibility, 'none_plain: overlay snaps hidden').toBe('hidden');
	const nf = await openAndRead('none_fade');
	expect(nf.visibility, 'none_fade: overlay stays visible (fades)').toBe('visible');
	expect(nf.opacity, 'none_fade: overlay fades to transparent').toBe('0');
});
