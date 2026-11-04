/**
 * Behavioural tests for Prado.WebUI.TUrlValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The URL cases in url-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TUrlValidatorTest.php, so both sides agree.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './url-cases.json';

const sets = {
	default:   {},
	noTld:     { RequireTld: false },
	noIdn:     { AllowIdn: false },
	anyScheme: { ValidSchemes: [] },
	ftpOnly:   { ValidSchemes: ['ftp'] },
};

describe('TUrlValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'uvForm';
		input = document.createElement('input');
		input.id = 'uvInput';
		input.type = 'text';
		const span = document.createElement('span');
		span.id = 'uvSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'uvForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.uvForm;
	});

	function makeValidator(extra = {}) {
		return new WebUI.TUrlValidator({
			ID:                'uvSpan',
			FormID:            'uvForm',
			ControlToValidate: 'uvInput',
			ErrorMessage:      'invalid URL',
			Enabled:           true,
			ValidSchemes:      ['http', 'https'],
			AllowIdn:          true,
			RequireTld:        true,
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

	it('enforces the 253 character domain limit', () => {
		const v = makeValidator();
		const label = 'a'.repeat(63);
		const host = `${[label, label, label, 'a'.repeat(57)].join('.')}.com`;
		expect(host.length).toBe(253);
		expect(v.isValidUrl(`http://${host}`)).toBe(true);
		expect(v.isValidUrl(`http://a${host}`)).toBe(false);
	});

	it('validates IPv6 forms', () => {
		const v = makeValidator();
		for (const ip of ['::', '::1', '1::', '1:2:3:4:5:6:7:8', '1::8', '1:2:3:4:5:6:7::', '::ffff:1.2.3.4', '64:ff9b::192.0.2.33'])
			expect(v.isIPv6(ip)).toBe(true);
		for (const ip of ['', ':', ':::', '1:2:3:4:5:6:7', '1:2:3:4:5:6:7:8::', ':1::2', '1::2::3', 'g::1', '1.2.3.4', '::1.2.3', '::ffff:256.1.1.1'])
			expect(v.isIPv6(ip)).toBe(false);
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = 'example.com';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = 'https://example.com';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
