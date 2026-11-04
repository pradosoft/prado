/**
 * Behavioural tests for Prado.WebUI.TLuhnValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The Luhn cases in luhn-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TLuhnValidatorTest.php, so both sides agree.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './luhn-cases.json';

describe('TLuhnValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'lnForm';
		input = document.createElement('input');
		input.id = 'lnInput';
		const span = document.createElement('span');
		span.id = 'lnSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'lnForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.lnForm;
	});

	function makeValidator(allowSeparators = false) {
		return new WebUI.TLuhnValidator({
			ID:                'lnSpan',
			FormID:            'lnForm',
			ControlToValidate: 'lnInput',
			ErrorMessage:      'invalid number',
			Enabled:           true,
			AllowSeparators:   allowSeparators,
		});
	}

	for (const set of ['default', 'separators']) {
		describe(`${set} cases`, () => {
			for (const [value, expected] of cases[set]) {
				it(`${JSON.stringify(value)} is ${expected ? 'valid' : 'invalid'}`, () => {
					const v = makeValidator(set === 'separators');
					input.value = value;
					expect(v.evaluateIsValid()).toBe(expected);
				});
			}
		});
	}

	it('accepts exactly one check digit for a payload', () => {
		const v = makeValidator();
		const valid = [...'0123456789'].filter((d) => v.isValidLuhn(`7992739871${d}`));
		expect(valid).toEqual(['3']);
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = '79927398710';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = '79927398713';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
