/**
 * Behavioural tests for Prado.WebUI.TUnicodeValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The validator checks on the server only; the client side always succeeds,
 * so a value the server rejected is posted again for the server to decide.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';

describe('TUnicodeValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'unForm';
		input = document.createElement('input');
		input.id = 'unInput';
		const span = document.createElement('span');
		span.id = 'unSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'unForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.unForm;
	});

	function makeValidator(extra = {}) {
		return new WebUI.TUnicodeValidator({
			ID: 'unSpan', FormID: 'unForm', ControlToValidate: 'unInput', ErrorMessage: 'suspicious', Enabled: true, ...extra,
		});
	}

	it('takes over the aria-invalid that the server rendered for its failure', () => {
		input.setAttribute('aria-invalid', 'true');
		const v = makeValidator({ IsValid: false });
		expect(input.lastAriaValidator).toBe('unSpan');
		input.value = 'paypal';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});

	it('leaves a server aria-invalid alone when it did not fail', () => {
		input.setAttribute('aria-invalid', 'true');
		const v = makeValidator();
		expect(input.lastAriaValidator).toBeUndefined();
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('true');
	});

	it('always passes on the client', () => {
		const v = new WebUI.TUnicodeValidator({
			ID: 'unSpan', FormID: 'unForm', ControlToValidate: 'unInput', ErrorMessage: 'suspicious', Enabled: true,
		});
		for (const value of ['', 'admin', 'pаypal', 'abc​def']) {
			input.value = value;
			expect(v.evaluateIsValid()).toBe(true);
			expect(v.validate()).toBe(true);
		}
	});
});
