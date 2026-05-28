/**
 * Tests for Prado.WebUI.TSlider (slider.js).
 * Source: framework/Web/Javascripts/source/prado/controls/slider.js
 *
 * DOM structure expected by TSlider:
 *   <div  id="{ID}_track">    — the slider rail
 *   <div  id="{ID}_handle">   — the draggable thumb
 *   <div  id="{ID}_progress"> — (optional) filled portion before the handle
 *   <input id="{ID}_1" …>     — hidden field that stores the current value
 *
 * Implementation notes:
 *   - initializeTrack() bails out early when the track is not :visible.
 *     In jsdom every element is invisible by default (no real layout engine),
 *     so we must either (a) make the track visible via inline style or
 *     (b) call initializeTrack() explicitly after setting up mocks.
 *   - offsetWidth / offsetHeight return 0 in jsdom; we stub them on the
 *     specific elements we care about so the pixel calculations work.
 *   - The slider binds document-level mouseup/mousemove; those handlers are
 *     cleaned up in afterEach via deinitialize().
 *
 * ESM note: only tests/js/adapters/slider.js changes on ESM conversion.
 */

import { TSlider } from '../adapters/slider.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

const SLIDER_ID = 'slider1';

/**
 * Build the slider DOM and return an options hash for TSlider.
 *
 * @param {object} extra - merged into the returned options
 */
function buildDOM(extra = {}) {
	// Track — give it a visible style and non-zero offsetWidth so
	// initializeTrack() can compute lengths.
	const track = document.createElement('div');
	track.id = SLIDER_ID + '_track';
	track.style.display = 'block';
	track.style.width = '200px';
	track.style.height = '20px';
	// jsdom doesn't compute layout; stub offsetWidth/Height.
	Object.defineProperty(track, 'offsetWidth', { configurable: true, value: 200 });
	Object.defineProperty(track, 'offsetHeight', { configurable: true, value: 20 });
	document.body.appendChild(track);

	// Handle — 20px wide
	const handle = document.createElement('div');
	handle.id = SLIDER_ID + '_handle';
	handle.style.display = 'block';
	handle.style.width = '20px';
	Object.defineProperty(handle, 'offsetWidth', { configurable: true, value: 20 });
	Object.defineProperty(handle, 'offsetHeight', { configurable: true, value: 20 });
	document.body.appendChild(handle);

	// Progress bar (optional, but most tests include it)
	const progress = document.createElement('div');
	progress.id = SLIDER_ID + '_progress';
	progress.style.display = 'block';
	document.body.appendChild(progress);

	// Hidden value field
	const hidden = document.createElement('input');
	hidden.type = 'hidden';
	hidden.id = SLIDER_ID + '_1';
	hidden.value = '0';
	document.body.appendChild(hidden);

	// Make track :visible in jQuery's eyes by ensuring it is in the document
	// and has a non-zero size (jQuery uses offsetWidth for visibility checks).
	// We achieve this by overriding jQuery's :visible pseudo-class result via
	// stubbing $.fn.is for this element.  Simpler: patch $.fn.is globally for
	// the duration of tests so that ":visible" always returns true.

	return Object.assign(
		{
			ID: SLIDER_ID,
			axis: 'horizontal',
			range: [0, 100],
			minimum: 0,
			maximum: 100,
			sliderValue: 0,
			disabled: false,
			AutoPostBack: false,
		},
		extra,
	);
}

/** Create a fresh TSlider instance, clearing the Prado registry first. */
function makeSlider(extra = {}) {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}

	// Patch jQuery's :visible check so initializeTrack() doesn't bail out.
	const origIs = global.jQuery.fn.is;
	global.jQuery.fn.is = function (sel) {
		if (sel === ':visible') return true;
		return origIs.call(this, sel);
	};

	const options = buildDOM(extra);
	const slider = new TSlider(options);

	global.jQuery.fn.is = origIs;

	return slider;
}

afterEach(() => {
	// Deinitialize registered slider to remove document-level event listeners.
	const reg = global.Prado.Registry[SLIDER_ID];
	if (reg && reg.registered) {
		reg.deinitialize();
	}
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

// ─── Class shape ─────────────────────────────────────────────────────────────

describe('TSlider class shape', () => {
	it('is a constructor function', () => {
		expect(typeof TSlider).toBe('function');
	});

	it('prototype has onInit', () => {
		expect(typeof TSlider.prototype.onInit).toBe('function');
	});

	it('prototype has initializeTrack', () => {
		expect(typeof TSlider.prototype.initializeTrack).toBe('function');
	});

	it('prototype has setValue', () => {
		expect(typeof TSlider.prototype.setValue).toBe('function');
	});

	it('prototype has setValueBy', () => {
		expect(typeof TSlider.prototype.setValueBy).toBe('function');
	});

	it('prototype has getNearestValue', () => {
		expect(typeof TSlider.prototype.getNearestValue).toBe('function');
	});

	it('prototype has translateToPx', () => {
		expect(typeof TSlider.prototype.translateToPx).toBe('function');
	});

	it('prototype has translateToValue', () => {
		expect(typeof TSlider.prototype.translateToValue).toBe('function');
	});

	it('prototype has minimumOffset', () => {
		expect(typeof TSlider.prototype.minimumOffset).toBe('function');
	});

	it('prototype has maximumOffset', () => {
		expect(typeof TSlider.prototype.maximumOffset).toBe('function');
	});

	it('prototype has isVertical', () => {
		expect(typeof TSlider.prototype.isVertical).toBe('function');
	});

	it('prototype has updateStyles', () => {
		expect(typeof TSlider.prototype.updateStyles).toBe('function');
	});

	it('prototype has startDrag', () => {
		expect(typeof TSlider.prototype.startDrag).toBe('function');
	});

	it('prototype has update', () => {
		expect(typeof TSlider.prototype.update).toBe('function');
	});

	it('prototype has draw', () => {
		expect(typeof TSlider.prototype.draw).toBe('function');
	});

	it('prototype has endDrag', () => {
		expect(typeof TSlider.prototype.endDrag).toBe('function');
	});

	it('prototype has finishDrag', () => {
		expect(typeof TSlider.prototype.finishDrag).toBe('function');
	});

	it('prototype has updateFinished', () => {
		expect(typeof TSlider.prototype.updateFinished).toBe('function');
	});

	it('prototype has setDisabled', () => {
		expect(typeof TSlider.prototype.setDisabled).toBe('function');
	});

	it('prototype has setEnabled', () => {
		expect(typeof TSlider.prototype.setEnabled).toBe('function');
	});

	it('prototype has doPostback', () => {
		expect(typeof TSlider.prototype.doPostback).toBe('function');
	});
});

// ─── onInit — basic wiring ────────────────────────────────────────────────────

describe('TSlider.onInit', () => {
	it('constructs without throwing', () => {
		expect(() => makeSlider()).not.toThrow();
	});

	it('stores options', () => {
		const s = makeSlider();
		expect(s.options.ID).toBe(SLIDER_ID);
	});

	it('resolves track element', () => {
		const s = makeSlider();
		expect(s.track).not.toBeNull();
		expect(s.track.id).toBe(SLIDER_ID + '_track');
	});

	it('resolves handle element', () => {
		const s = makeSlider();
		expect(s.handle).not.toBeNull();
		expect(s.handle.id).toBe(SLIDER_ID + '_handle');
	});

	it('resolves progress element', () => {
		const s = makeSlider();
		expect(s.progress).not.toBeNull();
		expect(s.progress.id).toBe(SLIDER_ID + '_progress');
	});

	it('resolves hiddenField', () => {
		const s = makeSlider();
		expect(s.hiddenField).not.toBeNull();
		expect(s.hiddenField.id).toBe(SLIDER_ID + '_1');
	});

	it('sets axis to horizontal by default', () => {
		const s = makeSlider();
		expect(s.axis).toBe('horizontal');
	});

	it('sets axis to vertical when option provided', () => {
		const s = makeSlider({ axis: 'vertical' });
		expect(s.axis).toBe('vertical');
	});

	it('sets minimum from options', () => {
		const s = makeSlider({ minimum: 10, range: [10, 90] });
		expect(s.minimum).toBe(10);
	});

	it('sets maximum from options', () => {
		const s = makeSlider({ maximum: 90, range: [0, 90] });
		expect(s.maximum).toBe(90);
	});

	it('starts with active = false', () => {
		const s = makeSlider();
		expect(s.active).toBe(false);
	});

	it('starts with dragging = false', () => {
		const s = makeSlider();
		expect(s.dragging).toBe(false);
	});

	it('starts with disabled = false when options.disabled is falsy', () => {
		const s = makeSlider({ disabled: false });
		expect(s.disabled).toBe(false);
	});

	it('sets initialized = true after construction', () => {
		const s = makeSlider();
		expect(s.initialized).toBe(true);
	});

	it('registers in Prado.Registry', () => {
		const s = makeSlider();
		expect(global.Prado.Registry[SLIDER_ID]).toBe(s);
	});

	it('sets initial value to sliderValue option', () => {
		const s = makeSlider({ sliderValue: 50, range: [0, 100] });
		expect(s.value).toBe(50);
	});
});

// ─── setDisabled / setEnabled ─────────────────────────────────────────────────

describe('TSlider.setDisabled / setEnabled', () => {
	it('setDisabled sets disabled = true', () => {
		const s = makeSlider();
		s.setDisabled();
		expect(s.disabled).toBe(true);
	});

	it('setEnabled sets disabled = false', () => {
		const s = makeSlider();
		s.setDisabled();
		s.setEnabled();
		expect(s.disabled).toBe(false);
	});

	it('disabled slider ignores startDrag (left-click)', () => {
		const s = makeSlider();
		s.setDisabled();
		const event = {
			which: 1,
			target: s.handle,
			pageX: 50,
			pageY: 10,
			stopPropagation: vi.fn(),
		};
		s.startDrag(event);
		expect(s.active).toBe(false);
	});

	it('options.disabled true at construction time calls setDisabled', () => {
		const s = makeSlider({ disabled: true });
		expect(s.disabled).toBe(true);
	});
});

// ─── isVertical ───────────────────────────────────────────────────────────────

describe('TSlider.isVertical', () => {
	it('returns false for horizontal axis', () => {
		const s = makeSlider({ axis: 'horizontal' });
		expect(s.isVertical()).toBe(false);
	});

	it('returns true for vertical axis', () => {
		const s = makeSlider({ axis: 'vertical' });
		expect(s.isVertical()).toBe(true);
	});
});

// ─── getNearestValue — continuous range ────────────────────────────────────────

describe('TSlider.getNearestValue (continuous range)', () => {
	it('clamps value below range minimum to minimum', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.getNearestValue(-5)).toBe(0);
	});

	it('clamps value above range maximum to maximum', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.getNearestValue(150)).toBe(100);
	});

	it('returns a value within range unchanged', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.getNearestValue(42)).toBe(42);
	});

	it('returns minimum when value equals minimum', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.getNearestValue(0)).toBe(0);
	});

	it('returns maximum when value equals maximum', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.getNearestValue(100)).toBe(100);
	});

	it('handles non-zero minimum', () => {
		const s = makeSlider({ range: [20, 80], minimum: 20, maximum: 80 });
		expect(s.getNearestValue(15)).toBe(20);
		expect(s.getNearestValue(85)).toBe(80);
		expect(s.getNearestValue(50)).toBe(50);
	});
});

// ─── getNearestValue — allowedValues ──────────────────────────────────────────

describe('TSlider.getNearestValue (allowedValues)', () => {
	it('snaps to the nearest allowed value', () => {
		const s = makeSlider({ values: [0, 25, 50, 75, 100], range: [0, 100] });
		expect(s.getNearestValue(30)).toBe(25);
	});

	it('exact allowed value is returned as-is', () => {
		const s = makeSlider({ values: [0, 25, 50, 75, 100], range: [0, 100] });
		expect(s.getNearestValue(75)).toBe(75);
	});

	it('value above max of allowedValues is clamped to max', () => {
		const s = makeSlider({ values: [10, 20, 30], range: [10, 30] });
		expect(s.getNearestValue(50)).toBe(30);
	});

	it('value below min of allowedValues is clamped to min', () => {
		const s = makeSlider({ values: [10, 20, 30], range: [10, 30] });
		expect(s.getNearestValue(5)).toBe(10);
	});

	it('snaps correctly when value is equidistant between two steps', () => {
		// At a tie, the algorithm keeps the later element because currentOffset
		// uses <=; value 12.5 is 2.5 from 10 and 2.5 from 15 — the last one wins.
		const s = makeSlider({ values: [10, 15, 20], range: [10, 20] });
		const result = s.getNearestValue(12.5);
		expect([10, 15]).toContain(result);
	});

	it('derives minimum and maximum from allowedValues', () => {
		const s = makeSlider({ values: [5, 15, 25], range: [0, 100] });
		expect(s.minimum).toBe(5);
		expect(s.maximum).toBe(25);
	});
});

// ─── translateToPx and translateToValue ───────────────────────────────────────

describe('TSlider.translateToPx / translateToValue', () => {
	it('translateToPx returns "0px" for minimum value', () => {
		const s = makeSlider({ range: [0, 100] });
		// trackLength and handleLength are set in initializeTrack
		// In our setup: trackLength = 200 - 0 = 200 (from maximumOffset - minimumOffset)
		// handleLength = 20 (offsetWidth of handle)
		// formula: ((200-20)/(100-0)) * (0-0) = 0
		expect(s.translateToPx(0)).toBe('0px');
	});

	it('translateToPx returns positive px string for a mid-range value', () => {
		const s = makeSlider({ range: [0, 100] });
		const result = s.translateToPx(50);
		expect(result).toMatch(/^\d+px$/);
		expect(parseInt(result)).toBeGreaterThan(0);
	});

	it('translateToValue returns minimum for offset 0', () => {
		const s = makeSlider({ range: [0, 100] });
		expect(s.translateToValue(0)).toBe(0);
	});

	it('translateToValue is the inverse of translateToPx (round trip)', () => {
		const s = makeSlider({ range: [0, 100] });
		const px = s.translateToPx(40);
		const offset = parseInt(px);
		const roundTripped = s.translateToValue(offset);
		expect(Math.round(roundTripped)).toBe(40);
	});
});

// ─── setValue ─────────────────────────────────────────────────────────────────

describe('TSlider.setValue', () => {
	it('sets this.value to the nearest valid value', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 0 });
		s.setValue(60);
		expect(s.value).toBe(60);
	});

	it('clamps to minimum when value is below range', () => {
		const s = makeSlider({ range: [0, 100] });
		s.setValue(-10);
		expect(s.value).toBe(0);
	});

	it('clamps to maximum when value is above range', () => {
		const s = makeSlider({ range: [0, 100] });
		s.setValue(200);
		expect(s.value).toBe(100);
	});

	it('updates hiddenField.value', () => {
		const s = makeSlider({ range: [0, 100] });
		s.setValue(75);
		expect(s.hiddenField.value).toBe('75');
	});

	it('sets handle left style for horizontal slider', () => {
		const s = makeSlider({ axis: 'horizontal', range: [0, 100] });
		s.setValue(50);
		expect(s.handle.style.left).toMatch(/\d+px/);
	});

	it('sets handle top style for vertical slider', () => {
		const s = makeSlider({ axis: 'vertical', range: [0, 100] });
		s.setValue(50);
		expect(s.handle.style.top).toMatch(/\d+px/);
	});

	it('updates progress width for horizontal slider', () => {
		const s = makeSlider({ axis: 'horizontal', range: [0, 100] });
		s.setValue(50);
		expect(s.progress.style.width).toMatch(/\d+px/);
	});

	it('updates progress height for vertical slider', () => {
		const s = makeSlider({ axis: 'vertical', range: [0, 100] });
		s.setValue(50);
		expect(s.progress.style.height).toMatch(/\d+px/);
	});

	it('calls updateFinished when not dragging', () => {
		const s = makeSlider({ range: [0, 100] });
		const spy = vi.spyOn(s, 'updateFinished');
		s.setValue(30);
		expect(spy).toHaveBeenCalled();
	});

	it('calls onChange callback when initialized', () => {
		const onChange = vi.fn();
		const s = makeSlider({ range: [0, 100], onChange });
		s.setValue(30);
		expect(onChange).toHaveBeenCalledWith(30, s);
	});

	it('does NOT call onChange when not yet initialized', () => {
		const onChange = vi.fn();
		const s = makeSlider({ range: [0, 100], onChange });
		s.initialized = false;
		s.setValue(30);
		expect(onChange).not.toHaveBeenCalled();
	});
});

// ─── setValueBy ───────────────────────────────────────────────────────────────

describe('TSlider.setValueBy', () => {
	it('increments the value by delta', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 40 });
		s.setValueBy(10);
		expect(s.value).toBe(50);
	});

	it('decrements the value by delta', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 40 });
		s.setValueBy(-15);
		expect(s.value).toBe(25);
	});

	it('clamps at maximum', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 90 });
		s.setValueBy(50);
		expect(s.value).toBe(100);
	});

	it('clamps at minimum', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 10 });
		s.setValueBy(-50);
		expect(s.value).toBe(0);
	});
});

// ─── updateStyles ────────────────────────────────────────────────────────────

describe('TSlider.updateStyles', () => {
	it('adds "selected" class to handle when active', () => {
		const s = makeSlider();
		s.active = true;
		s.updateStyles();
		expect(global.jQuery(s.handle).hasClass('selected')).toBe(true);
	});

	it('removes "selected" class from handle when not active', () => {
		const s = makeSlider();
		s.active = false;
		global.jQuery(s.handle).addClass('selected');
		s.updateStyles();
		expect(global.jQuery(s.handle).hasClass('selected')).toBe(false);
	});
});

// ─── startDrag ────────────────────────────────────────────────────────────────

describe('TSlider.startDrag', () => {
	it('sets active = true on left-click when not disabled', () => {
		const s = makeSlider();
		const event = {
			which: 1,
			target: s.handle,
			pageX: 50,
			pageY: 10,
			stopPropagation: vi.fn(),
		};
		s.startDrag(event);
		expect(s.active).toBe(true);
	});

	it('does NOT activate on right-click (which !== 1)', () => {
		const s = makeSlider();
		const event = {
			which: 3,
			target: s.handle,
			pageX: 50,
			pageY: 10,
			stopPropagation: vi.fn(),
		};
		s.startDrag(event);
		expect(s.active).toBe(false);
	});

	it('calls stopPropagation on left-click', () => {
		const s = makeSlider();
		const stopProp = vi.fn();
		s.startDrag({
			which: 1,
			target: s.handle,
			pageX: 50,
			pageY: 10,
			stopPropagation: stopProp,
		});
		expect(stopProp).toHaveBeenCalled();
	});

	it('does not activate when disabled', () => {
		const s = makeSlider();
		s.setDisabled();
		const event = {
			which: 1,
			target: s.handle,
			pageX: 50,
			pageY: 10,
			stopPropagation: vi.fn(),
		};
		s.startDrag(event);
		expect(s.active).toBe(false);
	});

	it('clicking the track calls setValue with translated value', () => {
		const s = makeSlider();
		const spy = vi.spyOn(s, 'setValue');
		const event = {
			which: 1,
			target: s.track, // clicking track, not handle
			pageX: 100,
			pageY: 5,
			stopPropagation: vi.fn(),
		};
		s.startDrag(event);
		expect(spy).toHaveBeenCalled();
	});
});

// ─── update (mousemove) ───────────────────────────────────────────────────────

describe('TSlider.update', () => {
	it('calls draw when active', () => {
		const s = makeSlider();
		s.active = true;
		const drawSpy = vi.spyOn(s, 'draw');
		const event = { pageX: 80, pageY: 10, stopPropagation: vi.fn() };
		s.update(event);
		expect(drawSpy).toHaveBeenCalledWith(event);
	});

	it('does NOT call draw when not active', () => {
		const s = makeSlider();
		s.active = false;
		const drawSpy = vi.spyOn(s, 'draw');
		const event = { pageX: 80, pageY: 10, stopPropagation: vi.fn() };
		s.update(event);
		expect(drawSpy).not.toHaveBeenCalled();
	});

	it('sets dragging = true on first update when active', () => {
		const s = makeSlider();
		s.active = true;
		s.dragging = false;
		const event = { pageX: 80, pageY: 10, stopPropagation: vi.fn() };
		vi.spyOn(s, 'draw').mockImplementation(() => {});
		s.update(event);
		expect(s.dragging).toBe(true);
	});
});

// ─── draw ─────────────────────────────────────────────────────────────────────

describe('TSlider.draw', () => {
	it('calls setValue with a translated value', () => {
		const s = makeSlider();
		s.active = true;
		s.offsetX = 0;
		s.offsetY = 0;
		const spy = vi.spyOn(s, 'setValue');
		const event = { pageX: 100, pageY: 10 };
		s.draw(event);
		expect(spy).toHaveBeenCalled();
	});

	it('calls onSlide callback when initialized', () => {
		const onSlide = vi.fn();
		const s = makeSlider({ onSlide });
		s.active = true;
		s.offsetX = 0;
		s.offsetY = 0;
		s.draw({ pageX: 100, pageY: 10 });
		expect(onSlide).toHaveBeenCalled();
	});

	it('does NOT call onSlide callback when not initialized', () => {
		const onSlide = vi.fn();
		const s = makeSlider({ onSlide });
		s.initialized = false;
		s.offsetX = 0;
		s.offsetY = 0;
		s.draw({ pageX: 100, pageY: 10 });
		expect(onSlide).not.toHaveBeenCalled();
	});
});

// ─── endDrag ──────────────────────────────────────────────────────────────────

describe('TSlider.endDrag', () => {
	it('calls finishDrag when active and dragging', () => {
		const s = makeSlider();
		s.active = true;
		s.dragging = true;
		const finishSpy = vi.spyOn(s, 'finishDrag');
		const event = { stopPropagation: vi.fn() };
		s.endDrag(event);
		expect(finishSpy).toHaveBeenCalled();
	});

	it('does NOT call finishDrag when active but not dragging', () => {
		const s = makeSlider();
		s.active = true;
		s.dragging = false;
		const finishSpy = vi.spyOn(s, 'finishDrag');
		s.endDrag({ stopPropagation: vi.fn() });
		expect(finishSpy).not.toHaveBeenCalled();
	});

	it('does NOT call finishDrag when not active', () => {
		const s = makeSlider();
		s.active = false;
		s.dragging = true;
		const finishSpy = vi.spyOn(s, 'finishDrag');
		s.endDrag({ stopPropagation: vi.fn() });
		expect(finishSpy).not.toHaveBeenCalled();
	});

	it('resets active to false', () => {
		const s = makeSlider();
		s.active = true;
		s.dragging = false;
		s.endDrag({ stopPropagation: vi.fn() });
		expect(s.active).toBe(false);
	});

	it('resets dragging to false', () => {
		const s = makeSlider();
		s.active = true;
		s.dragging = true;
		s.endDrag({ stopPropagation: vi.fn() });
		expect(s.dragging).toBe(false);
	});
});

// ─── finishDrag ───────────────────────────────────────────────────────────────

describe('TSlider.finishDrag', () => {
	it('sets active = false', () => {
		const s = makeSlider();
		s.active = true;
		s.finishDrag({}, true);
		expect(s.active).toBe(false);
	});

	it('sets dragging = false', () => {
		const s = makeSlider();
		s.dragging = true;
		s.finishDrag({}, true);
		expect(s.dragging).toBe(false);
	});

	it('calls updateFinished', () => {
		const s = makeSlider();
		const spy = vi.spyOn(s, 'updateFinished');
		s.finishDrag({}, true);
		expect(spy).toHaveBeenCalled();
	});
});

// ─── updateFinished ───────────────────────────────────────────────────────────

describe('TSlider.updateFinished', () => {
	it('writes current value to hiddenField', () => {
		const s = makeSlider({ range: [0, 100], sliderValue: 0 });
		s.value = 55;
		s.updateFinished();
		expect(s.hiddenField.value).toBe('55');
	});

	it('clears this.event', () => {
		const s = makeSlider();
		s.event = { pageX: 0, pageY: 0 };
		s.updateFinished();
		expect(s.event).toBeNull();
	});

	it('calls onChange callback when initialized', () => {
		const onChange = vi.fn();
		const s = makeSlider({ onChange, range: [0, 100] });
		s.value = 70;
		s.updateFinished();
		expect(onChange).toHaveBeenCalledWith(70, s);
	});

	it('does NOT call onChange when not initialized', () => {
		const onChange = vi.fn();
		const s = makeSlider({ onChange, range: [0, 100] });
		s.initialized = false;
		s.updateFinished();
		expect(onChange).not.toHaveBeenCalled();
	});

	it('triggers hidden field change event when AutoPostBack is true', () => {
		const s = makeSlider({ AutoPostBack: true, range: [0, 100] });
		const changeSpy = vi.fn();
		global.jQuery(s.hiddenField).on('change', changeSpy);
		s.updateFinished();
		expect(changeSpy).toHaveBeenCalled();
	});

	it('does NOT trigger hidden field change when AutoPostBack is false', () => {
		const s = makeSlider({ AutoPostBack: false, range: [0, 100] });
		const changeSpy = vi.fn();
		global.jQuery(s.hiddenField).on('change', changeSpy);
		s.updateFinished();
		expect(changeSpy).not.toHaveBeenCalled();
	});
});

// ─── minimumOffset / maximumOffset ────────────────────────────────────────────

describe('TSlider.minimumOffset / maximumOffset', () => {
	it('minimumOffset returns alignX for horizontal slider', () => {
		const s = makeSlider({ axis: 'horizontal' });
		expect(s.minimumOffset()).toBe(s.alignX);
	});

	it('minimumOffset returns alignY for vertical slider', () => {
		const s = makeSlider({ axis: 'vertical' });
		expect(s.minimumOffset()).toBe(s.alignY);
	});

	it('maximumOffset returns a number for horizontal slider', () => {
		const s = makeSlider({ axis: 'horizontal' });
		expect(typeof s.maximumOffset()).toBe('number');
	});
});

// ─── allowedValues — minimum/maximum derived from steps ───────────────────────

describe('TSlider allowedValues derive range', () => {
	it('sets minimum from smallest allowed value', () => {
		const s = makeSlider({ values: [20, 40, 60, 80] });
		expect(s.minimum).toBe(20);
	});

	it('sets maximum from largest allowed value', () => {
		const s = makeSlider({ values: [20, 40, 60, 80] });
		expect(s.maximum).toBe(80);
	});

	it('allowedValues array is sorted', () => {
		const s = makeSlider({ values: [80, 20, 60, 40] });
		expect(s.allowedValues).toEqual([20, 40, 60, 80]);
	});
});

// ─── No progress element (optional element) ───────────────────────────────────

describe('TSlider without a progress element', () => {
	it('constructs successfully when progress element is absent', () => {
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}

		// Build DOM without progress element.
		const track = document.createElement('div');
		track.id = SLIDER_ID + '_track';
		track.style.display = 'block';
		Object.defineProperty(track, 'offsetWidth', { configurable: true, value: 200 });
		Object.defineProperty(track, 'offsetHeight', { configurable: true, value: 20 });
		document.body.appendChild(track);

		const handle = document.createElement('div');
		handle.id = SLIDER_ID + '_handle';
		Object.defineProperty(handle, 'offsetWidth', { configurable: true, value: 20 });
		Object.defineProperty(handle, 'offsetHeight', { configurable: true, value: 20 });
		document.body.appendChild(handle);

		// No _progress element.

		const hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.id = SLIDER_ID + '_1';
		hidden.value = '0';
		document.body.appendChild(hidden);

		const origIs = global.jQuery.fn.is;
		global.jQuery.fn.is = function (sel) {
			if (sel === ':visible') return true;
			return origIs.call(this, sel);
		};

		let slider;
		expect(() => {
			slider = new TSlider({
				ID: SLIDER_ID,
				axis: 'horizontal',
				range: [0, 100],
				minimum: 0,
				maximum: 100,
				sliderValue: 0,
				disabled: false,
			});
		}).not.toThrow();

		global.jQuery.fn.is = origIs;

		// document.getElementById of a missing id returns null.
	expect(slider.progress).toBeNull();
	});
});

// ─── Vertical slider ─────────────────────────────────────────────────────────

describe('TSlider vertical axis', () => {
	it('isVertical() returns true', () => {
		const s = makeSlider({ axis: 'vertical' });
		expect(s.isVertical()).toBe(true);
	});

	it('setValue sets handle top style', () => {
		const s = makeSlider({ axis: 'vertical', range: [0, 100] });
		s.setValue(50);
		expect(s.handle.style.top).toMatch(/\d+px/);
	});

	it('setValue does NOT set handle left style for vertical slider', () => {
		const s = makeSlider({ axis: 'vertical', range: [0, 100] });
		s.handle.style.left = '';
		s.setValue(50);
		expect(s.handle.style.left).toBe('');
	});
});
