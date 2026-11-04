/**
 * Behavioural tests for Prado.WebUI.TPasswordStrengthValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * The password cases in password-cases.json are shared with the server side
 * test, tests/unit/Web/UI/WebControls/TPasswordStrengthValidatorTest.php, so
 * both sides estimate the same entropy.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';
import cases from './password-cases.json';

const minEntropy = { VeryWeak: 0, Weak: 40, Medium: 60, Strong: 80, VeryStrong: 100 };

describe('TPasswordStrengthValidator', () => {
	let form, input;

	beforeEach(() => {
		form = document.createElement('form');
		form.id = 'psForm';
		input = document.createElement('input');
		input.id = 'psInput';
		input.type = 'password';
		const span = document.createElement('span');
		span.id = 'psSpan';
		form.appendChild(input);
		form.appendChild(span);
		document.body.appendChild(form);
		new ValidationManager({ FormID: 'psForm' });
	});

	afterEach(() => {
		document.body.removeChild(form);
		delete Validation.managers.psForm;
	});

	function makeValidator(extra = {}) {
		return new WebUI.TPasswordStrengthValidator({
			ID:                'psSpan',
			FormID:            'psForm',
			ControlToValidate: 'psInput',
			ErrorMessage:      'weak password',
			Enabled:           true,
			MinEntropy:        60,
			...extra,
		});
	}

	describe('shared cases', () => {
		for (const [password, entropy, strength] of cases.cases) {
			it(`${JSON.stringify(password)} estimates ${entropy} bits (${strength})`, () => {
				const v = makeValidator();
				expect(v.estimateEntropy(password)).toBeCloseTo(entropy, 4);
				for (const [level, bits] of Object.entries(minEntropy)) {
					input.value = password;
					v.options.MinEntropy = bits;
					const reached = minEntropy[strength] >= bits;
					expect(v.evaluateIsValid()).toBe(password === '' || reached);
					if (level === strength)
						expect(reached).toBe(true);
				}
			});
		}
	});

	it('counts a surrogate pair as one character', () => {
		const v = makeValidator();
		expect(v.estimateEntropy('😀')).toBeCloseTo(Math.log2(100), 9);
	});

	it('shares the estimate with Prado.Validation', () => {
		expect(Validation.estimatePasswordEntropy('password')).toBeCloseTo(7 * Math.log2(26) + Math.log2(7), 9);
	});

	it('runs through validate() and marks the control invalid', () => {
		const v = makeValidator();
		input.value = 'Password1';
		expect(v.validate()).toBe(false);
		expect(input.getAttribute('aria-invalid')).toBe('true');
		input.value = 'Password1!';
		expect(v.validate()).toBe(true);
		expect(input.getAttribute('aria-invalid')).toBe('false');
	});
});

describe('TPasswordStrengthMeter', () => {
	let input, meter;

	const levelTexts = ['Very weak', 'Weak', 'Medium', 'Strong', 'Very strong'];

	function build(texts = true, bits = true) {
		input = document.createElement('input');
		input.id = 'pmInput';
		input.type = 'password';
		meter = document.createElement('div');
		meter.id = 'pmMeter';
		meter.setAttribute('role', 'meter');
		meter.innerHTML = '<span class="prado-password-meter-bar" aria-hidden="true"><span class="prado-password-meter-fill"></span></span>'
			+ (texts ? '<span class="prado-password-meter-text" aria-hidden="true"></span>' : '')
			+ (bits ? '<span class="prado-password-meter-bits" aria-hidden="true"></span>' : '');
		document.body.appendChild(input);
		document.body.appendChild(meter);
		return new WebUI.TPasswordStrengthMeter({
			ID:               'pmMeter',
			ControlToMeasure: 'pmInput',
			MaxEntropy:       120,
			LevelTexts:       levelTexts,
			LevelEntropy:     [0, 40, 60, 80, 100],
			BitsFormat:       '{0} bits',
		});
	}

	afterEach(() => {
		delete global.Prado.Registry.pmMeter;
		input.remove();
		meter.remove();
	});

	function type(value) {
		input.value = value;
		input.dispatchEvent(new Event('input'));
	}

	const width = () => parseFloat(meter.querySelector('.prado-password-meter-fill').style.width);
	const hue = () => meter.querySelector('.prado-password-meter-bar').style.getPropertyValue('--prado-meter-hue');
	const text = () => meter.querySelector('.prado-password-meter-text').textContent;
	const bits = () => meter.querySelector('.prado-password-meter-bits').textContent;

	it('starts empty', () => {
		build();
		expect(width()).toBe(0);
		expect(hue()).toBe('0');
		expect(text()).toBe('');
		expect(bits()).toBe('');
		expect(meter.getAttribute('aria-valuenow')).toBe('0');
		expect(meter.getAttribute('aria-valuetext')).toBe('Very weak');
	});

	it('grows the fill and shifts the hue as the password grows', () => {
		build();
		const seen = [];
		for (const value of ['a', 'password', 'Password1', 'Password1!', 'kX9#mQ2$vL7!pZ', 'correct horse battery staple']) {
			type(value);
			seen.push({ width: width(), hue: Number(hue()), text: text() });
		}
		expect(seen.map((s) => s.text)).toEqual(['Very weak', 'Very weak', 'Weak', 'Medium', 'Strong', 'Very strong']);
		for (let i = 1; i < seen.length; i++) {
			expect(seen[i].width).toBeGreaterThan(seen[i - 1].width);
			expect(seen[i].hue).toBeGreaterThanOrEqual(seen[i - 1].hue);
		}
		// 132 bits fills the bar and turns it green.
		expect(seen[5]).toEqual({ width: 100, hue: 120, text: 'Very strong' });
	});

	it('maps the entropy to the fill, the hue, and the bits text', () => {
		build();
		type('Password1!');
		const estimate = Validation.estimatePasswordEntropy('Password1!');
		expect(width()).toBeCloseTo(estimate / 120 * 100, 2);
		expect(Number(hue())).toBe(Math.round(estimate / 120 * 120));
		expect(bits()).toBe(`${Math.round(estimate)} bits`);
		expect(meter.getAttribute('aria-valuenow')).toBe(String(Math.round(estimate)));
		expect(meter.getAttribute('aria-valuetext')).toBe('Medium');
	});

	it('caps the meter value at MaxEntropy', () => {
		build();
		type('correct horse battery staple');
		expect(meter.getAttribute('aria-valuenow')).toBe('120');
		expect(bits()).toBe('132 bits');
	});

	it('works without the level and bits texts', () => {
		build(false, false);
		type('Password1!');
		expect(width()).toBeGreaterThan(0);
		expect(meter.getAttribute('aria-valuetext')).toBe('Medium');
	});

	it('empties again when the field is cleared', () => {
		build();
		type('Password1!');
		type('');
		expect(width()).toBe(0);
		expect(text()).toBe('');
		expect(bits()).toBe('');
	});
});
