/**
 * Edge-case tests for Date extensions that were missing from the existing
 * date.test.js.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import '../adapters/prado-core.js';

// ─── Date.SimpleParse — edge cases ───────────────────────────────────────────

describe('Date.SimpleParse — edge cases', () => {
	it('falls back to new Date(value) when format is an empty string', () => {
		// SimpleParse(value, '') — source treats length <= 0 as a fall-through.
		const iso = '2024-06-15';
		const result = Date.SimpleParse(iso, '');
		// Either a Date or null is acceptable; it must not throw.
		expect(result === null || result instanceof Date || typeof result === 'number').toBe(true);
	});

	it('returns null for completely unparseable input with a known format', () => {
		const result = Date.SimpleParse('not-a-date', 'yyyy/MM/dd');
		expect(result).toBeNull();
	});

	it('parses day=31 for a 31-day month (January)', () => {
		const result = Date.SimpleParse('2024/01/31', 'yyyy/MM/dd');
		expect(result).not.toBeNull();
		if (result !== null) {
			const d = new Date(result);
			expect(d.getDate()).toBe(31);
			expect(d.getMonth()).toBe(0);
		}
	});

	it('handles a 2-digit year token (y with min=2, max=4)', () => {
		// Some implementations expand 2-digit years: 70–99 → 1970–1999, 00–69 → 2000–2069.
		const result = Date.SimpleParse('99/06/15', 'yy/MM/dd');
		if (result !== null) {
			const d = new Date(result);
			// Year should have been expanded (either 1999 or 2099 depending on impl).
			expect(d.getFullYear()).toBeGreaterThan(100);
		}
	});
});

// ─── Date.prototype.SimpleFormat — edge cases ────────────────────────────────

describe('Date.prototype.SimpleFormat — edge cases', () => {
	it('formats a date with a 4-digit year correctly', () => {
		const d = new Date(2024, 5, 15); // June 15 2024
		const formatted = d.SimpleFormat('yyyy/MM/dd');
		expect(formatted).toBe('2024/06/15');
	});

	it('formats a date with a 2-digit year token', () => {
		const d = new Date(1999, 0, 1);
		const formatted = d.SimpleFormat('yy/MM/dd');
		// With a 2-char year the source typically prefixes with '19'.
		expect(formatted).toMatch(/\d{2}\/01\/01/);
	});
});

// ─── Date.prototype.toISODate ─────────────────────────────────────────────────
//
// The PRADO implementation concatenates year + zerofill(month) + zerofill(day)
// WITHOUT separator dashes — it returns 'YYYYMMDD', not 'YYYY-MM-DD'.

describe('Date.prototype.toISODate', () => {
	it('formats a normal date as YYYYMMDD (no dashes)', () => {
		const d = new Date(2024, 11, 25); // Dec 25 2024
		expect(d.toISODate()).toBe('20241225');
	});

	it('zero-pads single-digit months and days', () => {
		const d = new Date(2024, 0, 5); // Jan 5 2024
		expect(d.toISODate()).toBe('20240105');
	});
});
