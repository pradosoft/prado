/**
 * Behavioural tests for Prado.WebUI.TIpAddressValidator and the
 * Prado.Validation IP address helpers.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The IP address cases in ip-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TIpAddressValidatorTest.php, so both sides agree.
 * The server test also checks that "clientRanges" matches the Ranges option
 * the server sends.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './ip-cases.json';

const sets = Object.keys(cases.options);

describe('TIpAddressValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'ipForm';
		input = document.createElement('input');
		input.id = 'ipInput';
		const span = document.createElement('span');
		span.id = 'ipSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'ipForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.ipForm;
	});

	function makeValidator(extra = {}) {
		return new WebUI.TIpAddressValidator({
			ID:                'ipSpan',
			FormID:            'ipForm',
			ControlToValidate: 'ipInput',
			ErrorMessage:      'invalid IP address',
			Enabled:           true,
			Version:           'Any',
			AllowCidr:         false,
			RequireCidr:       false,
			Ranges:            [],
			...extra,
		});
	}

	function setOptions(set) {
		const { Ranges, ...options } = cases.options[set];
		return { ...options, Ranges: Ranges === undefined ? [] : cases.clientRanges[set] };
	}

	for (const set of sets) {
		describe(`${set} cases`, () => {
			for (const [value, expected] of cases[set]) {
				it(`${JSON.stringify(value)} is ${expected ? 'valid' : 'invalid'}`, () => {
					const v = makeValidator(setOptions(set));
					input.value = value;
					expect(v.evaluateIsValid()).toBe(expected);
				});
			}
		});
	}

	it('converts addresses to bytes as inet_pton() does', () => {
		expect(Validation.ipAddressBytes('192.0.2.1')).toEqual([192, 0, 2, 1]);
		expect(Validation.ipAddressBytes('::1')).toEqual([...new Array(15).fill(0), 1]);
		expect(Validation.ipAddressBytes('2001:db8::ff00:42:8329')).toEqual([0x20, 0x01, 0x0d, 0xb8, 0, 0, 0, 0, 0, 0, 0xff, 0x00, 0x00, 0x42, 0x83, 0x29]);
		expect(Validation.ipAddressBytes('::ffff:192.0.2.1')).toEqual([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0xff, 0xff, 192, 0, 2, 1]);
		expect(Validation.ipAddressBytes('1:2:3:4:5:6:7::')).toEqual([0, 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0, 0]);
		expect(Validation.ipAddressBytes('1.2.3')).toBeNull();
		expect(Validation.ipAddressBytes('1::2::3')).toBeNull();
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = '999.0.0.1';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = '192.0.2.1';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
