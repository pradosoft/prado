/**
 * Tests for Prado.WebUI.TTooltip (tooltip.js).
 * Source: framework/Web/Javascripts/source/prado/controls/tooltip.js
 *
 * Strategy
 * --------
 * jsdom has no Popover API, so the tooltip element gets stand-in
 * `showPopover()`, `hidePopover()`, and `matches(':popover-open')` that track an
 * open flag and record the `source` option. Fake timers drive ShowDelay and
 * HideDelay.
 *
 * ESM note: only tests/js/adapters/tooltip.js changes on ESM conversion.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TTooltip } from '../adapters/tooltip.js';

let control;
let tip;
let target;

/**
 * Builds the target and the tooltip, with a stand-in Popover API on the tooltip.
 * @param {string} describedBy - the initial aria-describedby of the target
 */
function build(describedBy = '') {
	document.body.innerHTML =
		`<input id="name"${describedBy ? ` aria-describedby="${describedBy}"` : ''}>` +
		'<div id="tip" popover="hint" role="tooltip">Your full name</div>' +
		'<dialog id="dlg"></dialog>';
	target = document.getElementById('name');
	tip = document.getElementById('tip');
	tip.open = false;
	tip.source = null;
	tip.showPopover = vi.fn((options) => { tip.open = true; tip.source = options?.source ?? null; });
	tip.hidePopover = vi.fn(() => { tip.open = false; });
	const matches = tip.matches.bind(tip);
	tip.matches = (selector) => (selector === ':popover-open' ? tip.open : matches(selector));
}

function create(options = {}) {
	control = new TTooltip({ ID: 'tip', TargetID: 'name', ShowDelay: 300, HideDelay: 150, ...options });
	return control;
}

const fire = (el, type) => el.dispatchEvent(new Event(type, { bubbles: type === 'focusin' || type === 'focusout' }));

beforeEach(() => {
	vi.useFakeTimers();
	build();
	control = null;
});

afterEach(() => {
	if (control && control.registered)
		control.deinitialize();
	vi.useRealTimers();
	document.body.innerHTML = '';
});

describe('Prado.WebUI.TTooltip', () => {
	it('names the tooltip in the aria-describedby of the target, once', () => {
		build('hint');
		create();
		expect(target.getAttribute('aria-describedby')).toBe('hint tip');
		TTooltip.describe(target, 'tip');
		expect(target.getAttribute('aria-describedby')).toBe('hint tip');
	});

	it('does nothing without its target', () => {
		create({ TargetID: 'missing' });
		expect(control.target).toBeNull();
		fire(target, 'focusin');
		vi.runAllTimers();
		expect(tip.showPopover).not.toHaveBeenCalled();
	});

	it('shows after ShowDelay of hover, anchored to the target', () => {
		create();
		fire(target, 'pointerenter');
		vi.advanceTimersByTime(299);
		expect(tip.open).toBe(false);
		vi.advanceTimersByTime(1);
		expect(tip.open).toBe(true);
		expect(tip.source).toBe(target);
	});

	it('does not show when the pointer leaves within ShowDelay', () => {
		create();
		fire(target, 'pointerenter');
		vi.advanceTimersByTime(100);
		fire(target, 'pointerleave');
		vi.runAllTimers();
		expect(tip.showPopover).not.toHaveBeenCalled();
	});

	it('hides HideDelay after the pointer leaves', () => {
		create();
		fire(target, 'pointerenter');
		vi.advanceTimersByTime(300);
		fire(target, 'pointerleave');
		vi.advanceTimersByTime(149);
		expect(tip.open).toBe(true);
		vi.advanceTimersByTime(1);
		expect(tip.open).toBe(false);
	});

	it('stays open while the pointer moves onto the tooltip (hoverable)', () => {
		create();
		fire(target, 'pointerenter');
		vi.advanceTimersByTime(300);
		fire(target, 'pointerleave');
		vi.advanceTimersByTime(100);
		fire(tip, 'pointerenter');
		vi.runAllTimers();
		expect(tip.open).toBe(true);
		fire(tip, 'pointerleave');
		vi.runAllTimers();
		expect(tip.open).toBe(false);
	});

	it('shows at once on focus and stays while focused after the pointer leaves', () => {
		create();
		fire(target, 'focusin');
		vi.advanceTimersByTime(0);
		expect(tip.open).toBe(true);
		fire(target, 'pointerenter');
		fire(target, 'pointerleave');
		vi.runAllTimers();
		expect(tip.open).toBe(true);
		fire(target, 'focusout');
		vi.runAllTimers();
		expect(tip.open).toBe(false);
	});

	it('hides on Esc and keeps the key from an enclosing dialog', () => {
		create();
		fire(target, 'focusin');
		vi.runAllTimers();
		const event = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true });
		target.dispatchEvent(event);
		expect(tip.open).toBe(false);
		expect(event.defaultPrevented).toBe(true);
	});

	it('leaves Esc alone while hidden', () => {
		create();
		const event = new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true });
		target.dispatchEvent(event);
		expect(event.defaultPrevented).toBe(false);
		expect(tip.hidePopover).not.toHaveBeenCalled();
	});

	it('cancels a pending show when deinitialized', () => {
		create();
		fire(target, 'pointerenter');
		control.deinitialize();
		vi.runAllTimers();
		expect(tip.showPopover).not.toHaveBeenCalled();
	});
});
