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

// ─── TCaptchaValidator — the token stays on the server ───────────────────────

describe('TCaptchaValidator.prototype.evaluateIsValid', () => {
	function evaluate(value) {
		return WebUI.TCaptchaValidator.prototype.evaluateIsValid.call({ getValidationValue: () => value });
	}

	it('requires an input', () => {
		expect(evaluate('')).toBe(false);
		expect(evaluate('   ')).toBe(false);
	});

	it('accepts any input for the server to compare', () => {
		expect(evaluate('aBcD')).toBe(true);
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

// ─── Validator ARIA wiring (aria-describedby / aria-invalid) ─────────────────
//
// A validator links its message to the validated control with aria-describedby
// and toggles aria-invalid on the control as validity changes.  When several
// validators share a control, aria-invalid only clears once the validator that
// last marked it invalid passes (mirrors the CSS lastValidator ownership).

describe('validator ARIA wiring', () => {
	let form, input, span, span2;

	beforeEach(() => {
		form  = document.createElement('form');
		form.id = 'ariaForm';

		input = document.createElement('input');
		input.id   = 'ariaInput';
		input.type = 'text';

		span  = document.createElement('span');
		span.id = 'ariaValidator';

		span2 = document.createElement('span');
		span2.id = 'ariaValidator2';

		form.appendChild(input);
		form.appendChild(span);
		form.appendChild(span2);
		document.body.appendChild(form);

		new ValidationManager({ FormID: 'ariaForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['ariaForm'];
	});

	function makeValidator(id) {
		return new WebUI.TRequiredFieldValidator({
			ID:               id,
			FormID:           'ariaForm',
			ControlToValidate:'ariaInput',
			ErrorMessage:     '*',
			Enabled:          true,
		});
	}

	it('links the message to the control via aria-describedby on construction', () => {
		makeValidator('ariaValidator');
		expect(input.getAttribute('aria-describedby')).toBe('ariaValidator');
	});

	it('appends to an existing aria-describedby without duplicating', () => {
		input.setAttribute('aria-describedby', 'existingHelp');
		makeValidator('ariaValidator');
		expect(input.getAttribute('aria-describedby')).toBe('existingHelp ariaValidator');
		// A second construction with the same message id must not duplicate.
		makeValidator('ariaValidator');
		expect(input.getAttribute('aria-describedby')).toBe('existingHelp ariaValidator');
	});

	it('sets aria-invalid=true on the control when validation fails', () => {
		const v = makeValidator('ariaValidator');
		input.value = '';
		v.validate();
		expect(input.getAttribute('aria-invalid')).toBe('true');
	});

	it('clears aria-invalid to false when validation passes', () => {
		const v = makeValidator('ariaValidator');
		input.value = '';
		v.validate();
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = 'ok';
		v.validate();
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});

	it('keeps aria-invalid=true when a non-owning validator passes while the owner fails', () => {
		// A required validator (owner of the invalid state) plus a datatype
		// validator that treats an empty value as valid.
		const required = makeValidator('ariaValidator');
		const dataType = new WebUI.TDataTypeValidator({
			ID:               'ariaValidator2',
			FormID:           'ariaForm',
			ControlToValidate:'ariaInput',
			ErrorMessage:     '*',
			Enabled:          true,
			DataType:         'Integer',
		});

		input.value = '';
		required.validate();   // fails: empty -> owner of aria-invalid
		expect(input.getAttribute('aria-invalid')).toBe('true');

		dataType.validate();   // passes: empty is a valid Integer field
		// The datatype validator does not own the invalid state, so it must not
		// clear the required validator's aria-invalid.
		expect(input.getAttribute('aria-invalid')).toBe('true');

		// Supplying a valid integer satisfies both: aria-invalid clears.
		input.value = '42';
		required.validate();
		dataType.validate();
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});

// ─── change re-validation during a mouse press ──────────────────────────────

describe('change re-validation during a mouse press', () => {
	let form, input, span, button;

	beforeEach(() => {
		vi.useFakeTimers();
		form  = document.createElement('form');
		form.id = 'pressForm';

		input = document.createElement('input');
		input.id   = 'pressInput';
		input.type = 'text';

		span  = document.createElement('span');
		span.id = 'pressValidator';

		button = document.createElement('input');
		button.type = 'submit';

		form.appendChild(input);
		form.appendChild(span);
		form.appendChild(button);
		document.body.appendChild(form);

		new ValidationManager({ FormID: 'pressForm' });
	});

	afterEach(() => {
		document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		vi.runAllTimers();
		vi.useRealTimers();
		document.body.removeChild(form);
		delete Validation.managers['pressForm'];
	});

	/**
	 * Builds a required field validator, fails it, then fixes the value.
	 */
	function failThenFix(display) {
		const v = new WebUI.TRequiredFieldValidator({
			ID:               'pressValidator',
			FormID:           'pressForm',
			ControlToValidate:'pressInput',
			ErrorMessage:     '*',
			Enabled:          true,
			Display:          display,
		});
		input.value = '';
		v.validate();
		expect(v.isValid).toBe(false);
		input.value = 'fixed';
		return v;
	}

	function press(target, buttonIndex = 0) {
		target.dispatchEvent(new MouseEvent('mousedown', { bubbles: true, button: buttonIndex }));
	}

	function change() {
		input.dispatchEvent(new Event('change', { bubbles: true }));
	}

	it('afterPress runs the callback at once without a press', () => {
		const callback = vi.fn();
		Validation.afterPress(callback);
		expect(callback).toHaveBeenCalledTimes(1);
	});

	it('re-validates at once on change without a press', () => {
		const v = failThenFix('Dynamic');
		change();
		expect(v.isValid).toBe(true);
		expect(span.style.display).toBe('none');
	});

	it('keeps a Dynamic message in place until the press ends', () => {
		const v = failThenFix('Dynamic');
		press(button);
		change();
		expect(v.isValid).toBe(false);
		expect(span.style.display).toBe('');

		button.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		// The click dispatches before the waiting re-validation runs
		expect(span.style.display).toBe('');
		vi.runAllTimers();
		expect(v.isValid).toBe(true);
		expect(span.style.display).toBe('none');
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});

	it('keeps a Fixed message visible until the press ends', () => {
		failThenFix('Fixed');
		press(button);
		change();
		expect(span.style.visibility).toBe('visible');
		document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		vi.runAllTimers();
		expect(span.style.visibility).toBe('hidden');
	});

	it('updates the validation summary after the press ends', () => {
		const summary = document.createElement('div');
		summary.id = 'pressSummary';
		form.appendChild(summary);
		new WebUI.TValidationSummary({ ID: 'pressSummary', FormID: 'pressForm', Display: 'Dynamic', ShowSummary: true });
		failThenFix('None');
		Validation.managers['pressForm'].updateSummary();
		expect(summary.style.display).toBe('');

		press(button);
		change();
		expect(summary.style.display).toBe('');
		document.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		vi.runAllTimers();
		expect(summary.style.display).toBe('none');
	});

	it('does not wait for a secondary mouse button', () => {
		const v = failThenFix('Dynamic');
		press(button, 2);
		change();
		expect(v.isValid).toBe(true);
	});

	it.each([
		['a drag end', () => document.dispatchEvent(new Event('dragend', { bubbles: true }))],
		['the window losing focus', () => window.dispatchEvent(new Event('blur'))],
	])('ends the press on %s', (_name, release) => {
		const v = failThenFix('Dynamic');
		press(button);
		change();
		expect(v.isValid).toBe(false);
		release();
		expect(Validation.pressing).toBe(false);
		vi.runAllTimers();
		expect(v.isValid).toBe(true);
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
