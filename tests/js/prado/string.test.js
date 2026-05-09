/**
 * Tests for String.prototype extensions added by prado.js.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * These extensions are loaded as a side-effect of importing the adapter.
 * If prado.js is later refactored to export standalone pure functions
 * instead of extending String.prototype, the adapter would export those
 * functions and these tests would call them directly — the test logic
 * itself (inputs and expected outputs) would remain the same.
 *
 * ESM note: only tests/js/adapters/prado-core.js changes on ESM conversion.
 */

// Import adapter for its side-effect: loading prado.js extends String.prototype.
import '../adapters/prado-core.js';

// ─── pad / padLeft / padRight / zerofill ─────────────────────────────────────

describe('String.prototype.pad', () => {
	it('pads left with spaces by default', () => {
		expect('hi'.pad('left', 5)).toBe('   hi');
	});

	it('pads right with spaces by default', () => {
		expect('hi'.pad('right', 5)).toBe('hi   ');
	});

	it('pads with a custom character', () => {
		expect('5'.pad('left', 3, '0')).toBe('005');
	});

	it('does not pad when already at the minimum length', () => {
		// pad returns `this` (String wrapper) when no padding is needed;
		// coerce to primitive for the equality check.
		expect(String('hello'.pad('left', 5))).toBe('hello');
	});

	it('does not truncate strings that exceed minimum length', () => {
		expect(String('toolong'.pad('left', 3))).toBe('toolong');
	});
});

describe('String.prototype.padLeft', () => {
	it('pads a short number string to the given length', () => {
		expect('42'.padLeft(5, '0')).toBe('00042');
	});

	it('returns the string unchanged when already long enough', () => {
		expect(String('hello'.padLeft(3, 'x'))).toBe('hello');
	});
});

describe('String.prototype.padRight', () => {
	it('pads to the right with the given character', () => {
		expect('42'.padRight(5, '-')).toBe('42---');
	});
});

describe('String.prototype.zerofill', () => {
	it('zero-pads to the specified length', () => {
		expect('7'.zerofill(3)).toBe('007');
	});

	it('does not truncate strings that are already long enough', () => {
		expect(String('12345'.zerofill(3))).toBe('12345');
	});
});

// ─── trim / trimLeft / trimRight ─────────────────────────────────────────────

describe('String.prototype.trim', () => {
	it('removes leading and trailing whitespace', () => {
		expect('  hello  '.trim()).toBe('hello');
	});

	it('returns an empty string for a whitespace-only string', () => {
		expect('   '.trim()).toBe('');
	});

	it('leaves interior whitespace intact', () => {
		expect('  a b  '.trim()).toBe('a b');
	});
});

describe('String.prototype.trimLeft', () => {
	it('removes only leading whitespace', () => {
		expect('  hello  '.trimLeft()).toBe('hello  ');
	});
});

describe('String.prototype.trimRight', () => {
	it('removes only trailing whitespace', () => {
		expect('  hello  '.trimRight()).toBe('  hello');
	});
});

// ─── toInteger ───────────────────────────────────────────────────────────────

describe('String.prototype.toInteger', () => {
	it('converts a positive integer string', () => {
		expect('42'.toInteger()).toBe(42);
	});

	it('converts a negative integer string', () => {
		expect('-7'.toInteger()).toBe(-7);
	});

	it('converts a string with surrounding whitespace', () => {
		expect('  10  '.toInteger()).toBe(10);
	});

	it('returns null for a float string', () => {
		expect('3.14'.toInteger()).toBeNull();
	});

	it('returns null for a non-numeric string', () => {
		expect('abc'.toInteger()).toBeNull();
	});

	it('returns null for an empty string', () => {
		expect(''.toInteger()).toBeNull();
	});

	it('returns null for a string with alphabetic suffix', () => {
		expect('42px'.toInteger()).toBeNull();
	});
});

// ─── toDouble ────────────────────────────────────────────────────────────────

describe('String.prototype.toDouble', () => {
	it('converts a positive decimal string', () => {
		expect('3.14'.toDouble()).toBeCloseTo(3.14);
	});

	it('converts a negative decimal string', () => {
		expect('-2.5'.toDouble()).toBeCloseTo(-2.5);
	});

	it('converts an integer (no decimal point)', () => {
		expect('42'.toDouble()).toBe(42);
	});

	it('handles a custom decimal character', () => {
		expect('3,14'.toDouble(',')).toBeCloseTo(3.14);
	});

	it('returns null for an empty string', () => {
		expect(''.toDouble()).toBeNull();
	});

	it('returns null for a non-numeric string', () => {
		expect('abc'.toDouble()).toBeNull();
	});

	it('handles a leading decimal point (no integer part)', () => {
		// ".5" matches the pattern: integer part is empty (0), decimal is 5
		expect('.5'.toDouble()).toBeCloseTo(0.5);
	});
});

// ─── toCurrency ──────────────────────────────────────────────────────────────

describe('String.prototype.toCurrency', () => {
	it('converts a simple decimal currency string', () => {
		expect('1234.56'.toCurrency()).toBeCloseTo(1234.56);
	});

	it('strips thousands separator and parses correctly', () => {
		expect('1,234.56'.toCurrency()).toBeCloseTo(1234.56);
	});

	it('handles a negative currency value', () => {
		expect('-1,234.56'.toCurrency()).toBeCloseTo(-1234.56);
	});

	it('handles custom groupchar and decimalchar (European notation)', () => {
		expect('1.234,56'.toCurrency('.', 2, ',')).toBeCloseTo(1234.56);
	});

	it('returns null for a non-currency string', () => {
		expect('abc'.toCurrency()).toBeNull();
	});

	it('handles zero digits (integer currency)', () => {
		expect('1234'.toCurrency(',', 0)).toBe(1234);
	});
});

// ─── toFunction ──────────────────────────────────────────────────────────────

describe('String.prototype.toFunction', () => {
	it('resolves a dot-separated path to the correct function reference', () => {
		// Prado.Element.createOptions is a known exported function.
		const fn = 'Prado.Element.createOptions'.toFunction();
		expect(typeof fn).toBe('function');
	});

	it('throws when the path does not resolve to a function', () => {
		expect(() => 'Prado.Nonexistent.Method'.toFunction()).toThrow();
	});
});

// ─── px (String) ─────────────────────────────────────────────────────────────

describe('String.prototype.px', () => {
	it('appends "px" to a numeric string', () => {
		expect('42'.px()).toBe('42px');
	});

	it('does not double-append "px"', () => {
		// px() returns `this` (String wrapper) when already ending with 'px';
		// coerce to primitive for the equality check.
		expect(String('42px'.px())).toBe('42px');
	});

	it('works with "0"', () => {
		expect('0'.px()).toBe('0px');
	});
	
	it('works with a negative number', () => {
		expect('-10'.px()).toBe('-10px');
	});
});

// ─── Number.prototype.px ─────────────────────────────────────────────────────

describe('Number.prototype.px', () => {
	it('converts a number to a px string', () => {
		expect((42).px()).toBe('42px');
	});

	it('works with zero', () => {
		expect((0).px()).toBe('0px');
	});

	it('works with a negative number', () => {
		expect((-10).px()).toBe('-10px');
	});
});
