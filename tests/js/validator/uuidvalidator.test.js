/**
 * Behavioural tests for Prado.WebUI.TUuidValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The UUID cases in uuid-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TUuidValidatorTest.php, so both sides agree.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './uuid-cases.json';

const sets = {
	default: {},
	loose:   { Strict: false },
	v4v7:    { Versions: [4, 7] },
	nil:     { AllowNil: true },
};

describe('TUuidValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'uuForm';
		input = document.createElement('input');
		input.id = 'uuInput';
		const span = document.createElement('span');
		span.id = 'uuSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'uuForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.uuForm;
	});

	function makeValidator(extra = {}) {
		return new WebUI.TUuidValidator({
			ID:                'uuSpan',
			FormID:            'uuForm',
			ControlToValidate: 'uuInput',
			ErrorMessage:      'invalid UUID',
			Enabled:           true,
			Versions:          [],
			Strict:            true,
			AllowNil:          false,
			...extra,
		});
	}

	for (const [set, options] of Object.entries(sets)) {
		describe(`${set} cases`, () => {
			for (const [value, expected] of cases[set]) {
				it(`${JSON.stringify(value)} is ${expected ? 'valid' : 'invalid'}`, () => {
					const v = makeValidator(options);
					input.value = value;
					expect(v.evaluateIsValid()).toBe(expected);
				});
			}
		});
	}

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = 'not-a-uuid';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = '550e8400-e29b-41d4-a716-446655440000';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
