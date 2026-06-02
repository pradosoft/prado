/**
 * Tests for Prado.WebUI.TActiveDatePicker (activedatepicker.js).
 * Source: framework/Web/Javascripts/source/prado/activecontrols/activedatepicker.js
 *
 * Strategy
 * --------
 * TActiveDatePicker extends TDatePicker.  All inherited pure-logic (getDaysPerMonth,
 * newDate, formatDate, etc.) is tested in datepicker.test.js and is not repeated here.
 * These tests focus on the delta introduced by TActiveDatePicker:
 *   - its own onInit (ShowCalendar gate, AutoPostBack, full change listener wiring)
 *   - its onDateChanged override (raises OnDateChanged callback AND dispatches a
 *     Prado.CallbackRequest when AutoPostBack is true)
 *
 * ESM note: only tests/js/adapters/activedatepicker.js changes on ESM conversion.
 */

import { TActiveDatePicker } from '../adapters/activedatepicker.js';

// ─── Shared DOM helpers ───────────────────────────────────────────────────────

let _idSeq = 0;
function nextId() { return `adp_test_${++_idSeq}`; }

function buildDOM(id, triggerId) {
	document.getElementById(id) && document.getElementById(id).remove();
	if (triggerId) document.getElementById(triggerId) && document.getElementById(triggerId).remove();

	const container = document.createElement('div');
	container.id = `${id}_container`;

	const input = document.createElement('input');
	input.type = 'text';
	input.id = id;
	container.appendChild(input);

	if (triggerId) {
		const btn = document.createElement('button');
		btn.id = triggerId;
		btn.type = 'button';
		btn.textContent = '...';
		container.appendChild(btn);
	}

	document.body.appendChild(container);
	return { id, triggerId, container };
}

function buildDropDownDOM(baseId) {
	document.getElementById(baseId) && document.getElementById(baseId).remove();

	const container = document.createElement('div');
	container.id = `${baseId}_container`;

	const anchor = document.createElement('input');
	anchor.type = 'hidden';
	anchor.id = baseId;
	container.appendChild(anchor);

	const daySelect = document.createElement('select');
	daySelect.id = `${baseId}_day`;
	for (let d = 1; d <= 31; d++) {
		const opt = document.createElement('option');
		opt.value = d;
		opt.text = d;
		daySelect.appendChild(opt);
	}
	container.appendChild(daySelect);

	const monthSelect = document.createElement('select');
	monthSelect.id = `${baseId}_month`;
	['January','February','March','April','May','June',
	 'July','August','September','October','November','December'].forEach((name, i) => {
		const opt = document.createElement('option');
		opt.value = i;
		opt.text = name;
		monthSelect.appendChild(opt);
	});
	container.appendChild(monthSelect);

	const yearSelect = document.createElement('select');
	yearSelect.id = `${baseId}_year`;
	for (let y = 2000; y <= 2030; y++) {
		const opt = document.createElement('option');
		opt.value = y;
		opt.text = y;
		yearSelect.appendChild(opt);
	}
	container.appendChild(yearSelect);

	document.body.appendChild(container);
	return { baseId, container };
}

function makeOptions(id, extra = {}) {
	return Object.assign({
		ID: id,
		InputMode: 'TextBox',
		Format: 'yyyy-MM-dd',
		FromYear: 2000,
		UpToYear: 2030,
		ShowCalendar: true,
	}, extra);
}

// ─── TActiveDatePicker class structure ───────────────────────────────────────

describe('TActiveDatePicker class structure', () => {
	it('is a constructor function', () => {
		expect(typeof TActiveDatePicker).toBe('function');
	});

	it('is registered in Prado.WebUI', () => {
		expect(global.Prado.WebUI.TActiveDatePicker).toBe(TActiveDatePicker);
	});

	it('inherits from TDatePicker (prototype chain)', () => {
		expect(TActiveDatePicker.superclass).toBe(global.Prado.WebUI.TDatePicker);
	});
});

// ─── Instance defaults and inherited properties ───────────────────────────────

describe('TActiveDatePicker instance defaults', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id));
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('creates a dateSlot array with 42 slots', () => {
		expect(picker.dateSlot).toHaveLength(42);
	});

	it('creates a weekSlot array with 6 slots', () => {
		expect(picker.weekSlot).toHaveLength(6);
	});

	it('sets selectedDate to a Date instance', () => {
		expect(picker.selectedDate instanceof Date).toBe(true);
	});

	it('registers itself in Prado.Registry', () => {
		expect(global.Prado.Registry[id]).toBe(picker);
	});

	it('inherits MonthNames from TDatePicker', () => {
		expect(picker.MonthNames).toHaveLength(12);
		expect(picker.MonthNames[0]).toBe('January');
	});

	it('inherits getDaysPerMonth method', () => {
		expect(typeof picker.getDaysPerMonth).toBe('function');
	});

	it('inherits nextMonth method', () => {
		expect(typeof picker.nextMonth).toBe('function');
	});

	it('inherits prevMonth method', () => {
		expect(typeof picker.prevMonth).toBe('function');
	});

	it('inherits formatDate method', () => {
		expect(typeof picker.formatDate).toBe('function');
	});

	it('inherits create method', () => {
		expect(typeof picker.create).toBe('function');
	});

	it('inherits getSelectedDate method', () => {
		expect(typeof picker.getSelectedDate).toBe('function');
	});

	it('has its own onDateChanged override', () => {
		// The prototype chain means TActiveDatePicker.prototype.onDateChanged
		// should be defined on TActiveDatePicker's own prototype.
		expect(TActiveDatePicker.prototype.onDateChanged).toBeDefined();
	});
});

// ─── ShowCalendar option ──────────────────────────────────────────────────────

describe('TActiveDatePicker ShowCalendar option', () => {
	afterEach(() => {
		document.querySelectorAll('[id^="adp_test_"]').forEach(el => el.closest('[id$="_container"]')?.remove() || el.remove());
	});

	it('wires the show handler when ShowCalendar is true', () => {
		const id = nextId();
		buildDOM(id);
		const picker = new TActiveDatePicker(makeOptions(id, { ShowCalendar: true }));

		// show should be callable without errors (creates the calendar)
		expect(() => picker.show()).not.toThrow();

		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('does not wire the show handler when ShowCalendar is false', () => {
		const id = nextId();
		buildDOM(id);
		const picker = new TActiveDatePicker(makeOptions(id, { ShowCalendar: false }));

		// clicking the trigger should NOT call show()
		const showSpy = vi.spyOn(picker, 'show');
		jQuery(picker.trigger).trigger('focus');
		expect(showSpy).not.toHaveBeenCalled();

		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});
});

// ─── Trigger element option ───────────────────────────────────────────────────

describe('TActiveDatePicker with separate Trigger element', () => {
	let picker;
	const id = nextId();
	const triggerId = `${id}_btn`;

	beforeEach(() => {
		buildDOM(id, triggerId);
		picker = new TActiveDatePicker(makeOptions(id, { Trigger: triggerId }));
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('uses the Trigger element (not the control) as the trigger', () => {
		expect(picker.trigger).toBe(document.getElementById(triggerId));
	});

	it('the trigger element is different from the control', () => {
		expect(picker.trigger).not.toBe(picker.control);
	});
});

// ─── PositionMode=Top option ──────────────────────────────────────────────────

describe('TActiveDatePicker PositionMode=Top', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id, { PositionMode: 'Top' }));
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('sets positionMode to Top', () => {
		expect(picker.positionMode).toBe('Top');
	});
});

// ─── onDateChanged — TextBox mode, OnDateChanged callback ─────────────────────

describe('TActiveDatePicker#onDateChanged — TextBox mode', () => {
	let picker;
	const id = nextId();

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('calls OnDateChanged with (picker, rawInputValue)', () => {
		const cb = vi.fn();
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id, {
			InputMode: 'TextBox',
			Format: 'yyyy-MM-dd',
			OnDateChanged: cb,
		}));

		picker.control.value = '2024-07-04';
		picker.onDateChanged();

		expect(cb).toHaveBeenCalledTimes(1);
		const [ref, dateStr] = cb.mock.calls[0];
		expect(ref).toBe(picker);
		expect(dateStr).toBe('2024-07-04');
	});

	it('does not throw when OnDateChanged is not a function', () => {
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id, { InputMode: 'TextBox' }));
		picker.control.value = '2024-07-04';
		expect(() => picker.onDateChanged()).not.toThrow();
	});
});

// ─── onDateChanged — DropDownList mode, OnDateChanged callback ────────────────

describe('TActiveDatePicker#onDateChanged — DropDownList mode', () => {
	let picker;
	let baseId;
	let domInfo;

	beforeEach(() => {
		baseId = nextId();
		domInfo = buildDropDownDOM(baseId);
	});

	afterEach(() => {
		domInfo.container.remove();
		delete global.Prado.Registry[baseId];
	});

	it('calls OnDateChanged with (picker, formattedDateString)', () => {
		const cb = vi.fn();
		picker = new TActiveDatePicker({
			ID: baseId,
			InputMode: 'DropDownList',
			Format: 'yyyy-MM-dd',
			FromYear: 2000,
			UpToYear: 2030,
			ShowCalendar: false,
			OnDateChanged: cb,
		});

		// Set dropdowns to 2024-06-15
		const daySelect = document.getElementById(`${baseId}_day`);
		const monthSelect = document.getElementById(`${baseId}_month`);
		const yearSelect = document.getElementById(`${baseId}_year`);
		daySelect.selectedIndex = 14;   // day 15
		monthSelect.selectedIndex = 5;  // June
		const yearIdx = [...yearSelect.options].findIndex(o => Number(o.value) === 2024);
		yearSelect.selectedIndex = yearIdx;

		picker.onDateChanged();

		expect(cb).toHaveBeenCalledTimes(1);
		const [ref, dateStr] = cb.mock.calls[0];
		expect(ref).toBe(picker);
		// The formatted date should contain 2024, 06, 15 in some order per Format
		expect(dateStr).toBe('2024-06-15');
	});

	it('does not throw when OnDateChanged is absent', () => {
		picker = new TActiveDatePicker({
			ID: baseId,
			InputMode: 'DropDownList',
			Format: 'yyyy-MM-dd',
			FromYear: 2000,
			UpToYear: 2030,
			ShowCalendar: false,
		});
		expect(() => picker.onDateChanged()).not.toThrow();
	});
});

// ─── AutoPostBack — Prado.CallbackRequest dispatch ────────────────────────────

describe('TActiveDatePicker AutoPostBack', () => {
	let picker;
	const id = nextId();
	let originalCallbackRequest;

	beforeEach(() => {
		buildDOM(id);
		// Save original and replace with a spy constructor
		originalCallbackRequest = global.Prado.CallbackRequest;
	});

	afterEach(() => {
		global.Prado.CallbackRequest = originalCallbackRequest;
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('dispatches a CallbackRequest when AutoPostBack is true', () => {
		const dispatchSpy = vi.fn();
		// Must use a regular function (not arrow) so `new` works correctly.
		function MockCallbackRequest() { this.dispatch = dispatchSpy; }
		global.Prado.CallbackRequest = MockCallbackRequest;

		picker = new TActiveDatePicker(makeOptions(id, {
			AutoPostBack: true,
			EventTarget: 'MyControl',
			InputMode: 'TextBox',
		}));

		picker.control.value = '2024-07-04';
		picker.onDateChanged();

		expect(dispatchSpy).toHaveBeenCalledTimes(1);
	});

	it('passes EventTarget as first arg to CallbackRequest', () => {
		const constructorArgs = [];
		function MockCallbackRequest(target, opts) {
			constructorArgs.push(target);
			this.dispatch = vi.fn();
		}
		global.Prado.CallbackRequest = MockCallbackRequest;

		picker = new TActiveDatePicker(makeOptions(id, {
			AutoPostBack: true,
			EventTarget: 'SomeControl$DatePicker',
			InputMode: 'TextBox',
		}));

		picker.control.value = '2024-01-01';
		picker.onDateChanged();

		expect(constructorArgs[0]).toBe('SomeControl$DatePicker');
	});

	it('does not dispatch a CallbackRequest when AutoPostBack is false', () => {
		const dispatchSpy = vi.fn();
		function MockCallbackRequest() { this.dispatch = dispatchSpy; }
		global.Prado.CallbackRequest = MockCallbackRequest;

		picker = new TActiveDatePicker(makeOptions(id, {
			AutoPostBack: false,
			EventTarget: 'MyControl',
			InputMode: 'TextBox',
		}));

		picker.control.value = '2024-07-04';
		picker.onDateChanged();

		expect(dispatchSpy).not.toHaveBeenCalled();
	});

	it('does not dispatch a CallbackRequest when AutoPostBack is absent', () => {
		const dispatchSpy = vi.fn();
		function MockCallbackRequest() { this.dispatch = dispatchSpy; }
		global.Prado.CallbackRequest = MockCallbackRequest;

		picker = new TActiveDatePicker(makeOptions(id, { InputMode: 'TextBox' }));
		// no AutoPostBack in options

		picker.control.value = '2024-07-04';
		picker.onDateChanged();

		expect(dispatchSpy).not.toHaveBeenCalled();
	});
});

// ─── Change-event wiring — TextBox mode ──────────────────────────────────────
//
// vi.spyOn() cannot intercept the jQuery.proxy() closure captured during onInit,
// so we verify the observable end-to-end effect via the OnDateChanged callback.

describe('TActiveDatePicker change-event wiring — TextBox mode', () => {
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('fires OnDateChanged when the input fires a change event', () => {
		const cb = vi.fn();
		const picker = new TActiveDatePicker(makeOptions(id, { InputMode: 'TextBox', OnDateChanged: cb }));

		picker.control.value = '2024-08-01';
		picker.control.dispatchEvent(new Event('change', { bubbles: true }));

		expect(cb).toHaveBeenCalledTimes(1);
	});

	it('passes the raw input value to OnDateChanged on change', () => {
		const cb = vi.fn();
		const picker = new TActiveDatePicker(makeOptions(id, { InputMode: 'TextBox', OnDateChanged: cb }));

		picker.control.value = '2025-12-25';
		picker.control.dispatchEvent(new Event('change', { bubbles: true }));

		expect(cb.mock.calls[0][1]).toBe('2025-12-25');
	});
});

// ─── Change-event wiring — DropDownList mode ─────────────────────────────────
//
// vi.spyOn() cannot intercept the jQuery.proxy() closure captured during onInit,
// so we verify the end-to-end observable effect instead: OnDateChanged is called
// when each dropdown fires its change event.

describe('TActiveDatePicker change-event wiring — DropDownList mode', () => {
	let baseId;
	let domInfo;

	beforeEach(() => {
		baseId = nextId();
		domInfo = buildDropDownDOM(baseId);
	});

	afterEach(() => {
		domInfo.container.remove();
		delete global.Prado.Registry[baseId];
	});

	function makeDropDownPicker(baseId, extra = {}) {
		return new TActiveDatePicker(Object.assign({
			ID: baseId,
			InputMode: 'DropDownList',
			Format: 'yyyy-MM-dd',
			FromYear: 2000,
			UpToYear: 2030,
			ShowCalendar: false,
		}, extra));
	}

	it('fires OnDateChanged when the day dropdown changes', () => {
		const cb = vi.fn();
		makeDropDownPicker(baseId, { OnDateChanged: cb });
		document.getElementById(`${baseId}_day`).dispatchEvent(new Event('change', { bubbles: true }));
		expect(cb).toHaveBeenCalledTimes(1);
	});

	it('fires OnDateChanged when the month dropdown changes', () => {
		const cb = vi.fn();
		makeDropDownPicker(baseId, { OnDateChanged: cb });
		document.getElementById(`${baseId}_month`).dispatchEvent(new Event('change', { bubbles: true }));
		expect(cb).toHaveBeenCalledTimes(1);
	});

	it('fires OnDateChanged when the year dropdown changes', () => {
		const cb = vi.fn();
		makeDropDownPicker(baseId, { OnDateChanged: cb });
		document.getElementById(`${baseId}_year`).dispatchEvent(new Event('change', { bubbles: true }));
		expect(cb).toHaveBeenCalledTimes(1);
	});

	it('each dropdown fires independently (no cross-contamination)', () => {
		const cb = vi.fn();
		makeDropDownPicker(baseId, { OnDateChanged: cb });
		document.getElementById(`${baseId}_day`).dispatchEvent(new Event('change', { bubbles: true }));
		document.getElementById(`${baseId}_month`).dispatchEvent(new Event('change', { bubbles: true }));
		document.getElementById(`${baseId}_year`).dispatchEvent(new Event('change', { bubbles: true }));
		expect(cb).toHaveBeenCalledTimes(3);
	});
});

// ─── Inherited calendar functionality ────────────────────────────────────────

describe('TActiveDatePicker inherits calendar behaviour from TDatePicker', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('nextMonth advances the month', () => {
		picker.setSelectedDate(new Date(2024, 4, 15));
		picker.nextMonth();
		expect(picker.selectedDate.getMonth()).toBe(5);
	});

	it('prevMonth retreats the month', () => {
		picker.setSelectedDate(new Date(2024, 4, 15));
		picker.prevMonth();
		expect(picker.selectedDate.getMonth()).toBe(3);
	});

	it('setYear updates the year', () => {
		picker.setSelectedDate(new Date(2024, 4, 15));
		picker.setYear(2026);
		expect(picker.selectedDate.getFullYear()).toBe(2026);
	});

	it('getDaysPerMonth returns 29 for Feb 2024 (leap)', () => {
		expect(picker.getDaysPerMonth(1, 2024)).toBe(29);
	});

	it('getDaysPerMonth returns 28 for Feb 2023 (non-leap)', () => {
		expect(picker.getDaysPerMonth(1, 2023)).toBe(28);
	});

	it('show() creates the calendar DOM', () => {
		expect(picker._calDiv).toBeDefined();
	});

	it('formatDate returns a correctly formatted string', () => {
		picker.selectedDate = new Date(2024, 0, 5);
		expect(picker.formatDate()).toBe('2024-01-05');
	});

	it('getSelectedDate returns a Date matching selectedDate', () => {
		picker.setSelectedDate(new Date(2024, 6, 20));
		const d = picker.getSelectedDate();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(6);
		expect(d.getDate()).toBe(20);
	});

	it('hide() hides the calendar', () => {
		picker.show();
		picker.hide();
		expect(picker.showing).toBe(false);
	});
});

// ─── December → January and January → December year boundary ─────────────────

describe('TActiveDatePicker year boundary navigation', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TActiveDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('nextMonth from December wraps to January of next year', () => {
		picker.setSelectedDate(new Date(2024, 11, 15));
		picker.nextMonth();
		expect(picker.selectedDate.getMonth()).toBe(0);
		expect(picker.selectedDate.getFullYear()).toBe(2025);
	});

	it('prevMonth from January wraps to December of previous year', () => {
		picker.setSelectedDate(new Date(2025, 0, 15));
		picker.prevMonth();
		expect(picker.selectedDate.getMonth()).toBe(11);
		expect(picker.selectedDate.getFullYear()).toBe(2024);
	});
});

// ─── onDateChanged fires before AutoPostBack dispatch ────────────────────────

describe('TActiveDatePicker#onDateChanged order of operations', () => {
	let picker;
	const id = nextId();
	let originalCallbackRequest;
	const callOrder = [];

	beforeEach(() => {
		buildDOM(id);
		originalCallbackRequest = global.Prado.CallbackRequest;
		callOrder.length = 0;
	});

	afterEach(() => {
		global.Prado.CallbackRequest = originalCallbackRequest;
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('calls OnDateChanged before dispatching the callback request', () => {
		function MockCallbackRequest() {
			this.dispatch = vi.fn(() => callOrder.push('dispatch'));
		}
		global.Prado.CallbackRequest = MockCallbackRequest;

		const cb = vi.fn(() => callOrder.push('OnDateChanged'));

		picker = new TActiveDatePicker(makeOptions(id, {
			AutoPostBack: true,
			EventTarget: 'Ctl',
			InputMode: 'TextBox',
			OnDateChanged: cb,
		}));

		picker.control.value = '2024-01-01';
		picker.onDateChanged();

		expect(callOrder).toEqual(['OnDateChanged', 'dispatch']);
	});
});
