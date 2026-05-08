/**
 * Tests for Date.prototype extensions and Date.SimpleParse added by prado.js.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * ESM note: only tests/js/adapters/prado-core.js changes on ESM conversion.
 */

// Import adapter for its side-effect: loading prado.js extends Date.prototype.
import '../adapters/prado-core.js';

// ─── Date.prototype.SimpleFormat ──────────────────────────────────────────────

describe('Date.prototype.SimpleFormat', () => {
	it('formats day and month with zero-padding using dd/MM', () => {
		const d = new Date(2024, 0, 5); // 5 Jan 2024
		expect(d.SimpleFormat('dd/MM/yyyy')).toBe('05/01/2024');
	});

	it('formats single-digit day and month without padding using d/M', () => {
		const d = new Date(2024, 0, 5);
		expect(d.SimpleFormat('d/M/yyyy')).toBe('5/1/2024');
	});

	it('formats a two-digit year with yy', () => {
		const d = new Date(2024, 5, 15); // 15 Jun 2024
		expect(d.SimpleFormat('yy')).toBe('24');
	});

	it('formats a four-digit year with yyyy', () => {
		const d = new Date(2024, 5, 15);
		expect(d.SimpleFormat('yyyy')).toBe('2024');
	});

	it('emits literal separators unchanged', () => {
		const d = new Date(2024, 11, 31); // 31 Dec 2024
		expect(d.SimpleFormat('yyyy-MM-dd')).toBe('2024-12-31');
	});

	it('formats abbreviated month names when data.AbbreviatedMonthNames is provided', () => {
		const d = new Date(2024, 0, 1); // January
		const data = {
			AbbreviatedMonthNames: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
		};
		expect(d.SimpleFormat('MMM yyyy', data)).toBe('Jan 2024');
	});

	it('formats full month names when data.MonthNames is provided', () => {
		const d = new Date(2024, 2, 1); // March
		const data = {
			MonthNames: ['January','February','March','April','May','June','July','August','September','October','November','December'],
		};
		expect(d.SimpleFormat('MMMM', data)).toBe('March');
	});
});

// ─── Date.prototype.toISODate ─────────────────────────────────────────────────

describe('Date.prototype.toISODate', () => {
	it('formats a date as YYYYMMDD with zero-padded month and day', () => {
		const d = new Date(2024, 0, 5); // 5 Jan 2024
		expect(d.toISODate()).toBe('20240105');
	});

	it('zero-pads single-digit month and day', () => {
		const d = new Date(2024, 8, 3); // 3 Sep 2024
		expect(d.toISODate()).toBe('20240903');
	});

	it('handles the last day of a year', () => {
		const d = new Date(2023, 11, 31);
		expect(d.toISODate()).toBe('20231231');
	});
});

// ─── Date.SimpleParse ────────────────────────────────────────────────────────

describe('Date.SimpleParse', () => {
	it('returns null for an empty value string', () => {
		expect(Date.SimpleParse('', 'yyyy/MM/dd')).toBeNull();
	});

	it('parses yyyy/MM/dd format', () => {
		const d = Date.SimpleParse('2024/01/05', 'yyyy/MM/dd');
		expect(d).not.toBeNull();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(0);  // January = 0
		expect(d.getDate()).toBe(5);
	});

	it('parses dd/MM/yyyy format', () => {
		const d = Date.SimpleParse('05/01/2024', 'dd/MM/yyyy');
		expect(d).not.toBeNull();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(0);
		expect(d.getDate()).toBe(5);
	});

	it('parses MM/dd/yyyy format', () => {
		const d = Date.SimpleParse('03/15/2024', 'MM/dd/yyyy');
		expect(d).not.toBeNull();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(2); // March = 2
		expect(d.getDate()).toBe(15);
	});

	it('parses yyyy-MM-dd with dashes', () => {
		const d = Date.SimpleParse('2024-06-15', 'yyyy-MM-dd');
		expect(d).not.toBeNull();
		expect(d.getFullYear()).toBe(2024);
		expect(d.getMonth()).toBe(5);
		expect(d.getDate()).toBe(15);
	});

	it('returns null for a string that does not match the format', () => {
		expect(Date.SimpleParse('not-a-date', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for month 0 (out of range)', () => {
		expect(Date.SimpleParse('2024/00/01', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for month 13 (out of range)', () => {
		expect(Date.SimpleParse('2024/13/01', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for day 0 (out of range)', () => {
		expect(Date.SimpleParse('2024/01/00', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for day 32 (out of range)', () => {
		expect(Date.SimpleParse('2024/01/32', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for February 30', () => {
		expect(Date.SimpleParse('2024/02/30', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for February 29 in a non-leap year', () => {
		expect(Date.SimpleParse('2023/02/29', 'yyyy/MM/dd')).toBeNull();
	});

	it('accepts February 29 in a leap year', () => {
		const d = Date.SimpleParse('2024/02/29', 'yyyy/MM/dd');
		expect(d).not.toBeNull();
		expect(d.getDate()).toBe(29);
	});

	it('accepts February 29 in a century leap year (divisible by 400)', () => {
		const d = Date.SimpleParse('2000/02/29', 'yyyy/MM/dd');
		expect(d).not.toBeNull();
	});

	it('rejects February 29 in a century non-leap year (divisible by 100 not 400)', () => {
		expect(Date.SimpleParse('1900/02/29', 'yyyy/MM/dd')).toBeNull();
	});

	it('returns null for April 31 (30-day month)', () => {
		expect(Date.SimpleParse('2024/04/31', 'yyyy/MM/dd')).toBeNull();
	});

	it('interprets a 2-digit year < 70 as 2000s', () => {
		const d = Date.SimpleParse('03/15/24', 'MM/dd/yy');
		expect(d.getFullYear()).toBe(2024);
	});

	it('interprets a 2-digit year >= 70 as 1900s', () => {
		const d = Date.SimpleParse('03/15/85', 'MM/dd/yy');
		expect(d.getFullYear()).toBe(1985);
	});

	it('returns null when trailing characters remain after parsing', () => {
		expect(Date.SimpleParse('2024/01/05extra', 'yyyy/MM/dd')).toBeNull();
	});
});
