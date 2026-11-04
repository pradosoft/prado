/**
 * Behavioural tests for Prado.WebUI.TIbanValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The IBAN cases in iban-cases.json are shared with the server side test,
 * tests/unit/Web/UI/WebControls/TIbanValidatorTest.php, so both sides agree.
 * The server supplies the country formats; the subset below covers the cases.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './iban-cases.json';

const formats = {
	AT: '16n', BR: '23n,1a,1c', CH: '5n,12c', DE: '18n', FR: '10n,11c,2n', GB: '4a,14n',
	LC: '4a,24c', MT: '4a,5n,18c', NL: '4a,10n', NO: '11n', RU: '14n,15c',
};

describe('TIbanValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'ibForm';
		input = document.createElement('input');
		input.id = 'ibInput';
		const span = document.createElement('span');
		span.id = 'ibSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'ibForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.ibForm;
	});

	function makeValidator(countryFormats = formats) {
		return new WebUI.TIbanValidator({
			ID:                'ibSpan',
			FormID:            'ibForm',
			ControlToValidate: 'ibInput',
			ErrorMessage:      'invalid IBAN',
			Enabled:           true,
			CountryFormats:    countryFormats,
		});
	}

	const sets = {
		default: formats,
		dach:    { DE: formats.DE, AT: formats.AT, CH: formats.CH },
	};

	for (const [set, countryFormats] of Object.entries(sets)) {
		describe(`${set} cases`, () => {
			for (const [value, expected] of cases[set]) {
				it(`${JSON.stringify(value)} is ${expected ? 'valid' : 'invalid'}`, () => {
					const v = makeValidator(countryFormats);
					input.value = value;
					expect(v.evaluateIsValid()).toBe(expected);
				});
			}
		});
	}

	it('computes the mod 97-10 remainder', () => {
		const v = makeValidator();
		expect(v.mod97('370400440532013000DE89')).toBe(1);
		expect(v.mod97('A')).toBe(10);
		expect(v.mod97('Z')).toBe(35);
	});

	it('ignores inherited object keys as country codes', () => {
		const v = makeValidator();
		expect(v.isValidIban('__89 3704 0044 0532 0130 00')).toBe(false);
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = 'DE89 3704 0044 0532 0130 01';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = 'DE89 3704 0044 0532 0130 00';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});
