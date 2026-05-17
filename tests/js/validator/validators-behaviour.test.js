/**
 * Behavioural tests for the validator classes that had zero coverage:
 *   TCompareValidator, TRangeValidator, TRegularExpressionValidator,
 *   TListControlValidator, TCustomValidator, TValidationSummary,
 *   and the Prado.ValidationManager orchestration layer.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * Strategy: build a minimal jsdom form+input+span for each test group.
 * Pure-logic methods (compare, evaluateIsValid) are tested directly on the
 * prototype where no DOM is needed.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';

// ─── Shared DOM helpers ───────────────────────────────────────────────────────

function makeEnv(formId, inputId, spanId, inputType = 'text') {
	const form  = document.createElement('form');
	form.id = formId;

	const input = document.createElement('input');
	input.id   = inputId;
	input.type = inputType;

	const span  = document.createElement('span');
	span.id = spanId;

	form.appendChild(input);
	form.appendChild(span);
	document.body.appendChild(form);
	new ValidationManager({ FormID: formId });

	return { form, input, span };
}

function teardown(form, formId) {
	document.body.removeChild(form);
	delete Validation.managers[formId];
}

// ─── TCompareValidator.compare — pure logic ───────────────────────────────────

describe('TCompareValidator.compare — pure logic', () => {
	/**
	 * Build a minimal "this" context for compare() without needing a real DOM.
	 */
	function cmp(dataType, operator, op1, op2) {
		return WebUI.TCompareValidator.prototype.compare.call(
			{
				options: { DataType: dataType, Operator: operator },
				convert: WebUI.TBaseValidator.prototype.convert,
			},
			op1,
			op2,
		);
	}

	describe('Integer / Equal', () => {
		it('returns true when both values are equal integers', () => {
			expect(cmp('Integer', 'Equal', '42', '42')).toBe(true);
		});

		it('returns false when integers are not equal', () => {
			expect(cmp('Integer', 'Equal', '5', '10')).toBe(false);
		});

		it('returns false when operand1 does not convert to Integer', () => {
			expect(cmp('Integer', 'Equal', 'abc', '42')).toBe(false);
		});

		it('returns true when operand2 does not convert to Integer (second operand null → true)', () => {
			expect(cmp('Integer', 'Equal', '42', 'abc')).toBe(true);
		});
	});

	describe('Integer operators', () => {
		it('NotEqual: returns true when values differ', () => {
			expect(cmp('Integer', 'NotEqual', '3', '5')).toBe(true);
		});

		it('NotEqual: returns false when values are equal', () => {
			expect(cmp('Integer', 'NotEqual', '5', '5')).toBe(false);
		});

		it('GreaterThan: returns true when op1 > op2', () => {
			expect(cmp('Integer', 'GreaterThan', '10', '5')).toBe(true);
		});

		it('GreaterThan: returns false when op1 <= op2', () => {
			expect(cmp('Integer', 'GreaterThan', '5', '10')).toBe(false);
		});

		it('GreaterThanEqual: returns true when op1 == op2', () => {
			expect(cmp('Integer', 'GreaterThanEqual', '5', '5')).toBe(true);
		});

		it('LessThan: returns true when op1 < op2', () => {
			expect(cmp('Integer', 'LessThan', '3', '10')).toBe(true);
		});

		it('LessThanEqual: returns true when op1 == op2', () => {
			expect(cmp('Integer', 'LessThanEqual', '7', '7')).toBe(true);
		});
	});

	describe('String / Equal', () => {
		it('compares strings lexically for Equal', () => {
			expect(cmp('String', 'Equal', 'hello', 'hello')).toBe(true);
			expect(cmp('String', 'Equal', 'abc', 'xyz')).toBe(false);
		});
	});
});

// ─── TCompareValidator.evaluateIsValid — DOM integration ─────────────────────

describe('TCompareValidator.evaluateIsValid — DOM', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('cvForm', 'cvInput', 'cvSpan');
	});

	afterEach(() => teardown(env.form, 'cvForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TCompareValidator({
			ID:               'cvSpan',
			FormID:           'cvForm',
			ControlToValidate:'cvInput',
			ErrorMessage:     '*',
			Enabled:          true,
			DataType:         'Integer',
			Operator:         'Equal',
			...extra,
		});
	}

	it('returns true when value is empty (not required)', () => {
		env.input.value = '';
		const v = makeValidator({ ValueToCompare: '10' });
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns true when Integer value equals ValueToCompare', () => {
		env.input.value = '42';
		const v = makeValidator({ ValueToCompare: '42' });
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns false when Integer value does not equal ValueToCompare', () => {
		env.input.value = '7';
		const v = makeValidator({ ValueToCompare: '42' });
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('returns true when GreaterThan and value > comparand', () => {
		env.input.value = '10';
		const v = makeValidator({ Operator: 'GreaterThan', ValueToCompare: '5' });
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns false when GreaterThan and value <= comparand', () => {
		env.input.value = '5';
		const v = makeValidator({ Operator: 'GreaterThan', ValueToCompare: '5' });
		v.validate();
		expect(v.isValid).toBe(false);
	});
});

// ─── TRangeValidator.evaluateIsValid ─────────────────────────────────────────

describe('TRangeValidator.evaluateIsValid', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('rvForm', 'rvInput', 'rvSpan');
	});

	afterEach(() => teardown(env.form, 'rvForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TRangeValidator({
			ID:               'rvSpan',
			FormID:           'rvForm',
			ControlToValidate:'rvInput',
			ErrorMessage:     '*',
			Enabled:          true,
			DataType:         'Integer',
			MinValue:         '1',
			MaxValue:         '10',
			...extra,
		});
	}

	it('returns true when value is empty', () => {
		env.input.value = '';
		makeValidator().validate();
		expect(Validation.managers['rvForm'].isValid()).toBe(true);
	});

	it('returns true when Integer value is within [min, max]', () => {
		env.input.value = '5';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns true when Integer value equals MinValue', () => {
		env.input.value = '1';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns true when Integer value equals MaxValue', () => {
		env.input.value = '10';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns false when Integer value is below MinValue', () => {
		env.input.value = '0';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('returns false when Integer value is above MaxValue', () => {
		env.input.value = '11';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('returns false when value cannot be converted to Integer', () => {
		env.input.value = 'abc';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('uses StrictComparison: fails when value equals MinValue strictly', () => {
		env.input.value = '1';
		const v = makeValidator({ StrictComparison: true });
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('validates StringLength type: passes when length is in [min, max]', () => {
		env.input.value = 'hello'; // length 5
		const v = makeValidator({ DataType: 'StringLength', MinValue: '3', MaxValue: '10' });
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('validates StringLength type: fails when length is below min', () => {
		env.input.value = 'hi'; // length 2
		const v = makeValidator({ DataType: 'StringLength', MinValue: '3', MaxValue: '10' });
		v.validate();
		expect(v.isValid).toBe(false);
	});
});

// ─── TRegularExpressionValidator.evaluateIsValid ──────────────────────────────

describe('TRegularExpressionValidator.evaluateIsValid', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('reForm', 'reInput', 'reSpan');
	});

	afterEach(() => teardown(env.form, 'reForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TRegularExpressionValidator({
			ID:                   'reSpan',
			FormID:               'reForm',
			ControlToValidate:    'reInput',
			ErrorMessage:         '*',
			Enabled:              true,
			ValidationExpression: '\\d+',
			PatternModifiers:     '',
			...extra,
		});
	}

	it('returns true when value is empty', () => {
		env.input.value = '';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns true when value matches the pattern', () => {
		env.input.value = '12345';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns false when value does not match the pattern', () => {
		env.input.value = 'abc';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('returns false for partial match (pattern is anchored)', () => {
		// Pattern \d+ matches the digit portion but not the whole string.
		env.input.value = '123abc';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('respects PatternModifiers (case-insensitive)', () => {
		env.input.value = 'HELLO';
		const v = makeValidator({ ValidationExpression: '[a-z]+', PatternModifiers: 'i' });
		v.validate();
		expect(v.isValid).toBe(true);
	});
});

// ─── TListControlValidator.evaluateIsValid ───────────────────────────────────

describe('TListControlValidator.evaluateIsValid', () => {
	let form, select, span;

	beforeEach(() => {
		form   = document.createElement('form');
		form.id = 'lcForm';
		select = document.createElement('select');
		select.id = 'lcSelect';
		select.setAttribute('multiple', '');
		['apple', 'banana', 'cherry'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			opt.text  = v;
			select.appendChild(opt);
		});
		span = document.createElement('span');
		span.id = 'lcSpan';
		form.appendChild(select);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'lcForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['lcForm'];
	});

	function makeValidator(extra = {}) {
		return new WebUI.TListControlValidator({
			ID:               'lcSpan',
			FormID:           'lcForm',
			ControlToValidate:'lcSelect',
			// ControlType 'TListBox' tells getListElements() to look for a
			// <select> element and return its options collection.
			ControlType:      'TListBox',
			ErrorMessage:     '*',
			Enabled:          true,
			Min:              1,
			Max:              3,
			...extra,
		});
	}

	it('returns true when at least Min items are selected', () => {
		select.options[0].selected = true;
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns false when fewer than Min items are selected', () => {
		// Nothing selected.
		const v = makeValidator({ Min: 1 });
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('returns false when more than Max items are selected', () => {
		select.options[0].selected = true;
		select.options[1].selected = true;
		select.options[2].selected = true;
		const v = makeValidator({ Min: 1, Max: 2 });
		v.validate();
		expect(v.isValid).toBe(false);
	});

	it('validates required values: returns true when required value is selected', () => {
		select.options[1].selected = true; // banana
		const v = makeValidator({ Required: 'banana', Min: 1 });
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('validates required values: returns false when required value is not selected', () => {
		select.options[0].selected = true; // apple — not banana
		const v = makeValidator({ Required: 'banana', Min: 1 });
		v.validate();
		expect(v.isValid).toBe(false);
	});
});

// ─── TCustomValidator.evaluateIsValid ────────────────────────────────────────

describe('TCustomValidator.evaluateIsValid', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('cuForm', 'cuInput', 'cuSpan');
	});

	afterEach(() => teardown(env.form, 'cuForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TCustomValidator({
			ID:               'cuSpan',
			FormID:           'cuForm',
			ControlToValidate:'cuInput',
			ErrorMessage:     '*',
			Enabled:          true,
			...extra,
		});
	}

	it('returns true when no ClientValidationFunction is set', () => {
		env.input.value = 'anything';
		const v = makeValidator();
		v.validate();
		expect(v.isValid).toBe(true);
	});

	it('returns true when ClientValidationFunction returns true', () => {
		global.__customValidate = function (_sender, value) { return value === 'good'; };
		env.input.value = 'good';
		const v = makeValidator({ ClientValidationFunction: '__customValidate' });
		v.validate();
		expect(v.isValid).toBe(true);
		delete global.__customValidate;
	});

	it('returns false when ClientValidationFunction returns false', () => {
		global.__customValidate = function () { return false; };
		env.input.value = 'anything';
		const v = makeValidator({ ClientValidationFunction: '__customValidate' });
		v.validate();
		expect(v.isValid).toBe(false);
		delete global.__customValidate;
	});
});

// ─── TValidationSummary ───────────────────────────────────────────────────────

describe('TValidationSummary', () => {
	let form, input, span, summary;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'vsForm';
		input   = document.createElement('input');
		input.id   = 'vsInput';
		input.type = 'text';
		span    = document.createElement('span');
		span.id = 'vsSpan';
		summary = document.createElement('div');
		summary.id = 'vsSummary';

		form.appendChild(input);
		form.appendChild(span);
		form.appendChild(summary);
		document.body.appendChild(form);

		new ValidationManager({ FormID: 'vsForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['vsForm'];
	});

	it('registers in Prado.Registry on construction', () => {
		new WebUI.TValidationSummary({ ID: 'vsSummary', FormID: 'vsForm', ShowSummary: true });
		expect(global.Prado.Registry['vsSummary']).toBeDefined();
	});

	it('hides the summary when there are no errors', () => {
		const s = new WebUI.TValidationSummary({ ID: 'vsSummary', FormID: 'vsForm', ShowSummary: true });
		s.updateSummary([], true);
		expect(summary.style.visibility).toBe('hidden');
	});

	it('shows the summary when there are errors', () => {
		const s = new WebUI.TValidationSummary({ ID: 'vsSummary', FormID: 'vsForm', ShowSummary: true });
		const fakeValidators = [{ getErrorMessage: () => 'Field required', requestDispatched: false }];
		s.updateSummary(fakeValidators, true);
		expect(summary.style.visibility).toBe('visible');
	});

	describe('formatSummary', () => {
		let s;

		beforeEach(() => {
			s = new WebUI.TValidationSummary({ ID: 'vsSummary', FormID: 'vsForm', ShowSummary: true, DisplayMode: 'BulletList' });
		});

		it('formats messages as a bullet list by default', () => {
			const html = s.formatSummary(['Error A', 'Error B']);
			expect(html).toContain('<ul>');
			expect(html).toContain('<li>Error A</li>');
			expect(html).toContain('<li>Error B</li>');
			expect(html).toContain('</ul>');
		});

		it('formats messages as a simple list', () => {
			s.options.DisplayMode = 'SimpleList';
			const html = s.formatSummary(['Err1']);
			expect(html).toContain('Err1');
			expect(html).toContain('<br />');
		});

		it('formats messages as a single paragraph', () => {
			s.options.DisplayMode = 'SingleParagraph';
			const html = s.formatSummary(['Err1', 'Err2']);
			expect(html).toContain('Err1');
			expect(html).toContain('Err2');
			expect(html).toContain('<br />');
		});

		it('prepends HeaderText when set', () => {
			s.options.HeaderText = 'Please fix:';
			const html = s.formatSummary(['Err1']);
			expect(html.startsWith('Please fix:')).toBe(true);
		});
	});

	describe('formatMessageBox', () => {
		let s;

		beforeEach(() => {
			s = new WebUI.TValidationSummary({ ID: 'vsSummary', FormID: 'vsForm', ShowSummary: true, DisplayMode: 'BulletList' });
		});

		it('formats messages with dash bullets by default (BulletList mode)', () => {
			const text = s.formatMessageBox(['Err1', 'Err2']);
			expect(text).toContain('  - Err1');
			expect(text).toContain('  - Err2');
		});

		it('formats messages line-by-line for List mode', () => {
			s.options.DisplayMode = 'List';
			const text = s.formatMessageBox(['Err1', 'Err2']);
			expect(text).toContain('Err1\n');
			expect(text).toContain('Err2\n');
		});

		it('returns only HeaderText for HeaderOnly mode', () => {
			s.options.DisplayMode = 'HeaderOnly';
			s.options.HeaderText  = 'Summary';
			const text = s.formatMessageBox(['Err1']);
			expect(text).toBe('Summary');
		});
	});
});

// ─── Prado.ValidationManager orchestration ───────────────────────────────────

describe('Prado.ValidationManager.validate', () => {
	let form, input, span;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'vmForm';
		input   = document.createElement('input');
		input.id   = 'vmInput';
		input.type = 'text';
		span    = document.createElement('span');
		span.id = 'vmSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'vmForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['vmForm'];
	});

	it('returns true when all validators pass', () => {
		input.value = 'hello';
		new WebUI.TRequiredFieldValidator({
			ID: 'vmSpan', FormID: 'vmForm', ControlToValidate: 'vmInput',
			ErrorMessage: '*', Enabled: true,
		});
		const result = Validation.validate('vmForm');
		expect(result).toBe(true);
	});

	it('returns false when a validator fails', () => {
		input.value = '';
		new WebUI.TRequiredFieldValidator({
			ID: 'vmSpan', FormID: 'vmForm', ControlToValidate: 'vmInput',
			ErrorMessage: '*', Enabled: true,
		});
		const result = Validation.validate('vmForm');
		expect(result).toBe(false);
	});

	it('throws when the form is not registered', () => {
		expect(() => Validation.validate('nonExistentForm')).toThrow();
	});
});

describe('Prado.ValidationManager.reset', () => {
	let form, input, span;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'resetForm';
		input   = document.createElement('input');
		input.id   = 'resetInput';
		input.type = 'text';
		span    = document.createElement('span');
		span.id = 'resetSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'resetForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['resetForm'];
	});

	it('sets isValid back to true after a failed validation', () => {
		input.value = '';
		const v = new WebUI.TRequiredFieldValidator({
			ID: 'resetSpan', FormID: 'resetForm', ControlToValidate: 'resetInput',
			ErrorMessage: '*', Enabled: true,
		});
		Validation.validate('resetForm');
		expect(v.isValid).toBe(false);

		Validation.managers['resetForm'].reset();
		expect(v.isValid).toBe(true);
	});
});

describe('Prado.ValidationManager.validatorPartition', () => {
	let form, inputA, inputB, spanA, spanB;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'partForm';
		inputA  = document.createElement('input');
		inputA.id = 'partInputA'; inputA.type = 'text';
		inputB  = document.createElement('input');
		inputB.id = 'partInputB'; inputB.type = 'text';
		spanA   = document.createElement('span'); spanA.id = 'partSpanA';
		spanB   = document.createElement('span'); spanB.id = 'partSpanB';
		[inputA, inputB, spanA, spanB].forEach(el => form.appendChild(el));
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'partForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['partForm'];
	});

	it('separates validators into in-group and out-of-group arrays', () => {
		const vA = new WebUI.TRequiredFieldValidator({
			ID: 'partSpanA', FormID: 'partForm', ControlToValidate: 'partInputA',
			ErrorMessage: '*', Enabled: true, ValidationGroup: 'GroupA',
		});
		const vB = new WebUI.TRequiredFieldValidator({
			ID: 'partSpanB', FormID: 'partForm', ControlToValidate: 'partInputB',
			ErrorMessage: '*', Enabled: true,
		});

		const mgr = Validation.managers['partForm'];
		const [inGroup, outGroup] = mgr.validatorPartition('GroupA');

		expect(inGroup).toContain(vA);
		expect(outGroup).toContain(vB);
	});

	it('returns validators without a group when group arg is omitted', () => {
		const vA = new WebUI.TRequiredFieldValidator({
			ID: 'partSpanA', FormID: 'partForm', ControlToValidate: 'partInputA',
			ErrorMessage: '*', Enabled: true, ValidationGroup: 'GroupA',
		});
		const vB = new WebUI.TRequiredFieldValidator({
			ID: 'partSpanB', FormID: 'partForm', ControlToValidate: 'partInputB',
			ErrorMessage: '*', Enabled: true,
		});

		const mgr = Validation.managers['partForm'];
		const [noGroup] = mgr.validatorPartition(null);

		expect(noGroup).toContain(vB);
		expect(noGroup).not.toContain(vA);
	});
});

describe('Prado.ValidationManager.isValid', () => {
	let form, input, span;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'ivForm';
		input   = document.createElement('input');
		input.id = 'ivInput'; input.type = 'text';
		span    = document.createElement('span'); span.id = 'ivSpan';
		form.appendChild(input); form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'ivForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['ivForm'];
	});

	it('returns true before any validation has run (all start valid)', () => {
		new WebUI.TRequiredFieldValidator({
			ID: 'ivSpan', FormID: 'ivForm', ControlToValidate: 'ivInput',
			ErrorMessage: '*', Enabled: true,
		});
		expect(Validation.managers['ivForm'].isValid()).toBe(true);
	});

	it('returns false after a failed validate()', () => {
		input.value = '';
		new WebUI.TRequiredFieldValidator({
			ID: 'ivSpan', FormID: 'ivForm', ControlToValidate: 'ivInput',
			ErrorMessage: '*', Enabled: true,
		});
		Validation.validate('ivForm');
		expect(Validation.managers['ivForm'].isValid()).toBe(false);
	});
});

describe('Prado.ValidationManager.getValidatorsWithError', () => {
	let form, input, span;

	beforeEach(() => {
		form    = document.createElement('form');
		form.id = 'gveForm';
		input   = document.createElement('input');
		input.id = 'gveInput'; input.type = 'text';
		span    = document.createElement('span'); span.id = 'gveSpan';
		form.appendChild(input); form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'gveForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers['gveForm'];
	});

	it('returns an empty array when all validators pass', () => {
		input.value = 'filled';
		new WebUI.TRequiredFieldValidator({
			ID: 'gveSpan', FormID: 'gveForm', ControlToValidate: 'gveInput',
			ErrorMessage: '*', Enabled: true,
		});
		Validation.validate('gveForm');
		expect(Validation.managers['gveForm'].getValidatorsWithError()).toHaveLength(0);
	});

	it('returns the failing validator when validation fails', () => {
		input.value = '';
		const v = new WebUI.TRequiredFieldValidator({
			ID: 'gveSpan', FormID: 'gveForm', ControlToValidate: 'gveInput',
			ErrorMessage: '*', Enabled: true,
		});
		Validation.validate('gveForm');
		expect(Validation.managers['gveForm'].getValidatorsWithError()).toContain(v);
	});
});
