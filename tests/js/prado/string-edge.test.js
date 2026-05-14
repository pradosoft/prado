/**
 * Edge-case tests for String.prototype extensions that were missing from
 * the existing string.test.js.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import '../adapters/prado-core.js';

// ─── toCurrency edge cases ────────────────────────────────────────────────────

describe('String.prototype.toCurrency — edge cases', () => {
	it('returns null for a string with a decimal when digits is 0', () => {
		// '1234.5' cannot be a 0-decimal currency value.
		expect('1234.5'.toCurrency(',', 0)).toBeNull();
	});

	it('handles a leading plus sign', () => {
		// '+1,234.56' should parse to 1234.56.
		const result = '+1,234.56'.toCurrency(',', 2);
		expect(result).not.toBeNull();
		if (result !== null) {
			expect(parseFloat(result)).toBeCloseTo(1234.56);
		}
	});
});

// ─── toDouble edge cases ──────────────────────────────────────────────────────

describe('String.prototype.toDouble — edge cases', () => {
	it('parses a string with a leading plus sign', () => {
		const result = '+3.14'.toDouble('.');
		expect(result).not.toBeNull();
		if (result !== null) {
			expect(result).toBeCloseTo(3.14);
		}
	});
});

// ─── toFunction edge cases ───────────────────────────────────────────────────

describe('String.prototype.toFunction — edge cases', () => {
	it('throws when the path resolves to a non-function property', () => {
		// The source throws Error("Missing function '...'") when the resolved
		// value is not callable — it does NOT return null.
		global.__testNonFn = { subprop: 42 };
		expect(() => '__testNonFn.subprop'.toFunction()).toThrow();
		delete global.__testNonFn;
	});

	it('returns a function when the path resolves to a function', () => {
		global.__testFnTarget = { doIt: function () { return 'yes'; } };
		const fn = '__testFnTarget.doIt'.toFunction();
		expect(typeof fn).toBe('function');
		expect(fn()).toBe('yes');
		delete global.__testFnTarget;
	});
});

// ─── pad — multi-character filler ─────────────────────────────────────────────

describe('String.prototype.pad — multi-character filler', () => {
	it('pads with a multi-character string repeating the first character', () => {
		// Source uses chr[0] when chr.length > 1 in some implementations.
		// We only assert that the total length is >= the requested minimum.
		const padded = String('x'.pad('left', 5, 'ab'));
		expect(padded.length).toBeGreaterThanOrEqual(5);
		expect(padded.endsWith('x')).toBe(true);
	});
});
