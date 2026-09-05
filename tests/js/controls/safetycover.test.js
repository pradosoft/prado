/**
 * Tests for Prado.WebUI.TSafetyCover (safetycover.js).
 * Source: framework/Web/Javascripts/source/prado/controls/safetycover.js
 *
 * DOM structure expected by TSafetyCover:
 *   <div id="{ID}" class="safety-cover">
 *     <div id="{ID}_slider" class="safety-cover-slider">
 *       <div id="{ID}_overlay" class="safety-cover-overlay">…</div>
 *     </div>
 *     <div id="{ID}_content" class="safety-cover-content">…</div>
 *   </div>
 *
 * The wrapper toggles the `safety-cover-open` class on the slider and the
 * `safety-cover-pulsate` class on the panel; the stylesheet supplies the
 * animations. Timers drive the opening and the overlay's return, so the tests run
 * on fake timers.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TSafetyCover } from '../adapters/safetycover.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

/**
 * Creates the control DOM structure and returns a registered wrapper for it.
 *
 * @param {object} options - extra wrapper options merged over {ID: 'guard'}
 */
function buildControl(options = {}) {
	document.body.innerHTML = `
		<div id="guard" class="safety-cover">
			<div id="guard_slider" class="safety-cover-slider">
				<div id="guard_overlay" class="safety-cover-overlay">Click to unlock</div>
			</div>
			<div id="guard_content" class="safety-cover-content"><button id="inner-button">Delete</button></div>
		</div>`;
	return new TSafetyCover(Object.assign({ ID: 'guard' }, options));
}

function slider() {
	return document.getElementById('guard_slider');
}

function panel() {
	return document.getElementById('guard');
}

function overlay() {
	return document.getElementById('guard_overlay');
}

beforeEach(() => {
	vi.useFakeTimers();
	document.body.innerHTML = '';
	global.Prado.Registry = {};
});

afterEach(() => {
	vi.useRealTimers();
	document.body.innerHTML = '';
	global.Prado.Registry = {};
});

// ─── registration ────────────────────────────────────────────────────────────

describe('registration', () => {
	it('registers itself in Prado.Registry under its ID', () => {
		const wrapper = buildControl();
		expect(global.Prado.Registry['guard']).toBe(wrapper);
	});

	it('starts closed', () => {
		const wrapper = buildControl();
		expect(wrapper.isOpen()).toBe(false);
		expect(slider().classList.contains('safety-cover-open')).toBe(false);
	});

	it('tolerates a missing DOM structure', () => {
		document.body.innerHTML = '<div id="lonely"></div>';
		const wrapper = new TSafetyCover({ ID: 'lonely' });
		expect(wrapper.isOpen()).toBe(false);
	});
});

// ─── options ─────────────────────────────────────────────────────────────────

describe('options', () => {
	it('defaults OpenDelay, AutoCloseDelay and MouseOutTimeout', () => {
		const wrapper = buildControl();
		expect(wrapper.getOpenDelay()).toBe(800);
		expect(wrapper.getAutoCloseDelay()).toBe(6000);
		expect(wrapper.getMouseOutTimeout()).toBe(1000);
	});

	it('accepts option overrides', () => {
		const wrapper = buildControl({ OpenDelay: 100, AutoCloseDelay: 2000, MouseOutTimeout: 300 });
		expect(wrapper.getOpenDelay()).toBe(100);
		expect(wrapper.getAutoCloseDelay()).toBe(2000);
		expect(wrapper.getMouseOutTimeout()).toBe(300);
	});
});

// ─── open ────────────────────────────────────────────────────────────────────

describe('open', () => {
	it('pulses first, then opens after OpenDelay', () => {
		const wrapper = buildControl();
		wrapper.open();
		expect(panel().classList.contains('safety-cover-pulsate')).toBe(true);
		expect(wrapper.isOpen()).toBe(false);
		vi.advanceTimersByTime(800);
		expect(panel().classList.contains('safety-cover-pulsate')).toBe(false);
		expect(slider().classList.contains('safety-cover-open')).toBe(true);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('opens on a click on the overlay', () => {
		const wrapper = buildControl();
		overlay().dispatchEvent(new MouseEvent('click', { bubbles: true }));
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('ignores a second open while pulsing or open', () => {
		const wrapper = buildControl();
		wrapper.open();
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.open();
		expect(wrapper.isOpen()).toBe(true);
	});

	it('closes on its own after AutoCloseDelay', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
		vi.advanceTimersByTime(6000);
		expect(wrapper.isOpen()).toBe(false);
		expect(slider().classList.contains('safety-cover-open')).toBe(false);
	});

	it('honors a custom AutoCloseDelay', () => {
		const wrapper = buildControl({ AutoCloseDelay: 2000 });
		wrapper.open();
		vi.advanceTimersByTime(800);
		vi.advanceTimersByTime(1999);
		expect(wrapper.isOpen()).toBe(true);
		vi.advanceTimersByTime(1);
		expect(wrapper.isOpen()).toBe(false);
	});
});

// ─── close ───────────────────────────────────────────────────────────────────

describe('close', () => {
	it('closes immediately and cancels pending timers', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.close();
		expect(wrapper.isOpen()).toBe(false);
		vi.advanceTimersByTime(60000);
		expect(wrapper.isOpen()).toBe(false);
	});

	it('cancels a pending open', () => {
		const wrapper = buildControl();
		wrapper.open();
		wrapper.close();
		vi.advanceTimersByTime(60000);
		expect(wrapper.isOpen()).toBe(false);
		expect(panel().classList.contains('safety-cover-pulsate')).toBe(false);
	});

	it('can open again after the close cooldown', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.close();
		vi.advanceTimersByTime(250); // past the close animation cooldown (AnimationDuration)
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});
});

// ─── close cooldown (no reopen mid-animation) ────────────────────────────────

describe('close cooldown', () => {
	it('ignores a reopen during the close animation', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.close();
		wrapper.open(); // click during the close animation — must be ignored
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(false);
	});

	it('becomes reopenable once the close animation ends', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.close();
		vi.advanceTimersByTime(249); // still within the 250ms animation cooldown
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(false); // reopen was still ignored
		vi.advanceTimersByTime(1); // cooldown elapses
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('ResetDelay extends the cooldown past the animation', () => {
		const wrapper = buildControl({ AnimationDuration: 250, ResetDelay: 500 });
		wrapper.open();
		vi.advanceTimersByTime(800);
		wrapper.close();
		vi.advanceTimersByTime(600); // past the animation but within animation+ResetDelay (750)
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(false); // still in cooldown
		vi.advanceTimersByTime(200); // now past 750
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('a cancelled pulse has no cooldown (open never completed)', () => {
		const wrapper = buildControl();
		wrapper.open();      // pulsing, not yet open
		wrapper.close();     // cancel before it opened — no animation, no cooldown
		wrapper.open();      // should start a fresh open immediately
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});
});

// ─── mouse behavior ──────────────────────────────────────────────────────────

describe('mouse behavior', () => {
	it('closes after the pointer leaves an open panel', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		panel().dispatchEvent(new MouseEvent('mouseleave'));
		vi.advanceTimersByTime(1000);
		expect(wrapper.isOpen()).toBe(false);
	});

	it('cancels the pending close when the pointer re-enters', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		panel().dispatchEvent(new MouseEvent('mouseleave'));
		vi.advanceTimersByTime(500);
		panel().dispatchEvent(new MouseEvent('mouseenter'));
		vi.advanceTimersByTime(5000);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('ignores mouseleave while closed', () => {
		const wrapper = buildControl();
		panel().dispatchEvent(new MouseEvent('mouseleave'));
		vi.advanceTimersByTime(5000);
		expect(wrapper.isOpen()).toBe(false);
	});
});

// ─── accessibility ─────────────────────────────────────────────────────────

// The content is guarded when it is inert (modern browsers) or, in the fallback
// path, hidden from assistive tech with aria-hidden.
function contentGuarded() {
	const c = document.getElementById('guard_content');
	return c.inert === true || c.getAttribute('aria-hidden') === 'true';
}

function keydown(key) {
	overlay().dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
}

describe('accessibility', () => {
	it('guards the content from keyboard and AT while closed', () => {
		buildControl();
		expect(contentGuarded()).toBe(true);
	});

	it('reveals the content on open and re-guards it on close', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(contentGuarded()).toBe(false);
		wrapper.close();
		expect(contentGuarded()).toBe(true);
	});

	it('opens on Enter on the guard', () => {
		const wrapper = buildControl();
		keydown('Enter');
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('opens on Space on the guard', () => {
		const wrapper = buildControl();
		keydown(' ');
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('ignores other keys', () => {
		const wrapper = buildControl();
		keydown('a');
		vi.advanceTimersByTime(800);
		expect(wrapper.isOpen()).toBe(false);
	});

	it('reflects state in aria-expanded', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(overlay().getAttribute('aria-expanded')).toBe('true');
		wrapper.close();
		expect(overlay().getAttribute('aria-expanded')).toBe('false');
	});

	it('moves focus into the content on a keyboard open', () => {
		buildControl();
		keydown('Enter');
		vi.advanceTimersByTime(800);
		expect(document.activeElement.id).toBe('inner-button');
	});

	it('does not move focus on a mouse open', () => {
		const wrapper = buildControl();
		wrapper.open(false);
		vi.advanceTimersByTime(800);
		expect(document.activeElement.id).not.toBe('inner-button');
	});

	it('is idempotent: repeated guarding does not corrupt the tab order (fallback)', () => {
		// jsdom has no inert, so this exercises the aria-hidden/tabindex fallback.
		const wrapper = buildControl();
		wrapper.setContentGuarded(true); // second guard(true) after onInit's — must be a no-op
		wrapper.open();
		vi.advanceTimersByTime(800);
		// The button's original tabindex (none) is restored, so it is reachable.
		expect(document.getElementById('inner-button').getAttribute('tabindex')).toBeNull();
	});

	it('drops the guard from the tab order while open and restores it on close', () => {
		const wrapper = buildControl();
		wrapper.open();
		vi.advanceTimersByTime(800);
		expect(overlay().getAttribute('tabindex')).toBe('-1');
		wrapper.close();
		expect(overlay().getAttribute('tabindex')).toBe('0');
	});

	it('restores the content on deinitialize so a later wrapper starts clean', () => {
		const wrapper = buildControl();
		expect(contentGuarded()).toBe(true);
		wrapper.deinitialize();
		expect(contentGuarded()).toBe(false);
		// fallback: the guarded button's original tabindex is restored, not left at -1
		expect(document.getElementById('inner-button').getAttribute('tabindex')).toBeNull();
	});

	it('acknowledges activation in aria-expanded immediately, before the pulse', () => {
		const wrapper = buildControl();
		wrapper.open();
		expect(overlay().getAttribute('aria-expanded')).toBe('true');
		expect(wrapper.isOpen()).toBe(false); // still pulsing, not yet open
	});

	it('leaves no stray tabindex on content that has no focusable', () => {
		document.body.innerHTML = `
			<div id="guard" class="safety-cover">
				<div id="guard_slider" class="safety-cover-slider">
					<div id="guard_overlay" class="safety-cover-overlay"></div>
				</div>
				<div id="guard_content" class="safety-cover-content"><span>no focusable</span></div>
			</div>`;
		new TSafetyCover({ ID: 'guard' });
		keydown('Enter');
		vi.advanceTimersByTime(800);
		expect(document.getElementById('guard_content').hasAttribute('tabindex')).toBe(false);
	});
});

// ─── keep open while active ──────────────────────────────────────────────────

describe('keep open while active', () => {
	it('resets the close timer on activity when enabled', () => {
		const wrapper = buildControl({ KeepOpenWhileActive: true, AutoCloseDelay: 6000 });
		wrapper.open();
		vi.advanceTimersByTime(800); // opened; close timer set for 6000
		vi.advanceTimersByTime(5000); // t≈5800, close pending at ≈6800
		panel().dispatchEvent(new MouseEvent('mousemove', { bubbles: true })); // reset → 6000 more
		vi.advanceTimersByTime(5000); // would have closed at 6800 without the reset
		expect(wrapper.isOpen()).toBe(true);
		vi.advanceTimersByTime(6000); // now idle past AutoCloseDelay
		expect(wrapper.isOpen()).toBe(false);
	});

	it('ignores activity when disabled (default)', () => {
		const wrapper = buildControl({ AutoCloseDelay: 6000 });
		wrapper.open();
		vi.advanceTimersByTime(800);
		vi.advanceTimersByTime(5000);
		panel().dispatchEvent(new MouseEvent('mousemove', { bubbles: true }));
		vi.advanceTimersByTime(1000); // total idle 6000
		expect(wrapper.isOpen()).toBe(false);
	});

	it('resets on keydown as well as mousemove', () => {
		const wrapper = buildControl({ KeepOpenWhileActive: true, AutoCloseDelay: 6000 });
		wrapper.open();
		vi.advanceTimersByTime(800);
		vi.advanceTimersByTime(5000);
		panel().dispatchEvent(new KeyboardEvent('keydown', { key: 'a', bubbles: true }));
		vi.advanceTimersByTime(5000);
		expect(wrapper.isOpen()).toBe(true);
	});

	it('cancels a pending mouse-out close on activity', () => {
		const wrapper = buildControl({ KeepOpenWhileActive: true, AutoCloseDelay: 6000, MouseOutTimeout: 1000 });
		wrapper.open();
		vi.advanceTimersByTime(800);
		panel().dispatchEvent(new MouseEvent('mouseleave')); // schedule mouse-out close at +1000
		panel().dispatchEvent(new KeyboardEvent('keydown', { key: 'a', bubbles: true })); // activity cancels it
		vi.advanceTimersByTime(1500);
		expect(wrapper.isOpen()).toBe(true);
	});
});

// ─── deinitialize ────────────────────────────────────────────────────────────

describe('deinitialize', () => {
	it('clears pending timers on deinitialize', () => {
		const wrapper = buildControl();
		wrapper.open();
		wrapper.deinitialize();
		expect(() => vi.advanceTimersByTime(60000)).not.toThrow();
		expect(wrapper.isOpen()).toBe(false);
	});
});
