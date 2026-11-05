/**
 * Behavioural tests for Prado.WebUI.TImageValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * Strategy: jsdom cannot decode images, so the asynchronous dimension reads
 * never complete here. The dimension logic is driven by seeding the
 * validator's `_imageInfo` cache with decoded entries and stubbing
 * `canReadImages()`.
 */

import { Validation, ValidationManager, WebUI } from '../adapters/validator.js';

// ─── Shared DOM helpers ───────────────────────────────────────────────────────

function makeEnv(formId, inputId, spanId) {
	const form  = document.createElement('form');
	form.id = formId;

	const input = document.createElement('input');
	input.id   = inputId;
	input.type = 'file';

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

function setFiles(input, files) {
	Object.defineProperty(input, 'files', { configurable: true, value: files });
}

function file(name, content = 'abc', type = 'image/png') {
	return new File([content], name, { type });
}

describe('TImageValidator', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('ivForm', 'ivInput', 'ivSpan');
	});

	afterEach(() => teardown(env.form, 'ivForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TImageValidator({
			ID:                'ivSpan',
			FormID:            'ivForm',
			ControlToValidate: 'ivInput',
			ErrorMessage:      'invalid image',
			Enabled:           true,
			MaxFileSize:       0,
			MinFileSize:       0,
			TotalMaxFileSize:  0,
			MaxFileCount:      0,
			MinFileCount:      0,
			AllowedFileExtensions: [],
			AllowedFileTypes:      [],
			MatchAnyType:          false,
			MinImageWidth:     0,
			MaxImageWidth:     0,
			MinImageHeight:    0,
			MaxImageHeight:    0,
			...extra,
		});
	}

	/**
	 * Seed the dimension cache with a decoded entry for the file and force
	 * canReadImages() so the dimension checks run under jsdom.
	 */
	function seedInfo(v, f, info) {
		v.canReadImages = () => true;
		v.readImageInfo = () => {};
		v._imageInfo[v.fileKey(f)] = { pending: false, notImage: false, width: 0, height: 0, ...info };
	}

	it('extends TFileValidator', () => {
		expect(makeValidator() instanceof WebUI.TFileValidator).toBe(true);
	});

	describe('dimension checks with decoded entries', () => {
		it('accepts an image within the bounds', () => {
			const v = makeValidator({ MinImageWidth: 10, MaxImageWidth: 200, MinImageHeight: 10, MaxImageHeight: 200 });
			const f = file('a.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 100, height: 100 });
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('rejects an image over MaxImageWidth', () => {
			const v = makeValidator({ MaxImageWidth: 100 });
			const f = file('wide.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 150, height: 50 });
			expect(v.evaluateIsValid()).toBe(false);
			expect(v.invalidFiles).toEqual(['wide.png']);
		});

		it('rejects an image under MinImageHeight', () => {
			const v = makeValidator({ MinImageHeight: 100 });
			const f = file('short.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 150, height: 50 });
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('rejects a file that failed to decode as an image', () => {
			const v = makeValidator();
			const f = file('fake.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { notImage: true });
			expect(v.evaluateIsValid()).toBe(false);
		});
	});

	describe('undecoded files', () => {
		it('passes a file whose decode is still pending', () => {
			const v = makeValidator({ MaxImageWidth: 1 });
			const f = file('a.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { pending: true });
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('passes a file with no cache entry and requests its decode', () => {
			const v = makeValidator({ MaxImageWidth: 1 });
			const f = file('a.png');
			setFiles(env.input, [f]);
			v.canReadImages = () => true;
			const requested = [];
			v.readImageInfo = (fileArg) => requested.push(fileArg.name);
			expect(v.evaluateIsValid()).toBe(true);
			expect(requested).toEqual(['a.png']);
		});

		it('skips the dimension checks when images cannot be read', () => {
			const v = makeValidator({ MaxImageWidth: 1 });
			v.canReadImages = () => false;
			setFiles(env.input, [file('a.png')]);
			expect(v.evaluateIsValid()).toBe(true);
		});
	});

	describe('inherited TFileValidator checks', () => {
		it('rejects a file over MaxFileSize before the dimension checks', () => {
			const v = makeValidator({ MaxFileSize: 2 });
			const f = file('a.png', 'longer content');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 10, height: 10 });
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('rejects a disallowed extension before the dimension checks', () => {
			const v = makeValidator({ AllowedFileExtensions: ['png'] });
			const f = file('a.gif', 'abc', 'image/gif');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 10, height: 10 });
			expect(v.evaluateIsValid()).toBe(false);
		});
	});

	describe('selection changes', () => {
		it('drops the stale dimension cache when the selection changes', () => {
			const v = makeValidator();
			const f = file('a.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { width: 10, height: 10 });
			v.canReadImages = () => false;
			env.input.dispatchEvent(new Event('change'));
			expect(v._imageInfo).toEqual({});
		});
	});

	describe('revalidate()', () => {
		it('re-validates after a decode completes once results are displayed', () => {
			const v = makeValidator({ MaxImageWidth: 100 });
			const f = file('wide.png');
			setFiles(env.input, [f]);
			seedInfo(v, f, { pending: true });
			expect(v.validate()).toBe(true);
			// the decode completes: the cache entry resolves to an oversized image
			v._imageInfo[v.fileKey(f)] = { pending: false, notImage: false, width: 150, height: 50 };
			v.revalidate();
			expect(v.isValid).toBe(false);
		});
	});
});
