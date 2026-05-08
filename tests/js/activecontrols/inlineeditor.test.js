/**
 * Tests for inlineeditor.js — Prado.WebUI.TInPlaceTextBox
 *
 * Source:
 *   framework/Web/Javascripts/source/prado/activecontrols/inlineeditor.js
 *
 * Strategy
 * --------
 * Each test sets up a minimal DOM: a label <span> (the display element) and,
 * after construction, the hidden <input> injected by createTextBox(). Network
 * calls are prevented by replacing Prado.CallbackRequest with a vi mock.
 *
 * ESM note: only tests/js/adapters/inlineeditor.js changes on ESM conversion.
 */

import { TInPlaceTextBox, Registry } from '../adapters/inlineeditor.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Remove all keys from Prado.Registry. */
function clearRegistry() {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
}

/** Clear TInPlaceTextBox.textboxes between tests. */
function clearTextboxes() {
	for (const k of Object.keys(TInPlaceTextBox.textboxes)) {
		delete TInPlaceTextBox.textboxes[k];
	}
}

/**
 * Mock Prado.CallbackRequest so that no XHR is ever made.
 * Uses a real constructor function (not an arrow) so `new` works.
 */
function mockCallbackRequest(dispatchReturnValue = true) {
	const dispatchMock              = vi.fn().mockReturnValue(dispatchReturnValue);
	const setCallbackParameterMock  = vi.fn();
	const setCausesValidationMock   = vi.fn();
	const instance = {
		dispatch:               dispatchMock,
		setCallbackParameter:   setCallbackParameterMock,
		setCausesValidation:    setCausesValidationMock,
		options:                {},
	};

	const original = global.Prado.CallbackRequest;
	const MockCtor = vi.fn(function () { return instance; });
	MockCtor.__original = original;
	global.Prado.CallbackRequest = MockCtor;

	return { instance, dispatchMock, setCallbackParameterMock, setCausesValidationMock };
}

function restoreMocks() {
	vi.restoreAllMocks();
	if (global.Prado.CallbackRequest?.__original !== undefined) {
		global.Prado.CallbackRequest = global.Prado.CallbackRequest.__original;
	}
}

/** Standard options for TInPlaceTextBox construction. */
function makeOptions(overrides = {}) {
	return Object.assign(
		{
			ID:          'lbl1',
			TextBoxID:   'tb_lbl1',
			EventTarget: 'lbl1',
			TextMode:    'SingleLine',
			ReadOnly:    false,
			AutoPostBack: false,
			AutoHide:    false,
			LoadTextOnEdit: false,
		},
		overrides,
	);
}

/** Build the minimal DOM for TInPlaceTextBox and return the label element. */
function buildDOM(labelId = 'lbl1', initialText = 'Hello') {
	const container = document.createElement('div');
	const label     = document.createElement('span');
	label.id        = labelId;
	label.innerHTML = initialText;
	container.appendChild(label);
	document.body.appendChild(container);
	return { container, label };
}

// ─── Class shape ─────────────────────────────────────────────────────────────

describe('TInPlaceTextBox class shape', () => {
	it('is a function (constructor)', () => {
		expect(typeof TInPlaceTextBox).toBe('function');
	});

	it('has static textboxes registry', () => {
		expect(typeof TInPlaceTextBox.textboxes).toBe('object');
	});

	it('has static register() method', () => {
		expect(typeof TInPlaceTextBox.register).toBe('function');
	});

	it('has static setDisplayTextBox() method', () => {
		expect(typeof TInPlaceTextBox.setDisplayTextBox).toBe('function');
	});

	it('has static setReadOnly() method', () => {
		expect(typeof TInPlaceTextBox.setReadOnly).toBe('function');
	});
});

// ─── Construction and registration ───────────────────────────────────────────

describe('TInPlaceTextBox construction', () => {
	let label, container;

	beforeEach(() => {
		clearRegistry();
		clearTextboxes();
		({ label, container } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TInPlaceTextBox(makeOptions());
		expect(Registry['lbl1']).toBeDefined();
	});

	it('registers in TInPlaceTextBox.textboxes keyed by TextBoxID', () => {
		new TInPlaceTextBox(makeOptions());
		expect(TInPlaceTextBox.textboxes['tb_lbl1']).toBeDefined();
	});

	it('sets isSaving to false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		expect(ctrl.isSaving).toBe(false);
	});

	it('sets isEditing to false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		expect(ctrl.isEditing).toBe(false);
	});

	it('sets readOnly from options.ReadOnly', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ ReadOnly: true }));
		expect(ctrl.readOnly).toBe(true);
	});

	it('creates the editField (hidden input) during construction', () => {
		new TInPlaceTextBox(makeOptions());
		const input = document.getElementById('tb_lbl1');
		expect(input).not.toBeNull();
	});

	it('editField starts hidden', () => {
		new TInPlaceTextBox(makeOptions());
		const input = document.getElementById('tb_lbl1');
		expect(input.style.display).toBe('none');
	});

	it('editField value equals the label innerHTML on construction', () => {
		new TInPlaceTextBox(makeOptions());
		const input = document.getElementById('tb_lbl1');
		expect(input.value).toBe('Hello');
	});
});

// ─── createTextBox — SingleLine vs MultiLine ──────────────────────────────────

describe('TInPlaceTextBox createTextBox', () => {
	let container;

	afterEach(() => {
		restoreMocks();
		container?.remove();
	});

	it('creates an <input> for SingleLine mode', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'SingleLine' }));
		const field = document.getElementById('tb_lbl1');
		expect(field.tagName.toLowerCase()).toBe('input');
	});

	it('creates a <textarea> for MultiLine mode', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'MultiLine' }));
		const field = document.getElementById('tb_lbl1');
		expect(field.tagName.toLowerCase()).toBe('textarea');
	});

	it('sets maxlength when MaxLength > 0 (SingleLine)', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'SingleLine', MaxLength: 20 }));
		const field = document.getElementById('tb_lbl1');
		expect(field.maxlength).toBe(20);
	});

	it('sets size when Columns > 0 (SingleLine)', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'SingleLine', Columns: 30 }));
		const field = document.getElementById('tb_lbl1');
		expect(field.size).toBe(30);
	});

	it('sets rows when Rows > 0 (MultiLine)', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'MultiLine', Rows: 5 }));
		const field = document.getElementById('tb_lbl1');
		expect(field.rows).toBe(5);
	});

	it('sets cols when Columns > 0 (MultiLine)', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		new TInPlaceTextBox(makeOptions({ TextMode: 'MultiLine', Columns: 40 }));
		const field = document.getElementById('tb_lbl1');
		expect(field.cols).toBe(40);
	});
});

// ─── getText ──────────────────────────────────────────────────────────────────

describe('TInPlaceTextBox getText', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', 'World'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('returns the current innerHTML of the label element', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		expect(ctrl.getText()).toBe('World');
	});

	it('reflects subsequent innerHTML changes', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		label.innerHTML = 'Updated';
		expect(ctrl.getText()).toBe('Updated');
	});
});

// ─── showTextBox / showLabel ──────────────────────────────────────────────────

describe('TInPlaceTextBox showTextBox and showLabel', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('showTextBox hides the label and shows the editField', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.showTextBox();
		// Check display style directly (jsdom :hidden/:visible are unreliable)
		expect(ctrl.element.style.display).toBe('none');
		expect(ctrl.editField.style.display).not.toBe('none');
	});

	it('showLabel shows the label and hides the editField', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.showTextBox();
		ctrl.showLabel();
		expect(ctrl.element.style.display).not.toBe('none');
		expect(ctrl.editField.style.display).toBe('none');
	});
});

// ─── enterEditMode ────────────────────────────────────────────────────────────

describe('TInPlaceTextBox enterEditMode', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('sets isEditing to true', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.enterEditMode(null);
		expect(ctrl.isEditing).toBe(true);
	});

	it('shows the textbox (label hidden, input visible)', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.enterEditMode(null);
		expect(global.jQuery('#lbl1').is(':hidden')).toBe(true);
	});

	it('is a no-op when readOnly is true', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ ReadOnly: true }));
		ctrl.enterEditMode(null);
		expect(ctrl.isEditing).toBe(false);
	});

	it('is a no-op when already editing', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isEditing = true;
		const showTextBox = vi.spyOn(ctrl, 'showTextBox');
		ctrl.enterEditMode(null);
		expect(showTextBox).not.toHaveBeenCalled();
	});

	it('is a no-op when isSaving is true', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isSaving = true;
		const showTextBox = vi.spyOn(ctrl, 'showTextBox');
		ctrl.enterEditMode(null);
		expect(showTextBox).not.toHaveBeenCalled();
	});

	it('calls event.preventDefault when an event is passed', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const evt = { preventDefault: vi.fn() };
		ctrl.enterEditMode(evt);
		expect(evt.preventDefault).toHaveBeenCalled();
	});

	it('calls onEnterEditMode callback when options.onEnterEditMode is set', () => {
		const onEnterEditMode = vi.fn();
		const ctrl = new TInPlaceTextBox(makeOptions({ onEnterEditMode }));
		ctrl.enterEditMode(null);
		expect(onEnterEditMode).toHaveBeenCalled();
	});
});

// ─── exitEditMode ─────────────────────────────────────────────────────────────

describe('TInPlaceTextBox exitEditMode', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', 'Original'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('sets isEditing and isSaving to false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isEditing = true;
		ctrl.isSaving  = true;
		ctrl.exitEditMode(null);
		expect(ctrl.isEditing).toBe(false);
		expect(ctrl.isSaving).toBe(false);
	});

	it('copies editField.value to the label innerHTML', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.editField.value = 'Edited text';
		ctrl.exitEditMode(null);
		expect(label.innerHTML).toBe('Edited text');
	});

	it('calls showLabel', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.exitEditMode(null);
		expect(showLabel).toHaveBeenCalled();
	});
});

// ─── onTextBoxBlur ────────────────────────────────────────────────────────────

describe('TInPlaceTextBox onTextBoxBlur', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', 'Start'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('copies editField.value to label when AutoPostBack is false and no change', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: false }));
		ctrl.editField.value = 'Start'; // same as innerHTML
		ctrl.onTextBoxBlur({});
		expect(label.innerHTML).toBe('Start');
	});

	it('calls showLabel when AutoHide is true and AutoPostBack is false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: false, AutoHide: true }));
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.editField.value = 'Start';
		ctrl.onTextBoxBlur({});
		expect(showLabel).toHaveBeenCalled();
	});

	it('calls onTextChanged when AutoPostBack is true and value changed', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.isEditing = true;
		ctrl.editField.value = 'New value'; // different from innerHTML 'Start'
		// mockImplementation prevents the real onTextChanged from firing a
		// CallbackRequest (which fails without a network/form in jsdom).
		const onTextChanged = vi.spyOn(ctrl, 'onTextChanged').mockImplementation(() => {});
		ctrl.onTextBoxBlur({});
		expect(onTextChanged).toHaveBeenCalled();
	});

	it('does NOT call onTextChanged when AutoPostBack is false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: false }));
		ctrl.isEditing = true;
		ctrl.editField.value = 'Different';
		// mockImplementation prevents the real onTextChanged from firing a
		// CallbackRequest (which fails without a network/form in jsdom).
		const onTextChanged = vi.spyOn(ctrl, 'onTextChanged').mockImplementation(() => {});
		ctrl.onTextBoxBlur({});
		expect(onTextChanged).not.toHaveBeenCalled();
	});
});

// ─── onKeyPressed ─────────────────────────────────────────────────────────────

describe('TInPlaceTextBox onKeyPressed', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('resets editField value and clears isEditing on ESC (keyCode 27) when AutoHide', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoHide: true }));
		ctrl.isEditing = true;
		ctrl.element.innerHTML = 'Original';
		ctrl.editField.value   = 'Changed';
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.onKeyPressed({ keyCode: 27 });
		expect(ctrl.editField.value).toBe('Original');
		expect(ctrl.isEditing).toBe(false);
		expect(showLabel).toHaveBeenCalled();
	});

	it('calls preventDefault on ENTER (keyCode 13) in SingleLine mode', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ TextMode: 'SingleLine' }));
		const evt = { keyCode: 13, preventDefault: vi.fn() };
		ctrl.onKeyPressed(evt);
		expect(evt.preventDefault).toHaveBeenCalled();
	});

	it('does NOT call preventDefault on ENTER in MultiLine mode', () => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM('lbl1'));
		// Re-build container for MultiLine (avoid double-ID)
		const ctrl = new TInPlaceTextBox(makeOptions({ TextMode: 'MultiLine' }));
		const evt = { keyCode: 13, preventDefault: vi.fn() };
		ctrl.onKeyPressed(evt);
		expect(evt.preventDefault).not.toHaveBeenCalled();
	});
});

// ─── onTextChanged ────────────────────────────────────────────────────────────

describe('TInPlaceTextBox onTextChanged', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('dispatches a CallbackRequest with the original text as parameter', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.onTextChanged('original text');
		expect(setCallbackParameterMock).toHaveBeenCalledWith('original text');
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('sets isSaving to true and disables editField when dispatch returns true', () => {
		mockCallbackRequest(true);
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.onTextChanged('old');
		expect(ctrl.isSaving).toBe(true);
		expect(ctrl.editField.disabled).toBe(true);
	});

	it('does NOT set isSaving when dispatch returns false', () => {
		mockCallbackRequest(false);
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.onTextChanged('old');
		expect(ctrl.isSaving).toBe(false);
	});
});

// ─── onTextChangedSuccess ─────────────────────────────────────────────────────

describe('TInPlaceTextBox onTextChangedSuccess', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', 'Before'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('sets isSaving and isEditing to false', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isSaving  = true;
		ctrl.isEditing = true;
		ctrl.onTextChangedSuccess({}, 'After');
		expect(ctrl.isSaving).toBe(false);
		expect(ctrl.isEditing).toBe(false);
	});

	it('uses callback parameter as new label text when provided', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.onTextChangedSuccess({}, 'New label text');
		// ctrl.element is the label span (same DOM node); use ctrl.element
		expect(ctrl.element.innerHTML).toBe('New label text');
	});

	it('falls back to editField.value when parameter is null', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.editField.value = 'From field';
		ctrl.onTextChangedSuccess({}, null);
		expect(ctrl.element.innerHTML).toBe('From field');
	});

	it('re-enables editField', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.editField.disabled = true;
		ctrl.onTextChangedSuccess({}, 'x');
		expect(ctrl.editField.disabled).toBe(false);
	});

	it('calls options.onSuccess callback when defined', () => {
		const onSuccess = vi.fn();
		const ctrl = new TInPlaceTextBox(makeOptions({ onSuccess }));
		ctrl.onTextChangedSuccess({}, 'x');
		expect(onSuccess).toHaveBeenCalled();
	});

	it('calls showLabel when AutoHide is true', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoHide: true }));
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.onTextChangedSuccess({}, 'x');
		expect(showLabel).toHaveBeenCalled();
	});
});

// ─── onTextChangedFailure ─────────────────────────────────────────────────────

describe('TInPlaceTextBox onTextChangedFailure', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('resets isSaving, isEditing and re-enables editField', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isSaving          = true;
		ctrl.isEditing         = true;
		ctrl.editField.disabled = true;
		ctrl.onTextChangedFailure({}, 'err');
		expect(ctrl.isSaving).toBe(false);
		expect(ctrl.isEditing).toBe(false);
		expect(ctrl.editField.disabled).toBe(false);
	});

	it('calls options.onFailure callback when defined', () => {
		const onFailure = vi.fn();
		const ctrl = new TInPlaceTextBox(makeOptions({ onFailure }));
		ctrl.onTextChangedFailure({}, 'err');
		expect(onFailure).toHaveBeenCalled();
	});
});

// ─── loadExternalText ─────────────────────────────────────────────────────────

describe('TInPlaceTextBox loadExternalText', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM('lbl1', 'CurrentText'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('disables editField while loading', () => {
		const { instance } = mockCallbackRequest();
		const ctrl = new TInPlaceTextBox(makeOptions({ LoadTextOnEdit: true }));
		ctrl.loadExternalText();
		expect(ctrl.editField.disabled).toBe(true);
	});

	it('dispatches a CallbackRequest with the current text', () => {
		const { dispatchMock, setCallbackParameterMock, instance } = mockCallbackRequest();
		const ctrl = new TInPlaceTextBox(makeOptions({ LoadTextOnEdit: true }));
		ctrl.loadExternalText();
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('sets setCausesValidation to false', () => {
		const { setCausesValidationMock } = mockCallbackRequest();
		const ctrl = new TInPlaceTextBox(makeOptions({ LoadTextOnEdit: true }));
		ctrl.loadExternalText();
		expect(setCausesValidationMock).toHaveBeenCalledWith(false);
	});
});

// ─── onloadExternalTextSuccess / onloadExternalTextFailure ────────────────────

describe('TInPlaceTextBox external text success/failure handlers', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', 'Label'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('onloadExternalTextSuccess re-enables editing', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.editField.disabled = true;
		ctrl.onloadExternalTextSuccess({}, 'new text');
		expect(ctrl.isEditing).toBe(true);
		expect(ctrl.editField.disabled).toBe(false);
	});

	it('onloadExternalTextSuccess calls options.onSuccess when defined', () => {
		const onSuccess = vi.fn();
		const ctrl = new TInPlaceTextBox(makeOptions({ onSuccess }));
		ctrl.onloadExternalTextSuccess({}, 'x');
		expect(onSuccess).toHaveBeenCalled();
	});

	it('onloadExternalTextFailure resets isSaving and isEditing and shows label', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.isSaving  = true;
		ctrl.isEditing = true;
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.onloadExternalTextFailure({}, 'err');
		expect(ctrl.isSaving).toBe(false);
		expect(ctrl.isEditing).toBe(false);
		expect(showLabel).toHaveBeenCalled();
	});

	it('onloadExternalTextFailure calls options.onFailure when defined', () => {
		const onFailure = vi.fn();
		const ctrl = new TInPlaceTextBox(makeOptions({ onFailure }));
		ctrl.onloadExternalTextFailure({}, 'err');
		expect(onFailure).toHaveBeenCalled();
	});
});

// ─── Static helpers ───────────────────────────────────────────────────────────

describe('TInPlaceTextBox static helpers', () => {
	let container;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('setDisplayTextBox(id, true) calls enterEditMode on the registered instance', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const enter = vi.spyOn(ctrl, 'enterEditMode');
		TInPlaceTextBox.setDisplayTextBox('tb_lbl1', true);
		expect(enter).toHaveBeenCalledWith(null);
	});

	it('setDisplayTextBox(id, false) calls exitEditMode on the registered instance', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const exit = vi.spyOn(ctrl, 'exitEditMode');
		TInPlaceTextBox.setDisplayTextBox('tb_lbl1', false);
		expect(exit).toHaveBeenCalledWith(null);
	});

	it('setDisplayTextBox is a no-op for unknown IDs', () => {
		expect(() => TInPlaceTextBox.setDisplayTextBox('unknown_id', true)).not.toThrow();
	});

	it('setReadOnly updates readOnly on the registered instance', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ ReadOnly: false }));
		TInPlaceTextBox.setReadOnly('tb_lbl1', true);
		expect(ctrl.readOnly).toBe(true);
	});

	it('setReadOnly is a no-op for unknown IDs', () => {
		expect(() => TInPlaceTextBox.setReadOnly('unknown_id', true)).not.toThrow();
	});
});

// ─── ExternalControl option ───────────────────────────────────────────────────

describe('TInPlaceTextBox ExternalControl', () => {
	let container, extBtn;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container } = buildDOM());
		extBtn = document.createElement('button');
		extBtn.id = 'editBtn';
		document.body.appendChild(extBtn);
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
		extBtn.remove();
	});

	it('observes click on the external control element', () => {
		const observe = vi.spyOn(global.Prado.WebUI.Control.prototype, 'observe');
		new TInPlaceTextBox(makeOptions({ ExternalControl: 'editBtn' }));
		const extCall = observe.mock.calls.find(
			(c) => c[0] === extBtn && c[1] === 'click',
		);
		expect(extCall).toBeDefined();
	});
});
