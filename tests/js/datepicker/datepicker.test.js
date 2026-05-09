/**
 * Tests for Prado.WebUI.TDatePicker (datepicker.js).
 * Source: framework/Web/Javascripts/source/prado/datepicker/datepicker.js
 *
 * Strategy
 * --------
 * Pure-logic methods (getDaysPerMonth, formatDate, newDate, etc.) are tested
 * by calling them on a minimal picker instance constructed without a real DOM
 * interaction where possible.  DOM-dependent tests (create, show, hide, update,
 * selectDate, navigation buttons, header dropdowns) set up and tear down a
 * realistic DOM fragment in beforeEach / afterEach.
 *
 * ESM note: only tests/js/adapters/datepicker.js changes on ESM conversion.
 */

import { TDatePicker } from '../adapters/datepicker.js';

// ─── Shared DOM helpers ───────────────────────────────────────────────────────

/** Unique counter so each test gets its own element ID. */
let _idSeq = 0;
function nextId() { return `dp_test_${++_idSeq}`; }

/**
 * Build the minimal DOM fragment that TDatePicker.onInit() requires.
 * Returns { id, triggerId, container }.
 */
function buildDOM(id, triggerId) {
	// Remove any leftover from a previous test
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

/** Build the three drop-down controls for DropDownList InputMode. */
function buildDropDownDOM(baseId) {
	document.getElementById(baseId) && document.getElementById(baseId).remove();

	const container = document.createElement('div');
	container.id = `${baseId}_container`;

	// Hidden anchor element with the base ID
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

/** Default options object for TextBox mode. */
function makeOptions(id, extra = {}) {
	return Object.assign({
		ID: id,
		InputMode: 'TextBox',
		Format: 'yyyy-MM-dd',
		FromYear: 2000,
		UpToYear: 2030,
	}, extra);
}

/** Instantiate TDatePicker without triggering show(). */
function makePicker(id, extra = {}) {
	buildDOM(id);
	return new TDatePicker(makeOptions(id, extra));
}

// ─── TDatePicker static structure ────────────────────────────────────────────

describe('TDatePicker class structure', () => {
	it('is a constructor function', () => {
		expect(typeof TDatePicker).toBe('function');
	});

	it('is registered in Prado.WebUI', () => {
		expect(global.Prado.WebUI.TDatePicker).toBe(TDatePicker);
	});

	it('has static getDropDownDate method', () => {
		expect(typeof TDatePicker.getDropDownDate).toBe('function');
	});

	it('has static getYearListControl method', () => {
		expect(typeof TDatePicker.getYearListControl).toBe('function');
	});

	it('has static getMonthListControl method', () => {
		expect(typeof TDatePicker.getMonthListControl).toBe('function');
	});

	it('has static getDayListControl method', () => {
		expect(typeof TDatePicker.getDayListControl).toBe('function');
	});
});

// ─── Instance defaults ────────────────────────────────────────────────────────

describe('TDatePicker instance defaults', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('sets MonthNames to 12 English month names', () => {
		expect(picker.MonthNames).toHaveLength(12);
		expect(picker.MonthNames[0]).toBe('January');
		expect(picker.MonthNames[11]).toBe('December');
	});

	it('sets AbbreviatedMonthNames to 12 abbreviated names', () => {
		expect(picker.AbbreviatedMonthNames).toHaveLength(12);
		expect(picker.AbbreviatedMonthNames[0]).toBe('Jan');
		expect(picker.AbbreviatedMonthNames[11]).toBe('Dec');
	});

	it('sets ShortWeekDayNames to 7 day names starting with Sun', () => {
		expect(picker.ShortWeekDayNames).toHaveLength(7);
		expect(picker.ShortWeekDayNames[0]).toBe('Sun');
		expect(picker.ShortWeekDayNames[6]).toBe('Sat');
	});

	it('has a dateSlot array with 42 slots', () => {
		expect(Array.isArray(picker.dateSlot)).toBe(true);
		expect(picker.dateSlot).toHaveLength(42);
	});

	it('has a weekSlot array with 6 slots', () => {
		expect(Array.isArray(picker.weekSlot)).toBe(true);
		expect(picker.weekSlot).toHaveLength(6);
	});

	it('registers itself in Prado.Registry', () => {
		expect(global.Prado.Registry[id]).toBe(picker);
	});

	it('sets selectedDate to a Date instance', () => {
		expect(picker.selectedDate instanceof Date).toBe(true);
	});

	it('uses Bottom as the default positionMode', () => {
		expect(picker.positionMode).toBe('Bottom');
	});
});

// ─── getDaysPerMonth ──────────────────────────────────────────────────────────

describe('TDatePicker#getDaysPerMonth', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('returns 31 for January (month 0)', () => {
		expect(picker.getDaysPerMonth(0, 2024)).toBe(31);
	});

	it('returns 28 for February in a non-leap year', () => {
		expect(picker.getDaysPerMonth(1, 2023)).toBe(28);
	});

	it('returns 29 for February in a leap year (divisible by 4, not 100)', () => {
		expect(picker.getDaysPerMonth(1, 2024)).toBe(29);
	});

	it('returns 28 for February in a century non-leap year (1900)', () => {
		expect(picker.getDaysPerMonth(1, 1900)).toBe(28);
	});

	it('returns 29 for February in a 400-year leap year (2000)', () => {
		expect(picker.getDaysPerMonth(1, 2000)).toBe(29);
	});

	it('returns 31 for March (month 2)', () => {
		expect(picker.getDaysPerMonth(2, 2024)).toBe(31);
	});

	it('returns 30 for April (month 3)', () => {
		expect(picker.getDaysPerMonth(3, 2024)).toBe(30);
	});

	it('returns 31 for May (month 4)', () => {
		expect(picker.getDaysPerMonth(4, 2024)).toBe(31);
	});

	it('returns 30 for June (month 5)', () => {
		expect(picker.getDaysPerMonth(5, 2024)).toBe(30);
	});

	it('returns 31 for July (month 6)', () => {
		expect(picker.getDaysPerMonth(6, 2024)).toBe(31);
	});

	it('returns 31 for August (month 7)', () => {
		expect(picker.getDaysPerMonth(7, 2024)).toBe(31);
	});

	it('returns 30 for September (month 8)', () => {
		expect(picker.getDaysPerMonth(8, 2024)).toBe(30);
	});

	it('returns 31 for October (month 9)', () => {
		expect(picker.getDaysPerMonth(9, 2024)).toBe(31);
	});

	it('returns 30 for November (month 10)', () => {
		expect(picker.getDaysPerMonth(10, 2024)).toBe(30);
	});

	it('returns 31 for December (month 11)', () => {
		expect(picker.getDaysPerMonth(11, 2024)).toBe(31);
	});

	it('normalises month 12 to 0 (January) via modulo', () => {
		// month 12 % 12 = 0 → January = 31
		expect(picker.getDaysPerMonth(12, 2024)).toBe(31);
	});

	it('normalises month -1 to 11 (December) via (+12)%12', () => {
		// (-1 + 12) % 12 = 11 → December = 31
		expect(picker.getDaysPerMonth(-1, 2024)).toBe(31);
	});
});

// ─── newDate ──────────────────────────────────────────────────────────────────

describe('TDatePicker#newDate', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id, { FromYear: 2000, UpToYear: 2030 });
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('returns a Date when called with no argument (today)', () => {
		const d = picker.newDate();
		expect(d instanceof Date).toBe(true);
	});

	it('returns a Date when called with a Date object', () => {
		const src = new Date(2024, 5, 15);
		const d = picker.newDate(src);
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(5);
		expect(d.getDate()).toBe(15);
	});

	it('accepts a numeric timestamp', () => {
		const ts = new Date(2024, 0, 10).getTime();
		const d = picker.newDate(ts);
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(0);
		expect(d.getDate()).toBe(10);
	});

	it('accepts a date string', () => {
		const d = picker.newDate('2025-03-20');
		expect(d instanceof Date).toBe(true);
	});

	it('clamps years below FromYear up to FromYear', () => {
		const d = picker.newDate(new Date(1990, 5, 15));
		expect(d.getFullYear()).toBe(2000);
	});

	it('clamps years above UpToYear down to UpToYear', () => {
		const d = picker.newDate(new Date(2050, 5, 15));
		expect(d.getFullYear()).toBe(2030);
	});

	it('zeroes out time components (hours, minutes, seconds)', () => {
		const src = new Date(2024, 5, 15, 14, 30, 45);
		const d = picker.newDate(src);
		expect(d.getHours()).toBe(0);
		expect(d.getMinutes()).toBe(0);
		expect(d.getSeconds()).toBe(0);
	});
});

// ─── formatDate ───────────────────────────────────────────────────────────────

describe('TDatePicker#formatDate', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id, { Format: 'yyyy-MM-dd' });
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('formats selectedDate using the configured Format string', () => {
		picker.selectedDate = new Date(2024, 0, 5); // 5 Jan 2024
		expect(picker.formatDate()).toBe('2024-01-05');
	});

	it('returns empty string when selectedDate is falsy', () => {
		picker.selectedDate = null;
		expect(picker.formatDate()).toBe('');
	});

	it('uses dd (zero-padded day)', () => {
		picker.Format = 'dd/MM/yyyy';
		picker.selectedDate = new Date(2024, 2, 3); // 3 Mar 2024
		expect(picker.formatDate()).toBe('03/03/2024');
	});

	it('uses d (non-padded day)', () => {
		picker.Format = 'd/M/yyyy';
		picker.selectedDate = new Date(2024, 2, 3);
		expect(picker.formatDate()).toBe('3/3/2024');
	});

	it('formats abbreviated month name (MMM)', () => {
		picker.Format = 'MMM yyyy';
		picker.selectedDate = new Date(2024, 0, 1); // January
		expect(picker.formatDate()).toBe('Jan 2024');
	});

	it('formats full month name (MMMM)', () => {
		picker.Format = 'MMMM yyyy';
		picker.selectedDate = new Date(2024, 2, 1); // March
		expect(picker.formatDate()).toBe('March 2024');
	});

	it('formats two-digit year (yy)', () => {
		picker.Format = 'yy';
		picker.selectedDate = new Date(2024, 0, 1);
		expect(picker.formatDate()).toBe('24');
	});

	it('formats four-digit year (yyyy)', () => {
		picker.Format = 'yyyy';
		picker.selectedDate = new Date(2024, 0, 1);
		expect(picker.formatDate()).toBe('2024');
	});
});

// ─── getSelectedDate ──────────────────────────────────────────────────────────

describe('TDatePicker#getSelectedDate', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('returns a Date instance', () => {
		expect(picker.getSelectedDate() instanceof Date).toBe(true);
	});

	it('returns null when selectedDate is null', () => {
		picker.selectedDate = null;
		expect(picker.getSelectedDate()).toBeNull();
	});

	it('returns a copy, not the same reference as selectedDate', () => {
		const returned = picker.getSelectedDate();
		expect(returned).not.toBe(picker.selectedDate);
	});

	it('returned date matches the selectedDate value', () => {
		picker.selectedDate = new Date(2024, 5, 15);
		const d = picker.getSelectedDate();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(5);
		expect(d.getDate()).toBe(15);
	});
});

// ─── setSelectedDate ──────────────────────────────────────────────────────────

describe('TDatePicker#setSelectedDate', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
		// create() must be called before updateHeader() / update() can run
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('ignores null', () => {
		const before = picker.selectedDate;
		picker.setSelectedDate(null);
		expect(picker.selectedDate).toBe(before);
	});

	it('updates selectedDate to the given date', () => {
		const target = new Date(2024, 5, 15);
		picker.setSelectedDate(target);
		expect(picker.selectedDate.getFullYear()).toBe(2024);
		expect(picker.selectedDate.getMonth()).toBe(5);
		expect(picker.selectedDate.getDate()).toBe(15);
	});

	it('calls onChange when the date changes', () => {
		const onChange = vi.fn();
		picker.onChange = onChange;
		picker.setSelectedDate(new Date(2024, 6, 1));
		expect(onChange).toHaveBeenCalled();
	});

	it('does not call onChange when the date is unchanged and the control already shows the right value', () => {
		// set to a known date first
		picker.setSelectedDate(new Date(2024, 6, 1));
		// sync the textbox value
		picker.control.value = picker.formatDate();

		const onChange = vi.fn();
		picker.onChange = onChange;

		// set the same date again
		picker.setSelectedDate(new Date(2024, 6, 1));
		expect(onChange).not.toHaveBeenCalled();
	});

	it('accepts a numeric timestamp', () => {
		const ts = new Date(2025, 0, 10).getTime();
		picker.setSelectedDate(ts);
		expect(picker.selectedDate.getDate()).toBe(10);
		expect(picker.selectedDate.getMonth()).toBe(0);
		expect(picker.selectedDate.getFullYear()).toBe(2025);
	});
});

// ─── setYear ──────────────────────────────────────────────────────────────────

describe('TDatePicker#setYear', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
		picker.create();
		picker.setSelectedDate(new Date(2024, 5, 15));
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('changes only the year, keeping month and day', () => {
		picker.setYear(2025);
		expect(picker.selectedDate.getFullYear()).toBe(2025);
		expect(picker.selectedDate.getMonth()).toBe(5);
		expect(picker.selectedDate.getDate()).toBe(15);
	});

	it('updates selectedDate to the new year', () => {
		picker.setYear(2020);
		expect(picker.selectedDate.getFullYear()).toBe(2020);
	});
});

// ─── setMonth ─────────────────────────────────────────────────────────────────

describe('TDatePicker#setMonth', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('changes the month', () => {
		picker.setSelectedDate(new Date(2024, 0, 15));
		picker.setMonth(5);
		expect(picker.selectedDate.getMonth()).toBe(5);
	});

	it('clamps the day when switching to a shorter month', () => {
		// Jan 31 → Feb (28 days in 2023) → day clamped to 28
		picker.setSelectedDate(new Date(2023, 0, 31));
		picker.setMonth(1);
		expect(picker.selectedDate.getMonth()).toBe(1);
		expect(picker.selectedDate.getDate()).toBeLessThanOrEqual(28);
	});

	it('does not clamp the day when staying in a long month', () => {
		picker.setSelectedDate(new Date(2024, 0, 31));
		picker.setMonth(2); // March has 31 days
		expect(picker.selectedDate.getDate()).toBe(31);
	});

	it('Feb 29 in leap year is kept when switching to Feb of a leap year', () => {
		// March 29 → Feb of a leap year (2024) → should become Feb 29
		picker.setSelectedDate(new Date(2024, 2, 29));
		picker.setMonth(1);
		expect(picker.selectedDate.getDate()).toBe(29);
		expect(picker.selectedDate.getMonth()).toBe(1);
	});
});

// ─── nextMonth / prevMonth ────────────────────────────────────────────────────

describe('TDatePicker#nextMonth and prevMonth', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		picker = makePicker(id);
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('nextMonth increments the month by 1', () => {
		picker.setSelectedDate(new Date(2024, 5, 15));
		picker.nextMonth();
		expect(picker.selectedDate.getMonth()).toBe(6);
	});

	it('nextMonth wraps from December to January and increments year', () => {
		picker.setSelectedDate(new Date(2024, 11, 15));
		picker.nextMonth();
		expect(picker.selectedDate.getMonth()).toBe(0);
		expect(picker.selectedDate.getFullYear()).toBe(2025);
	});

	it('prevMonth decrements the month by 1', () => {
		picker.setSelectedDate(new Date(2024, 5, 15));
		picker.prevMonth();
		expect(picker.selectedDate.getMonth()).toBe(4);
	});

	it('prevMonth wraps from January to December and decrements year', () => {
		picker.setSelectedDate(new Date(2024, 0, 15));
		picker.prevMonth();
		expect(picker.selectedDate.getMonth()).toBe(11);
		expect(picker.selectedDate.getFullYear()).toBe(2023);
	});

	it('prevMonth on March 31 → Feb clamps day to ≤ 28 or 29', () => {
		picker.setSelectedDate(new Date(2023, 2, 31)); // March 31 (non-leap)
		picker.prevMonth();
		expect(picker.selectedDate.getMonth()).toBe(1);
		expect(picker.selectedDate.getDate()).toBeLessThanOrEqual(28);
	});
});

// ─── create() and DOM structure ───────────────────────────────────────────────

describe('TDatePicker#create — calendar DOM structure', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('creates a _calDiv element', () => {
		expect(picker._calDiv).toBeDefined();
		expect(picker._calDiv.tagName.toLowerCase()).toBe('div');
	});

	it('_calDiv has position:absolute (floats over content)', () => {
		// create() sets position absolute so the calendar overlays the page.
		// (display may be 'none' or 'block' depending on whether the focus
		//  event fired by create() triggered show(); test position instead.)
		expect(picker._calDiv.style.position).toBe('absolute');
	});

	it('_calDiv has a calendarHeader child', () => {
		const header = picker._calDiv.querySelector('.calendarHeader');
		expect(header).not.toBeNull();
	});

	it('_calDiv has a calendarBody child', () => {
		const body = picker._calDiv.querySelector('.calendarBody');
		expect(body).not.toBeNull();
	});

	it('_calDiv has a calendarFooter child', () => {
		const footer = picker._calDiv.querySelector('.calendarFooter');
		expect(footer).not.toBeNull();
	});

	it('creates a prevMonthButton inside the header', () => {
		const btn = picker._calDiv.querySelector('.prevMonthButton');
		expect(btn).not.toBeNull();
		expect(btn.value).toBe('<<');
	});

	it('creates a nextMonthButton inside the header', () => {
		const btn = picker._calDiv.querySelector('.nextMonthButton');
		expect(btn).not.toBeNull();
		expect(btn.value).toBe('>>');
	});

	it('creates a month <select> with 12 options', () => {
		expect(picker._monthSelect).not.toBeNull();
		expect(picker._monthSelect.options).toHaveLength(12);
	});

	it('month <select> option 0 is January', () => {
		expect(picker._monthSelect.options[0].innerHTML).toBe('January');
	});

	it('month <select> option 11 is December', () => {
		expect(picker._monthSelect.options[11].innerHTML).toBe('December');
	});

	it('creates a year <select> with options from FromYear to UpToYear', () => {
		const count = picker.UpToYear - picker.FromYear + 1;
		expect(picker._yearSelect.options).toHaveLength(count);
	});

	it('year <select> first option equals FromYear', () => {
		expect(Number(picker._yearSelect.options[0].value)).toBe(picker.FromYear);
	});

	it('year <select> last option equals UpToYear', () => {
		const last = picker._yearSelect.options[picker._yearSelect.options.length - 1];
		expect(Number(last.value)).toBe(picker.UpToYear);
	});

	it('creates 7 weekday header cells', () => {
		const headers = picker._calDiv.querySelectorAll('th.weekDayHead');
		expect(headers).toHaveLength(7);
	});

	it('creates exactly 42 date slot td cells (6 weeks × 7 days)', () => {
		const cells = picker._calDiv.querySelectorAll('td.calendarDate, td.date, td.empty');
		// dateSlot array always has 42 entries
		expect(picker.dateSlot).toHaveLength(42);
	});

	it('creates a todayButton in the footer', () => {
		const btn = picker._calDiv.querySelector('.todayButton');
		expect(btn).not.toBeNull();
	});

	it('create() is idempotent — second call does not create a second _calDiv', () => {
		const first = picker._calDiv;
		picker.create();
		expect(picker._calDiv).toBe(first);
	});
});

// ─── update() — calendar grid population ─────────────────────────────────────

describe('TDatePicker#update — calendar grid', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	/** Count slots that have a positive date value. */
	function countFilledSlots(picker) {
		return picker.dateSlot.filter(s => s.value > 0).length;
	}

	it('fills exactly 28 slots for February 2023 (non-leap, starts on Wed)', () => {
		picker.setSelectedDate(new Date(2023, 1, 1));
		expect(countFilledSlots(picker)).toBe(28);
	});

	it('fills exactly 29 slots for February 2024 (leap, starts on Thu)', () => {
		picker.setSelectedDate(new Date(2024, 1, 1));
		expect(countFilledSlots(picker)).toBe(29);
	});

	it('fills exactly 31 slots for January 2024', () => {
		picker.setSelectedDate(new Date(2024, 0, 1));
		expect(countFilledSlots(picker)).toBe(31);
	});

	it('fills exactly 30 slots for April 2024', () => {
		picker.setSelectedDate(new Date(2024, 3, 1));
		expect(countFilledSlots(picker)).toBe(30);
	});

	it('fills exactly 31 slots for December 2024', () => {
		picker.setSelectedDate(new Date(2024, 11, 1));
		expect(countFilledSlots(picker)).toBe(31);
	});

	it('slot values run from 1 to monthLength in order', () => {
		picker.setSelectedDate(new Date(2024, 2, 1)); // March
		const filled = picker.dateSlot.filter(s => s.value > 0).map(s => s.value);
		expect(filled).toHaveLength(31);
		expect(filled[0]).toBe(1);
		expect(filled[30]).toBe(31);
	});

	it('empty slots before and after have value -1', () => {
		picker.setSelectedDate(new Date(2024, 2, 1));
		const empty = picker.dateSlot.filter(s => s.value === -1);
		expect(empty.length).toBeGreaterThan(0);
	});

	it('marks the selected day slot with class containing "selected"', () => {
		picker.setSelectedDate(new Date(2024, 2, 15));
		const selectedSlots = picker.dateSlot.filter(s => {
			return s.value > 0 && s.data.parentNode.className.includes('selected');
		});
		expect(selectedSlots).toHaveLength(1);
		expect(selectedSlots[0].value).toBe(15);
	});

	it('total slot count is always 42', () => {
		picker.setSelectedDate(new Date(2024, 5, 1));
		expect(picker.dateSlot).toHaveLength(42);
	});

	// Week day header order when FirstDayOfWeek is Monday (1)
	it('first weekday header is Mon when FirstDayOfWeek=1', () => {
		picker.FirstDayOfWeek = 1;
		// rebuild so headers are recreated
		delete picker._calDiv;
		picker.create();
		const headers = [...picker._calDiv.querySelectorAll('th.weekDayHead')];
		expect(headers[0].textContent).toBe('Mon');
	});

	it('first weekday header is Sun when FirstDayOfWeek=0', () => {
		delete picker._calDiv;
		picker.FirstDayOfWeek = 0;
		picker.create();
		const headers = [...picker._calDiv.querySelectorAll('th.weekDayHead')];
		expect(headers[0].textContent).toBe('Sun');
	});
});

// ─── updateHeader() ───────────────────────────────────────────────────────────

describe('TDatePicker#updateHeader', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('selects the correct month in the month dropdown', () => {
		picker.setSelectedDate(new Date(2024, 6, 1)); // July = index 6
		const selected = [...picker._monthSelect.options].find(o => o.selected);
		expect(Number(selected.value)).toBe(6);
	});

	it('selects the correct year in the year dropdown', () => {
		picker.setSelectedDate(new Date(2024, 0, 1));
		const selected = [...picker._yearSelect.options].find(o => o.selected);
		expect(Number(selected.value)).toBe(2024);
	});

	it('updates month dropdown when month changes', () => {
		picker.setSelectedDate(new Date(2024, 0, 1));
		picker.setSelectedDate(new Date(2024, 11, 1)); // December
		const selected = [...picker._monthSelect.options].find(o => o.selected);
		expect(Number(selected.value)).toBe(11);
	});
});

// ─── show() and hide() ────────────────────────────────────────────────────────

describe('TDatePicker#show and hide', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('show() creates the calendar if not yet created', () => {
		expect(picker._calDiv).toBeUndefined();
		picker.show();
		expect(picker._calDiv).toBeDefined();
	});

	it('show() sets showing to true', () => {
		picker.show();
		expect(picker.showing).toBe(true);
	});

	it('show() makes _calDiv visible (display != none)', () => {
		picker.show();
		expect(picker._calDiv.style.display).not.toBe('none');
	});

	it('hide() sets showing to false', () => {
		picker.show();
		picker.hide();
		expect(picker.showing).toBe(false);
	});

	it('hide() sets _calDiv.style.display to none', () => {
		picker.show();
		picker.hide();
		expect(picker._calDiv.style.display).toBe('none');
	});

	it('hide() does nothing when already hidden', () => {
		// should not throw
		expect(() => picker.hide()).not.toThrow();
	});

	it('show() reads date from input field and updates selectedDate', () => {
		picker.control.value = '2025-03-20';
		picker.show();
		expect(picker.selectedDate.getFullYear()).toBe(2025);
		expect(picker.selectedDate.getMonth()).toBe(2);
		expect(picker.selectedDate.getDate()).toBe(20);
	});
});

// ─── getDateFromInput ─────────────────────────────────────────────────────────

describe('TDatePicker#getDateFromInput', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { Format: 'yyyy-MM-dd' }));
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('returns null when the input is empty', () => {
		picker.control.value = '';
		expect(picker.getDateFromInput()).toBeNull();
	});

	it('parses a valid date string from the input', () => {
		picker.control.value = '2024-06-15';
		const d = picker.getDateFromInput();
		expect(d).not.toBeNull();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(5);
		expect(d.getDate()).toBe(15);
	});

	it('returns null for an unparseable string', () => {
		picker.control.value = 'not-a-date';
		expect(picker.getDateFromInput()).toBeNull();
	});
});

// ─── onChange (TextBox mode) ──────────────────────────────────────────────────

describe('TDatePicker#onChange — TextBox mode', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { InputMode: 'TextBox', Format: 'yyyy-MM-dd' }));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('sets the text input value to the formatted date', () => {
		picker.setSelectedDate(new Date(2024, 5, 15));
		expect(picker.control.value).toBe('2024-06-15');
	});

	it('updates the text input when the date changes via setSelectedDate', () => {
		picker.setSelectedDate(new Date(2024, 11, 31));
		expect(picker.control.value).toBe('2024-12-31');
	});
});

// ─── selectToday ─────────────────────────────────────────────────────────────

describe('TDatePicker#selectToday', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
		picker.show();
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('sets selectedDate to today', () => {
		const today = new Date();
		picker.setSelectedDate(new Date(2020, 0, 1)); // pick a different date first
		picker.selectToday();
		const sel = picker.selectedDate;
		expect(sel.getFullYear()).toBe(today.getFullYear());
		expect(sel.getMonth()).toBe(today.getMonth());
		expect(sel.getDate()).toBe(today.getDate());
	});

	it('hides the calendar when today is already selected', () => {
		picker.setSelectedDate(picker.newDate()); // today
		picker.showing = true;
		picker.selectToday();
		expect(picker.showing).toBe(false);
	});
});

// ─── monthSelect / yearSelect event handlers ──────────────────────────────────

describe('TDatePicker#monthSelect and yearSelect', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
		picker.setSelectedDate(new Date(2024, 0, 15));
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('monthSelect() updates the month from the event target value', () => {
		picker.monthSelect({ target: { value: 6 } });
		expect(picker.selectedDate.getMonth()).toBe(6);
	});

	it('yearSelect() updates the year from the event target value', () => {
		picker.yearSelect({ target: { value: 2025 } });
		expect(picker.selectedDate.getFullYear()).toBe(2025);
	});
});

// ─── selectDate (click on a date cell) ────────────────────────────────────────

describe('TDatePicker#selectDate', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
		picker.setSelectedDate(new Date(2024, 2, 1)); // March 2024
		picker.show();
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('selects the date when a valid day cell is clicked', () => {
		// Find a slot with value 15 and simulate a click on its td
		const slot15 = picker.dateSlot.find(s => s.value === 15);
		expect(slot15).toBeDefined();
		const td = slot15.data.parentNode;
		// Construct a synthetic event
		const fakeEvent = { target: td, preventDefault: vi.fn() };
		picker.selectDate(fakeEvent);
		expect(picker.selectedDate.getDate()).toBe(15);
	});

	it('hides the calendar after selecting a date', () => {
		const slot10 = picker.dateSlot.find(s => s.value === 10);
		const td = slot10.data.parentNode;
		picker.selectDate({ target: td, preventDefault: vi.fn() });
		expect(picker.showing).toBe(false);
	});

	it('ignores clicks on empty slots (value <= 0)', () => {
		const emptySlot = picker.dateSlot.find(s => s.value === -1);
		expect(emptySlot).toBeDefined();
		const td = emptySlot.data.parentNode;
		const before = picker.selectedDate.getDate();
		picker.selectDate({ target: td, preventDefault: vi.fn() });
		// date should be unchanged
		expect(picker.selectedDate.getDate()).toBe(before);
	});

	it('ignores clicks where no td ancestor can be found', () => {
		// Pass a span element that has no td ancestor
		const span = document.createElement('span');
		document.body.appendChild(span);
		expect(() => picker.selectDate({ target: span, preventDefault: vi.fn() })).not.toThrow();
		span.remove();
	});
});

// ─── mouseWheelChange ─────────────────────────────────────────────────────────

describe('TDatePicker#mouseWheelChange', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
		picker.setSelectedDate(new Date(2024, 5, 15)); // June
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('scrolling forward (wheelDelta positive) moves to next month', () => {
		picker.mouseWheelChange({ wheelDelta: 120 });
		expect(picker.selectedDate.getMonth()).toBe(6); // July
	});

	it('scrolling backward (wheelDelta negative) moves to prev month', () => {
		picker.mouseWheelChange({ wheelDelta: -120 });
		expect(picker.selectedDate.getMonth()).toBe(4); // May
	});

	it('scrolling forward using detail (Firefox) moves to prev month', () => {
		// detail > 0 means scroll down → prev month
		picker.mouseWheelChange({ detail: 3 });
		expect(picker.selectedDate.getMonth()).toBe(4); // May
	});

	it('returns false', () => {
		const result = picker.mouseWheelChange({ wheelDelta: 120 });
		expect(result).toBe(false);
	});
});

// ─── hideOnClick ──────────────────────────────────────────────────────────────

describe('TDatePicker#hideOnClick', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.show();
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('hides the calendar when clicking outside any calendar element', () => {
		const outside = document.createElement('div');
		document.body.appendChild(outside);
		picker.hideOnClick({ target: outside });
		expect(picker.showing).toBe(false);
		outside.remove();
	});

	it('does not hide when clicking inside the calendar div', () => {
		// The _calDiv has the TDatePicker_default class
		picker.hideOnClick({ target: picker._calDiv });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when clicking on the trigger element', () => {
		picker.hideOnClick({ target: picker.trigger });
		expect(picker.showing).toBe(true);
	});

	it('does not hide when clicking on the control (input)', () => {
		picker.hideOnClick({ target: picker.control });
		expect(picker.showing).toBe(true);
	});

	it('does nothing when showing is false', () => {
		picker.hide();
		expect(() => picker.hideOnClick({ target: document.body })).not.toThrow();
	});
});

// ─── keyPressed ───────────────────────────────────────────────────────────────

describe('TDatePicker#keyPressed', () => {
	let picker;
	const id = nextId();

	function fakeKey(kc, extra = {}) {
		return Object.assign({ keyCode: kc, preventDefault: vi.fn(), charCode: null, ctrlKey: false, shiftKey: false }, extra);
	}

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
		picker.setSelectedDate(new Date(2024, 5, 15)); // June 15, 2024
		picker.show();
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('does nothing when showing is false', () => {
		picker.showing = false;
		const before = picker.selectedDate.getDate();
		picker.keyPressed(fakeKey(39)); // right arrow
		expect(picker.selectedDate.getDate()).toBe(before);
	});

	it('Enter (13) hides the calendar', () => {
		picker.keyPressed(fakeKey(13));
		expect(picker.showing).toBe(false);
	});

	it('Space (32) hides the calendar', () => {
		picker.keyPressed(fakeKey(32));
		expect(picker.showing).toBe(false);
	});

	it('Escape (27) hides the calendar', () => {
		picker.keyPressed(fakeKey(27));
		expect(picker.showing).toBe(false);
	});

	it('Right arrow (39) moves forward one day', () => {
		picker.keyPressed(fakeKey(39));
		expect(picker.selectedDate.getDate()).toBe(16);
	});

	it('Left arrow (37) moves backward one day', () => {
		picker.keyPressed(fakeKey(37));
		expect(picker.selectedDate.getDate()).toBe(14);
	});

	it('Up arrow (38) moves backward seven days', () => {
		picker.keyPressed(fakeKey(38));
		expect(picker.selectedDate.getDate()).toBe(8); // 15 - 7 = 8
	});

	it('Right arrow + Ctrl moves forward one month', () => {
		picker.keyPressed(fakeKey(39, { ctrlKey: true }));
		expect(picker.selectedDate.getMonth()).toBe(6); // July
	});

	it('Left arrow + Ctrl moves backward one month', () => {
		picker.keyPressed(fakeKey(37, { ctrlKey: true }));
		expect(picker.selectedDate.getMonth()).toBe(4); // May
	});

	it('Up arrow + Ctrl moves backward one year', () => {
		picker.keyPressed(fakeKey(38, { ctrlKey: true }));
		expect(picker.selectedDate.getFullYear()).toBe(2023);
	});

	it('Down arrow + Ctrl moves forward one year', () => {
		picker.keyPressed(fakeKey(40, { ctrlKey: true }));
		expect(picker.selectedDate.getFullYear()).toBe(2025);
	});

	it('non-arrow, non-special keys return true (pass-through)', () => {
		const result = picker.keyPressed(fakeKey(65)); // 'a'
		expect(result).toBe(true);
	});
});

// ─── getElement ───────────────────────────────────────────────────────────────

describe('TDatePicker#getElement', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('returns the _calDiv element', () => {
		expect(picker.getElement()).toBe(picker._calDiv);
	});
});

// ─── PositionMode Top option ──────────────────────────────────────────────────

describe('TDatePicker PositionMode=Top', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { PositionMode: 'Top' }));
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('sets positionMode to Top', () => {
		expect(picker.positionMode).toBe('Top');
	});
});

// ─── Trigger element option ───────────────────────────────────────────────────

describe('TDatePicker with separate Trigger element', () => {
	let picker;
	const id = nextId();
	const triggerId = `${id}_btn`;

	beforeEach(() => {
		buildDOM(id, triggerId);
		picker = new TDatePicker(makeOptions(id, { Trigger: triggerId }));
	});

	afterEach(() => {
		picker.showing && picker.hide();
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('uses the trigger element (not the control) as the trigger', () => {
		expect(picker.trigger).toBe(document.getElementById(triggerId));
	});

	it('the trigger element is different from the control', () => {
		expect(picker.trigger).not.toBe(picker.control);
	});
});

// ─── OnDateChanged callback (TextBox mode) ────────────────────────────────────

describe('TDatePicker OnDateChanged callback — TextBox mode', () => {
	let picker;
	const id = nextId();

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('calls OnDateChanged with (picker, dateString) when the input changes', () => {
		const cb = vi.fn();
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { OnDateChanged: cb, InputMode: 'TextBox', Format: 'yyyy-MM-dd' }));
		picker.create();

		picker.control.value = '2024-07-04';
		jQuery(picker.control).trigger('change');

		expect(cb).toHaveBeenCalledTimes(1);
		const [ref, dateStr] = cb.mock.calls[0];
		expect(ref).toBe(picker);
		expect(dateStr).toBe('2024-07-04');
	});
});

// ─── Static helper: getDropDownDate ──────────────────────────────────────────

describe('TDatePicker.getDropDownDate', () => {
	let baseId;
	let domInfo;

	beforeEach(() => {
		baseId = nextId();
		domInfo = buildDropDownDOM(baseId);
	});

	afterEach(() => {
		domInfo.container.remove();
	});

	it('returns a Date', () => {
		const anchor = document.getElementById(baseId);
		const d = TDatePicker.getDropDownDate(anchor);
		expect(d instanceof Date).toBe(true);
	});

	it('reads the selected day from the day drop-down', () => {
		const anchor = document.getElementById(baseId);
		const daySelect = document.getElementById(`${baseId}_day`);
		daySelect.selectedIndex = 14; // day 15 (index 14 → value 15)
		const d = TDatePicker.getDropDownDate(anchor);
		expect(d.getDate()).toBe(15);
	});

	it('reads the selected month from the month drop-down', () => {
		const anchor = document.getElementById(baseId);
		const monthSelect = document.getElementById(`${baseId}_month`);
		monthSelect.selectedIndex = 5; // June
		const d = TDatePicker.getDropDownDate(anchor);
		expect(d.getMonth()).toBe(5);
	});

	it('reads the selected year from the year drop-down', () => {
		const anchor = document.getElementById(baseId);
		const yearSelect = document.getElementById(`${baseId}_year`);
		// find 2025 option
		const idx = [...yearSelect.options].findIndex(o => Number(o.value) === 2025);
		yearSelect.selectedIndex = idx;
		const d = TDatePicker.getDropDownDate(anchor);
		expect(d.getFullYear()).toBe(2025);
	});
});

// ─── Static helper: getDayListControl / getMonthListControl / getYearListControl

describe('TDatePicker static list control locators', () => {
	let baseId;
	let domInfo;

	beforeEach(() => {
		baseId = nextId();
		domInfo = buildDropDownDOM(baseId);
	});

	afterEach(() => {
		domInfo.container.remove();
	});

	it('getDayListControl returns the day select element', () => {
		const anchor = document.getElementById(baseId);
		expect(TDatePicker.getDayListControl(anchor)).toBe(document.getElementById(`${baseId}_day`));
	});

	it('getMonthListControl returns the month select element', () => {
		const anchor = document.getElementById(baseId);
		expect(TDatePicker.getMonthListControl(anchor)).toBe(document.getElementById(`${baseId}_month`));
	});

	it('getYearListControl returns the year select element', () => {
		const anchor = document.getElementById(baseId);
		expect(TDatePicker.getYearListControl(anchor)).toBe(document.getElementById(`${baseId}_year`));
	});

	it('getDayListControl returns undefined when element is absent', () => {
		const fakeAnchor = { id: 'nonexistent_99999' };
		expect(TDatePicker.getDayListControl(fakeAnchor)).toBeFalsy();
	});
});

// ─── DropDownList InputMode — onChange ────────────────────────────────────────

describe('TDatePicker#onChange — DropDownList mode', () => {
	let picker;
	let baseId;
	let domInfo;

	beforeEach(() => {
		baseId = nextId();
		domInfo = buildDropDownDOM(baseId);

		picker = new TDatePicker({
			ID: baseId,
			InputMode: 'DropDownList',
			Format: 'yyyy-MM-dd',
			FromYear: 2000,
			UpToYear: 2030,
		});
		picker.create();
	});

	afterEach(() => {
		domInfo.container.remove();
		delete global.Prado.Registry[baseId];
	});

	it('updates the day drop-down selectedIndex to match selectedDate day (1-based)', () => {
		picker.setSelectedDate(new Date(2024, 5, 20));
		const day = document.getElementById(`${baseId}_day`);
		// day 20 → selectedIndex should be 19 (0-based)
		expect(day.selectedIndex).toBe(19);
	});

	it('updates the month drop-down selectedIndex to match selectedDate month', () => {
		picker.setSelectedDate(new Date(2024, 7, 1)); // August = index 7
		const month = document.getElementById(`${baseId}_month`);
		expect(month.selectedIndex).toBe(7);
	});

	it('updates the year drop-down to match selectedDate year', () => {
		picker.setSelectedDate(new Date(2024, 0, 1));
		const year = document.getElementById(`${baseId}_year`);
		const selected = [...year.options].find(o => o.selected);
		expect(Number(selected.value)).toBe(2024);
	});
});

// ─── Edge cases: leap-year boundary months ────────────────────────────────────

describe('TDatePicker leap-year and boundary edge cases', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { FromYear: 1900, UpToYear: 2100 }));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('getDaysPerMonth(1, 2000) === 29 (divisible by 400)', () => {
		expect(picker.getDaysPerMonth(1, 2000)).toBe(29);
	});

	it('getDaysPerMonth(1, 1900) === 28 (divisible by 100, not 400)', () => {
		expect(picker.getDaysPerMonth(1, 1900)).toBe(28);
	});

	it('getDaysPerMonth(1, 2100) === 28 (divisible by 100, not 400)', () => {
		expect(picker.getDaysPerMonth(1, 2100)).toBe(28);
	});

	it('getDaysPerMonth(1, 2096) === 29 (divisible by 4, not 100)', () => {
		expect(picker.getDaysPerMonth(1, 2096)).toBe(29);
	});

	it('navigating nextMonth from December increments year to 2025', () => {
		picker.setSelectedDate(new Date(2024, 11, 1));
		picker.nextMonth();
		expect(picker.selectedDate.getFullYear()).toBe(2025);
		expect(picker.selectedDate.getMonth()).toBe(0);
	});

	it('navigating prevMonth from January decrements year to 2023', () => {
		picker.setSelectedDate(new Date(2024, 0, 1));
		picker.prevMonth();
		expect(picker.selectedDate.getFullYear()).toBe(2023);
		expect(picker.selectedDate.getMonth()).toBe(11);
	});
});

// ─── Custom CalendarStyle className ──────────────────────────────────────────

describe('TDatePicker CalendarStyle and ClassName options', () => {
	let picker;
	const id = nextId();

	beforeEach(() => {
		buildDOM(id);
		picker = new TDatePicker(makeOptions(id, { CalendarStyle: 'compact', ClassName: 'my-picker' }));
		picker.create();
	});

	afterEach(() => {
		document.getElementById(`${id}_container`)?.remove();
		delete global.Prado.Registry[id];
	});

	it('_calDiv className includes TDatePicker_{CalendarStyle}', () => {
		expect(picker._calDiv.className).toContain('TDatePicker_compact');
	});

	it('_calDiv className includes the custom ClassName', () => {
		expect(picker._calDiv.className).toContain('my-picker');
	});
});
