/**
 * Tests for Prado.Validation and Prado.WebUI validators (validation3.js).
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * Strategy
 * --------
 * Where possible we test pure logic (type conversion, data comparison)
 * by calling prototype methods with a minimal this-context so no real DOM
 * elements are needed.  DOM-dependent tests set up and tear down elements
 * in beforeEach/afterEach.
 *
 * ESM note: only tests/js/adapters/validator.js changes on ESM conversion.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';

// ─── Prado.Validation static structure ───────────────────────────────────────

describe('Prado.Validation', () => {
	it('is a function (constructor / class)', () => {
		expect(typeof Validation).toBe('function');
	});

	it('has a managers registry', () => {
		expect(typeof Validation.managers).toBe('object');
	});

	it('has a validate() static method', () => {
		expect(typeof Validation.validate).toBe('function');
	});

	it('has a validateControl() static method', () => {
		expect(typeof Validation.validateControl).toBe('function');
	});

	it('has an addValidator() static method', () => {
		expect(typeof Validation.addValidator).toBe('function');
	});

	it('has an addSummary() static method', () => {
		expect(typeof Validation.addSummary).toBe('function');
	});

	it('has an isValid() static method', () => {
		expect(typeof Validation.isValid).toBe('function');
	});
});

// ─── Prado.ValidationManager structure ───────────────────────────────────────

describe('Prado.ValidationManager', () => {
	it('is a function (constructor / class)', () => {
		expect(typeof ValidationManager).toBe('function');
	});
});

// ─── Prado.WebUI validator classes ───────────────────────────────────────────

describe('Prado.WebUI validator classes exist', () => {
	it.each([
		'TBaseValidator',
		'TRequiredFieldValidator',
		'TCompareValidator',
		'TRangeValidator',
		'TRegularExpressionValidator',
		'TDataTypeValidator',
		'TListControlValidator',
		'TCustomValidator',
		'TCaptchaValidator',
	])('%s is a constructor', (name) => {
		expect(typeof WebUI[name]).toBe('function');
	});
});

// ─── TBaseValidator.convert — type conversion (pure logic, no DOM) ────────────
//
// convert() delegates to String.prototype.toInteger / toDouble / SimpleParse
// which are already covered in detail in string.test.js and date.test.js.
// Here we verify the routing logic of convert() itself.

describe('TBaseValidator.prototype.convert', () => {
	// Call convert() with a minimal this-context so we don't need a real DOM element.
	function convert(dataType, value, options = {}) {
		return WebUI.TBaseValidator.prototype.convert.call({ options }, dataType, value);
	}

	describe('Integer', () => {
		it('parses a valid integer string', () => {
			expect(convert('Integer', '42')).toBe(42);
		});

		it('returns null for a non-integer string', () => {
			expect(convert('Integer', 'abc')).toBeNull();
		});

		it('returns null for a float string', () => {
			expect(convert('Integer', '3.14')).toBeNull();
		});
	});

	describe('Double / Float', () => {
		it('parses a valid decimal string as Double', () => {
			expect(convert('Double', '3.14')).toBeCloseTo(3.14);
		});

		it('parses a valid decimal string as Float (alias)', () => {
			expect(convert('Float', '2.718')).toBeCloseTo(2.718);
		});

		it('returns null for a non-numeric string', () => {
			expect(convert('Double', 'abc')).toBeNull();
		});
	});

	describe('String', () => {
		it('returns the value as a string', () => {
			expect(convert('String', 42)).toBe('42');
		});

		it('returns an empty string for an empty input', () => {
			expect(convert('String', '')).toBe('');
		});
	});

	describe('Date', () => {
		it('parses a date string using the configured DateFormat', () => {
			const result = convert('Date', '2024/01/05', { DateFormat: 'yyyy/MM/dd' });
			// Returns getTime() (milliseconds) of the parsed date.
			expect(typeof result).toBe('number');
			const d = new Date(result);
			expect(d.getFullYear()).toBe(2024);
			expect(d.getMonth()).toBe(0);
			expect(d.getDate()).toBe(5);
		});

		it('returns null for an unparseable date string', () => {
			expect(convert('Date', 'not-a-date', { DateFormat: 'yyyy/MM/dd' })).toBeNull();
		});

		it('passes a Date object through unchanged', () => {
			const d = new Date(2024, 0, 5);
			// When value is already a Date object, convert returns it directly.
			expect(convert('Date', d)).toBe(d);
		});
	});
});

// ─── TBaseValidator.prototype.trim ────────────────────────────────────────────

describe('TBaseValidator.prototype.trim', () => {
	const trimFn = WebUI.TBaseValidator.prototype.trim;

	it('trims a string', () => {
		expect(trimFn('  hello  ')).toBe('hello');
	});

	it('returns empty string for non-string input', () => {
		expect(trimFn(null)).toBe('');
		expect(trimFn(42)).toBe('');
		expect(trimFn(undefined)).toBe('');
	});
});

// ─── TBaseValidator.prototype.isCheckBoxType ─────────────────────────────────

describe('TBaseValidator.prototype.isCheckBoxType', () => {
	const fn = WebUI.TBaseValidator.prototype.isCheckBoxType;

	it('returns true for checkbox', () => {
		expect(fn({ type: 'checkbox' })).toBe(true);
	});

	it('returns true for radio', () => {
		expect(fn({ type: 'radio' })).toBe(true);
	});

	it('returns false for text', () => {
		expect(fn({ type: 'text' })).toBe(false);
	});

	it('returns false for null', () => {
		expect(fn(null)).toBe(false);
	});

	it('returns false for element without type', () => {
		expect(fn({})).toBe(false);
	});
});

// ─── TRequiredFieldValidator — DOM integration test ───────────────────────────
//
// Instantiating a validator requires matching DOM elements and a
// ValidationManager.  We set those up in beforeEach and clean up in afterEach.

describe('TRequiredFieldValidator', () => {
	let form, input, span;

	beforeEach(() => {
		// Build a minimal form with a text input and a validator span.
		form  = document.createElement('form');
		form.id = 'testForm';

		input = document.createElement('input');
		input.id   = 'testInput';
		input.type = 'text';

		span  = document.createElement('span');
		span.id = 'testValidator';

		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);

		// A ValidationManager must exist for the form before creating validators.
		new ValidationManager({ FormID: 'testForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		// Clean up the Validation.managers registry so tests don't bleed.
		delete Validation.managers['testForm'];
	});

	it('registers in Prado.Registry on construction', () => {
		new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     '*',
			Enabled:          true,
		});
		expect(global.Prado.Registry['testValidator']).toBeDefined();
	});

	it('getErrorMessage returns the configured message', () => {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     'Field is required',
			Enabled:          true,
		});
		expect(v.getErrorMessage()).toBe('Field is required');
	});

	it('is initially valid (isValid = true)', () => {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     '*',
			Enabled:          true,
		});
		expect(v.isValid).toBe(true);
	});

	it('validates as invalid when the input is empty', () => {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     '*',
			Enabled:          true,
		});
		input.value = '';
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('validates as valid when the input has a value', () => {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     '*',
			Enabled:          true,
		});
		input.value = 'Hello';
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('validates as invalid when the input equals the InitialValue', () => {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'testValidator',
			FormID:           'testForm',
			ControlToValidate:'testInput',
			ErrorMessage:     '*',
			Enabled:          true,
			InitialValue:     'placeholder',
		});
		input.value = 'placeholder';
		v.validate();
		expect(v.isValid).toBe(false);
	});
});

// ─── TDataTypeValidator — pure data-type validation ──────────────────────────

describe('TDataTypeValidator', () => {
	let form, input, span;

	beforeEach(() => {
		form  = document.createElement('form');
		form.id = 'dtForm';
		input = document.createElement('input');
		input.id   = 'dtInput';
		input.type = 'text';
		span  = document.createElement('span');
		span.id = 'dtValidator';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'dtForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['dtForm'];
	});

	it('validates as valid when the input matches the Integer data type', () => {
		input.value = '42';
		const v = new WebUI.TDataTypeValidator({
			ID:               'dtValidator',
			FormID:           'dtForm',
			ControlToValidate:'dtInput',
			ErrorMessage:     '*',
			Enabled:          true,
			DataType:         'Integer',
		});
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('validates as invalid when the input does not match the Integer data type', () => {
		input.value = 'abc';
		const v = new WebUI.TDataTypeValidator({
			ID:               'dtValidator',
			FormID:           'dtForm',
			ControlToValidate:'dtInput',
			ErrorMessage:     '*',
			Enabled:          true,
			DataType:         'Integer',
		});
		v.validate();
		expect(v.isValid).toBe(false);
	});
});
