/**
 * Behavioural tests for Prado.WebUI.TJsonValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The JSON cases in json-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TJsonValidatorTest.php, so both sides agree.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './json-cases.json';

describe('TJsonValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'jsForm';
		input = document.createElement('textarea');
		input.id = 'jsInput';
		const span = document.createElement('span');
		span.id = 'jsSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'jsForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.jsForm;
	});

	function makeValidator() {
		return new WebUI.TJsonValidator({
			ID:                'jsSpan',
			FormID:            'jsForm',
			ControlToValidate: 'jsInput',
			ErrorMessage:      'invalid JSON',
			Enabled:           true,
			MaxDepth:          512,
		});
	}

	describe('shared cases', () => {
		for (const [value, expected] of cases.cases) {
			it(`${JSON.stringify(value)} is ${expected ? 'valid' : 'invalid'}`, () => {
				const v = makeValidator();
				input.value = value;
				expect(v.evaluateIsValid()).toBe(expected);
			});
		}
	});

	it('limits nesting to MaxDepth - 1 levels, as PHP does', () => {
		const v = makeValidator();
		const nest = (n) => '['.repeat(n) + ']'.repeat(n);
		expect(v.isValidJson(nest(511))).toBe(true);
		expect(v.isValidJson(nest(512))).toBe(false);
		expect(v.isValidJson(`${'{"a":'.repeat(511)}1${'}'.repeat(511)}`)).toBe(true);
		expect(v.isValidJson(`${'{"a":'.repeat(512)}1${'}'.repeat(512)}`)).toBe(false);
	});

	it('ignores brackets inside strings when measuring depth', () => {
		const v = makeValidator();
		expect(v.nestingDepth('["[[[", "\\"{{"]')).toBe(1);
		expect(v.nestingDepth('1')).toBe(0);
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = '{"a":1,}';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = '{"a":1}';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
