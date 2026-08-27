/**
 * Behavioural tests for Prado.WebUI.TFileValidator.
 *
 * Source: framework/Web/Javascripts/source/prado/validator/validation3.js
 *
 * Strategy: build a minimal jsdom form + file input + span, override the
 * input's read-only `files` property with plain File arrays, and drive
 * evaluateIsValid() with different option sets.
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

function file(name, content = 'abc', type = '') {
	return new File([content], name, { type });
}

describe('TFileValidator', () => {
	let env;

	beforeEach(() => {
		env = makeEnv('fvForm', 'fvInput', 'fvSpan');
	});

	afterEach(() => teardown(env.form, 'fvForm'));

	function makeValidator(extra = {}) {
		return new WebUI.TFileValidator({
			ID:                'fvSpan',
			FormID:            'fvForm',
			ControlToValidate: 'fvInput',
			ErrorMessage:      'invalid file',
			Enabled:           true,
			MaxFileSize:       0,
			MinFileSize:       0,
			TotalMaxFileSize:  0,
			MaxFileCount:      0,
			MinFileCount:      0,
			AllowedFileExtensions: [],
			AllowedFileTypes:      [],
			MatchAnyType:          false,
			...extra,
		});
	}

	describe('empty selection', () => {
		it('is valid with no files selected', () => {
			const v = makeValidator({ MaxFileSize: 1, AllowedFileExtensions: ['txt'] });
			setFiles(env.input, []);
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('is valid when the File API is unavailable', () => {
			const v = makeValidator({ MaxFileSize: 1 });
			Object.defineProperty(env.input, 'files', { configurable: true, value: undefined });
			expect(v.evaluateIsValid()).toBe(true);
		});
	});

	describe('file size', () => {
		it('accepts a file within MaxFileSize', () => {
			const v = makeValidator({ MaxFileSize: 100 });
			setFiles(env.input, [file('a.txt', 'short')]);
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('rejects a file over MaxFileSize and records its name', () => {
			const v = makeValidator({ MaxFileSize: 3 });
			setFiles(env.input, [file('a.txt', 'longer content')]);
			expect(v.evaluateIsValid()).toBe(false);
			expect(v.invalidFiles).toEqual(['a.txt']);
		});

		it('rejects a file under MinFileSize', () => {
			const v = makeValidator({ MinFileSize: 100 });
			setFiles(env.input, [file('a.txt', 'tiny')]);
			expect(v.evaluateIsValid()).toBe(false);
		});
	});

	describe('file extensions', () => {
		it('matches extensions case-insensitively', () => {
			const v = makeValidator({ AllowedFileExtensions: ['jpg', 'png'] });
			setFiles(env.input, [file('photo.JPG')]);
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('rejects a file with a disallowed extension', () => {
			const v = makeValidator({ AllowedFileExtensions: ['jpg', 'png'] });
			setFiles(env.input, [file('anim.gif')]);
			expect(v.evaluateIsValid()).toBe(false);
			expect(v.invalidFiles).toEqual(['anim.gif']);
		});

		it('rejects a file without an extension when extensions are restricted', () => {
			const v = makeValidator({ AllowedFileExtensions: ['txt'] });
			setFiles(env.input, [file('README')]);
			expect(v.evaluateIsValid()).toBe(false);
		});
	});

	describe('MIME types', () => {
		it('accepts an exact MIME type match', () => {
			const v = makeValidator({ AllowedFileTypes: ['text/plain'] });
			setFiles(env.input, [file('a.txt', 'abc', 'text/plain')]);
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('rejects a MIME type not in the list', () => {
			const v = makeValidator({ AllowedFileTypes: ['text/plain'] });
			setFiles(env.input, [file('a.png', 'abc', 'image/png')]);
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('matches wildcard subtypes', () => {
			const v = makeValidator({ AllowedFileTypes: ['image/*'] });
			setFiles(env.input, [file('a.png', 'abc', 'image/png')]);
			expect(v.evaluateIsValid()).toBe(true);
			setFiles(env.input, [file('a.txt', 'abc', 'text/plain')]);
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('matches every type with "*"', () => {
			const v = makeValidator({ AllowedFileTypes: ['*'] });
			setFiles(env.input, [file('a.bin', 'abc', 'application/octet-stream')]);
			expect(v.evaluateIsValid()).toBe(true);
		});

		it('requires both lists to match without MatchAnyType', () => {
			const v = makeValidator({
				AllowedFileExtensions: ['txt'],
				AllowedFileTypes:      ['text/plain'],
			});
			setFiles(env.input, [file('a.txt', 'abc', 'text/plain')]);
			expect(v.evaluateIsValid()).toBe(true);
			setFiles(env.input, [file('a.txt', 'abc', 'image/png')]);
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('accepts any list match with MatchAnyType', () => {
			const v = makeValidator({
				AllowedFileExtensions: ['txt'],
				AllowedFileTypes:      ['image/png'],
				MatchAnyType:          true,
			});
			setFiles(env.input, [file('a.txt', 'abc', 'application/octet-stream')]);
			expect(v.evaluateIsValid()).toBe(true);
			setFiles(env.input, [file('b.png', 'abc', 'image/png')]);
			expect(v.evaluateIsValid()).toBe(true);
			setFiles(env.input, [file('c.exe', 'abc', 'application/x-msdownload')]);
			expect(v.evaluateIsValid()).toBe(false);
		});
	});

	describe('total file size', () => {
		it('rejects a selection whose combined size exceeds TotalMaxFileSize', () => {
			const v = makeValidator({ TotalMaxFileSize: 8 });
			setFiles(env.input, [file('a.txt', 'aaaaa'), file('b.txt', 'bbbbb')]);
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('accepts a selection within TotalMaxFileSize', () => {
			const v = makeValidator({ TotalMaxFileSize: 12 });
			setFiles(env.input, [file('a.txt', 'aaaaa'), file('b.txt', 'bbbbb')]);
			expect(v.evaluateIsValid()).toBe(true);
		});
	});

	describe('file counts', () => {
		it('rejects more files than MaxFileCount', () => {
			const v = makeValidator({ MaxFileCount: 2 });
			setFiles(env.input, [file('a.txt'), file('b.txt'), file('c.txt')]);
			expect(v.evaluateIsValid()).toBe(false);
		});

		it('rejects fewer files than MinFileCount', () => {
			const v = makeValidator({ MinFileCount: 2 });
			setFiles(env.input, [file('a.txt')]);
			expect(v.evaluateIsValid()).toBe(false);
			setFiles(env.input, [file('a.txt'), file('b.txt')]);
			expect(v.evaluateIsValid()).toBe(true);
		});
	});

	describe('{files} error message token', () => {
		it('substitutes the invalid file names in getErrorMessage()', () => {
			const v = makeValidator({
				AllowedFileExtensions: ['txt'],
				ErrorMessage:          'Invalid files: {files}',
			});
			setFiles(env.input, [file('bad.gif'), file('worse.exe')]);
			expect(v.evaluateIsValid()).toBe(false);
			expect(v.getErrorMessage()).toBe('Invalid files: bad.gif, worse.exe');
		});

		it('updates the message element text content', () => {
			const v = makeValidator({
				AllowedFileExtensions: ['txt'],
				ErrorMessage:          'Invalid: {files}',
			});
			setFiles(env.input, [file('bad.gif')]);
			v.evaluateIsValid();
			expect(env.span.textContent).toBe('Invalid: bad.gif');
		});

		it('leaves the message element alone without the token', () => {
			env.span.textContent = 'static message';
			const v = makeValidator({ AllowedFileExtensions: ['txt'] });
			setFiles(env.input, [file('bad.gif')]);
			v.evaluateIsValid();
			expect(env.span.textContent).toBe('static message');
		});
	});

	describe('validate() integration', () => {
		it('marks the validator invalid and shows the message', () => {
			const v = makeValidator({ AllowedFileExtensions: ['txt'] });
			setFiles(env.input, [file('bad.gif')]);
			expect(v.validate()).toBe(false);
			expect(v.isValid).toBe(false);
		});

		it('passes with a valid file', () => {
			const v = makeValidator({ AllowedFileExtensions: ['txt'], MaxFileSize: 100 });
			setFiles(env.input, [file('good.txt')]);
			expect(v.validate()).toBe(true);
			expect(v.isValid).toBe(true);
		});
	});
});
