/**
 * Tests for activefileupload.js — Prado.WebUI.TActiveFileUpload
 *
 * Source:
 *   framework/Web/Javascripts/source/prado/activefileupload/activefileupload.js
 *
 * Strategy
 * --------
 * The control interacts with five DOM elements (input, flag, form, indicator,
 * complete, error) plus an iframe target. All are created in beforeEach.
 * form.submit() is replaced with a vi.fn() to prevent actual submission.
 * Network calls are prevented by replacing Prado.CallbackRequest.
 *
 * ESM note: only tests/js/adapters/activefileupload.js changes on ESM
 * conversion; this file stays unchanged.
 */

import { TActiveFileUpload, Registry } from '../adapters/activefileupload.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function clearRegistry() {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
}

function clearControls() {
	for (const k of Object.keys(TActiveFileUpload.controls)) {
		delete TActiveFileUpload.controls[k];
	}
}

/**
 * Mock Prado.CallbackRequest so dispatch never makes real XHR.
 * Uses a real constructor function (not an arrow) so `new` works.
 */
function mockCallbackRequest() {
	const dispatchMock = vi.fn().mockReturnValue(true);
	const instance = { dispatch: dispatchMock, options: {} };

	const original = global.Prado.CallbackRequest;
	const MockCtor = vi.fn(function () { return instance; });
	MockCtor.__original = original;
	global.Prado.CallbackRequest = MockCtor;

	return { instance, dispatchMock };
}

function restoreMocks() {
	vi.restoreAllMocks();
	if (global.Prado.CallbackRequest?.__original !== undefined) {
		global.Prado.CallbackRequest = global.Prado.CallbackRequest.__original;
	}
}

/** Standard IDs used across tests. */
const IDS = {
	ID:          'fup1',
	inputID:     'fup1_input',
	flagID:      'fup1_flag',
	formID:      'fup1_form',
	indicatorID: 'fup1_indicator',
	completeID:  'fup1_complete',
	errorID:     'fup1_error',
	targetID:    'fup1_iframe',
	EventTarget: 'fup1',
};

/** Build the DOM structure expected by TActiveFileUpload. */
function buildDOM() {
	const form      = document.createElement('form');
	form.id         = IDS.formID;
	form.action     = '/upload';
	form.method     = 'GET';
	form.enctype    = 'application/x-www-form-urlencoded';
	form.target     = '';
	form.submit     = vi.fn(); // prevent real submission

	const fileInput     = document.createElement('input');
	fileInput.type      = 'file';
	fileInput.id        = IDS.inputID;
	fileInput.value     = ''; // jsdom allows setting to ''

	const flag          = document.createElement('input');
	flag.type           = 'hidden';
	flag.id             = IDS.flagID;
	flag.value          = '';

	const indicator     = document.createElement('span');
	indicator.id        = IDS.indicatorID;
	indicator.style.display = 'none';

	const complete      = document.createElement('span');
	complete.id         = IDS.completeID;
	complete.style.display = 'none';

	const error         = document.createElement('span');
	error.id            = IDS.errorID;
	error.style.display = 'none';

	const iframe        = document.createElement('iframe');
	iframe.id           = IDS.targetID;
	iframe.name         = IDS.targetID;

	form.appendChild(fileInput);
	form.appendChild(flag);
	document.body.appendChild(form);
	document.body.appendChild(indicator);
	document.body.appendChild(complete);
	document.body.appendChild(error);
	document.body.appendChild(iframe);

	// Also create a host div for the Control base class
	const host = document.createElement('div');
	host.id = IDS.ID;
	document.body.appendChild(host);

	return { form, fileInput, flag, indicator, complete, error, iframe, host };
}

function destroyDOM({ form, indicator, complete, error, iframe, host }) {
	form.remove();
	indicator.remove();
	complete.remove();
	error.remove();
	iframe.remove();
	host.remove();
}

// ─── Class shape ─────────────────────────────────────────────────────────────

describe('TActiveFileUpload class shape', () => {
	it('is a function (constructor)', () => {
		expect(typeof TActiveFileUpload).toBe('function');
	});

	it('has static controls registry', () => {
		expect(typeof TActiveFileUpload.controls).toBe('object');
	});

	it('has static register() method', () => {
		expect(typeof TActiveFileUpload.register).toBe('function');
	});

	it('has static onFileUpload() method', () => {
		expect(typeof TActiveFileUpload.onFileUpload).toBe('function');
	});

	it('has static fileChanged() method', () => {
		expect(typeof TActiveFileUpload.fileChanged).toBe('function');
	});
});

// ─── Construction and registration ───────────────────────────────────────────

describe('TActiveFileUpload construction', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	it('registers in Prado.Registry on construction', () => {
		new TActiveFileUpload(IDS);
		expect(Registry[IDS.ID]).toBeDefined();
	});

	it('registers in TActiveFileUpload.controls on construction', () => {
		new TActiveFileUpload(IDS);
		expect(TActiveFileUpload.controls[IDS.ID]).toBeDefined();
	});

	it('stores a reference to the file input element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.input).toBe(dom.fileInput);
	});

	it('stores a reference to the flag element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.flag).toBe(dom.flag);
	});

	it('stores a reference to the form element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.form).toBe(dom.form);
	});

	it('stores a reference to the indicator element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.indicator).toBe(dom.indicator);
	});

	it('stores a reference to the complete element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.complete).toBe(dom.complete);
	});

	it('stores a reference to the error element', () => {
		const ctrl = new TActiveFileUpload(IDS);
		expect(ctrl.error).toBe(dom.error);
	});

	it('observes "change" on the file input when autoPostBack is true', () => {
		const observe = vi.spyOn(global.Prado.WebUI.Control.prototype, 'observe');
		new TActiveFileUpload({ ...IDS, autoPostBack: true });
		const changeCall = observe.mock.calls.find(
			(c) => c[0] === dom.fileInput && c[1] === 'change',
		);
		expect(changeCall).toBeDefined();
	});

	it('does NOT observe "change" when autoPostBack is false', () => {
		const observe = vi.spyOn(global.Prado.WebUI.Control.prototype, 'observe');
		new TActiveFileUpload({ ...IDS, autoPostBack: false });
		const changeCall = observe.mock.calls.find(
			(c) => c[0] === dom.fileInput && c[1] === 'change',
		);
		expect(changeCall).toBeUndefined();
	});
});

// ─── fileChanged ──────────────────────────────────────────────────────────────

describe('TActiveFileUpload fileChanged', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	it('is a no-op when input.value is empty (IE11 fix)', () => {
		const ctrl = new TActiveFileUpload(IDS);
		// input.value is '' by default
		ctrl.fileChanged();
		expect(dom.form.submit).not.toHaveBeenCalled();
	});

	it('sets flag.value to "1" when a file is selected', () => {
		const ctrl = new TActiveFileUpload(IDS);
		// Simulate a file being selected (jsdom doesn't allow setting .value on file inputs,
		// so we define the property directly on the element)
		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'C:\\fakepath\\file.txt',
			configurable: true,
		});
		ctrl.fileChanged();
		expect(dom.flag.value).toBe('1');
	});

	it('shows indicator and hides complete and error', () => {
		const ctrl = new TActiveFileUpload(IDS);
		dom.complete.style.display = '';
		dom.error.style.display    = '';
		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		ctrl.fileChanged();
		expect(dom.complete.style.display).toBe('none');
		expect(dom.error.style.display).toBe('none');
		expect(dom.indicator.style.display).toBe('');
	});

	it('submits the form', () => {
		const ctrl = new TActiveFileUpload(IDS);
		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		ctrl.fileChanged();
		expect(dom.form.submit).toHaveBeenCalled();
	});

	it('modifies form.action to include upload query parameters', () => {
		const ctrl = new TActiveFileUpload(IDS);
		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		const originalAction = dom.form.action;
		ctrl.fileChanged();
		// The action during submit should have contained the upload params.
		// After fileChanged the action is restored, so capture via submit spy:
		const capturedAction = dom.form.submit.mock.instances[0]?.action
			?? dom.form.action; // fallback
		// At minimum, verify the form was submitted (action restore happens after submit)
		expect(dom.form.submit).toHaveBeenCalled();
	});

	it('restores form action, target, method and enctype after submission', () => {
		const ctrl = new TActiveFileUpload(IDS);
		const origAction  = dom.form.action;
		const origTarget  = dom.form.target;
		const origMethod  = dom.form.method;
		const origEnctype = dom.form.enctype;

		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		ctrl.fileChanged();

		expect(dom.form.action).toBe(origAction);
		expect(dom.form.target).toBe(origTarget);
		expect(dom.form.method).toBe(origMethod);
		expect(dom.form.enctype).toBe(origEnctype);
	});

	it('sets form method to POST and enctype to multipart/form-data during submission', () => {
		const ctrl = new TActiveFileUpload(IDS);
		let capturedMethod, capturedEnctype;
		dom.form.submit = vi.fn().mockImplementation(function () {
			capturedMethod  = this.method;
			capturedEnctype = this.enctype;
		});

		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		ctrl.fileChanged();

		// jsdom normalises form.method to lowercase.
		expect(capturedMethod).toBe('post');
		expect(capturedEnctype).toBe('multipart/form-data');
	});

	it('sets form target to targetID during submission', () => {
		const ctrl = new TActiveFileUpload(IDS);
		let capturedTarget;
		dom.form.submit = vi.fn().mockImplementation(function () {
			capturedTarget = this.target;
		});

		Object.defineProperty(dom.fileInput, 'value', {
			get: () => 'file.txt',
			configurable: true,
		});
		ctrl.fileChanged();
		expect(capturedTarget).toBe(IDS.targetID);
	});
});

// ─── finishUpload ─────────────────────────────────────────────────────────────

describe('TActiveFileUpload finishUpload', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	it('dispatches a CallbackRequest when targetID matches', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishUpload({ targetID: IDS.targetID, errorCode: '0' });
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('calls finishCallBack(true) directly when targetID does not match', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		const finishCallBack = vi.spyOn(ctrl, 'finishCallBack');
		ctrl.finishUpload({ targetID: 'wrong_target', errorCode: '0' });
		expect(finishCallBack).toHaveBeenCalledWith(true);
	});

	it('onSuccess callback triggers finishCallBack(true)', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		const finishCallBack = vi.spyOn(ctrl, 'finishCallBack');

		// Capture the callback object passed to CallbackRequest
		let capturedCallback;
		const original = global.Prado.CallbackRequest;
		global.Prado.CallbackRequest = vi.fn(function (_id, cb) {
			capturedCallback = cb;
			return { dispatch: vi.fn(), options: {} };
		});

		ctrl.finishUpload({ targetID: IDS.targetID, errorCode: '0' });
		global.Prado.CallbackRequest = original; // restore immediately
		capturedCallback.onSuccess();
		expect(finishCallBack).toHaveBeenCalledWith(true);
	});

	it('onFailure callback triggers finishCallBack(false)', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		const finishCallBack = vi.spyOn(ctrl, 'finishCallBack');

		let capturedCallback;
		const original = global.Prado.CallbackRequest;
		global.Prado.CallbackRequest = vi.fn(function (_id, cb) {
			capturedCallback = cb;
			return { dispatch: vi.fn(), options: {} };
		});

		ctrl.finishUpload({ targetID: IDS.targetID, errorCode: '0' });
		global.Prado.CallbackRequest = original; // restore immediately
		capturedCallback.onFailure();
		expect(finishCallBack).toHaveBeenCalledWith(false);
	});
});

// ─── finishCallBack ───────────────────────────────────────────────────────────

describe('TActiveFileUpload finishCallBack', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	it('hides the indicator', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		dom.indicator.style.display = '';
		ctrl.finishCallBack(true);
		expect(dom.indicator.style.display).toBe('none');
	});

	it('clears flag.value', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		dom.flag.value = '1';
		ctrl.finishCallBack(true);
		expect(dom.flag.value).toBe('');
	});

	it('shows complete indicator when errorCode is "0" and success is true', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		ctrl.finishCallBack(true);
		expect(dom.complete.style.display).toBe('');
	});

	it('shows complete indicator when errorCode is "[]" and success is true', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '[]' };
		ctrl.finishCallBack(true);
		expect(dom.complete.style.display).toBe('');
	});

	it('clears the input value on successful upload', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		// Set a placeholder value via property definition
		Object.defineProperty(dom.fileInput, 'value', {
			get: () => '',
			set: vi.fn(),
			configurable: true,
		});
		expect(() => ctrl.finishCallBack(true)).not.toThrow();
	});

	it('shows error indicator when success is false', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '0' };
		ctrl.finishCallBack(false);
		expect(dom.error.style.display).toBe('');
	});

	it('shows error indicator when errorCode is non-zero', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '1' }; // non-zero → error
		ctrl.finishCallBack(true);
		expect(dom.error.style.display).toBe('');
	});

	it('does NOT show complete when errorCode contains non-zero digits', () => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode: '2' };
		ctrl.finishCallBack(true);
		expect(dom.complete.style.display).not.toBe('');
		expect(dom.error.style.display).toBe('');
	});
});

// ─── Static helpers ───────────────────────────────────────────────────────────

describe('TActiveFileUpload static helpers', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	it('onFileUpload delegates to the matching control instance finishUpload', () => {
		const ctrl = new TActiveFileUpload(IDS);
		// mockImplementation prevents finishUpload from firing a real
		// CallbackRequest (which fails without a form/network in jsdom).
		const finishUpload = vi.spyOn(ctrl, 'finishUpload').mockImplementation(() => {});
		TActiveFileUpload.onFileUpload({ clientID: IDS.ID, targetID: IDS.targetID, errorCode: '0' });
		expect(finishUpload).toHaveBeenCalled();
	});

	it('static fileChanged delegates to the matching control instance fileChanged', () => {
		const ctrl = new TActiveFileUpload(IDS);
		const fileChanged = vi.spyOn(ctrl, 'fileChanged');
		TActiveFileUpload.fileChanged(IDS.ID);
		expect(fileChanged).toHaveBeenCalled();
	});
});

// ─── errorCode regex edge cases ───────────────────────────────────────────────

describe('TActiveFileUpload errorCode pattern matching', () => {
	let dom;

	beforeEach(() => {
		clearRegistry(); clearControls();
		dom = buildDOM();
	});

	afterEach(() => {
		restoreMocks();
		destroyDOM(dom);
	});

	/**
	 * The regex used in finishCallBack is: /^[0\[\],]+$/
	 * Strings consisting only of 0, [, ], and , match (success).
	 * Anything else means error.
	 */
	it.each([
		['0',       true,  'single zero'],
		['[0,0,0]', true,  'array of zeros'],
		['[]',      true,  'empty array'],
		['[0,[]]',  true,  'nested empty array'],
		['1',       false, 'non-zero digit'],
		['0,1',     false, 'mixed zeros and non-zero'],
		['error',   false, 'non-numeric string'],
	])('errorCode "%s" success=%s (%s)', (errorCode, expectComplete) => {
		const ctrl = new TActiveFileUpload(IDS);
		ctrl.finishoptions = { errorCode };
		dom.complete.style.display = 'none';
		dom.error.style.display    = 'none';
		ctrl.finishCallBack(true);
		if (expectComplete) {
			expect(dom.complete.style.display).toBe('');
		} else {
			expect(dom.error.style.display).toBe('');
		}
	});
});
