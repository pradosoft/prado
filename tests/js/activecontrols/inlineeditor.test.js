import { TInPlaceTextBox, EMPTY_ATTRIBUTE, Registry } from '../adapters/inlineeditor.js';
import { clearRegistry, clearMap, mockCallbackRequest, restoreMocks } from '../helpers/callbackMock.js';

// ─── Helpers ──────────────────────────────────────────────────────────

/** Clear the shared in-place control registry between tests. */
function clearTextboxes() {
	clearMap(TInPlaceTextBox.textboxes);
}

/** Standard options for TInPlaceTextBox construction. */
function makeOptions(overrides = {}) {
	return Object.assign(
		{
			ID:          'lbl1',
			TextBoxID:   'tb_lbl1',
			EditorID:    'tb_lbl1',
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

	it('sets isSaving when dispatch does not return false', () => {
		// dispatch() returns undefined on a dispatched request; the guard treats
		// non-false as dispatched. The field is not disabled during the save
		// (the request serializes the form lazily from the ajax queue).
		const { instance } = mockCallbackRequest();
		instance.dispatch.mockReturnValue(undefined);
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.onTextChanged('old');
		expect(ctrl.isSaving).toBe(true);
		expect(ctrl.editField.disabled).toBe(false);
	});

	it('does not dispatch a second save on re-blur while the first is in flight', () => {
		// The field is not disabled during a save, so a re-blur must be blocked
		// by the isSaving guard (mirroring the dropdown/listbox).
		const { instance, dispatchMock } = mockCallbackRequest();
		instance.dispatch.mockReturnValue(undefined);
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: true }));
		ctrl.enterEditMode(null);
		ctrl.editField.value = 'first change';
		ctrl.onTextBoxBlur({});          // save 1 dispatched, isSaving latches
		expect(ctrl.isSaving).toBe(true);
		ctrl.editField.value = 'second change';
		ctrl.onTextBoxBlur({});          // in flight -> must not dispatch again
		expect(dispatchMock).toHaveBeenCalledTimes(1);
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

// ─── EmptyDisplayText option ─────────────────────────────────────────────────────────

describe('TInPlaceTextBox EmptyDisplayText', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM('lbl1', '(none)'));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('getText reads the marked EmptyDisplayText placeholder as an empty string', () => {
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)' }));
		expect(ctrl.getText()).toBe('');
	});

	it('getText reads real text equal to EmptyDisplayText as that text', () => {
		// No marker: the server rendered a value that happens to match EmptyDisplayText.
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)' }));
		expect(ctrl.getText()).toBe('(none)');
	});

	it('starts editing with an empty editField when the label shows EmptyDisplayText', () => {
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)' }));
		ctrl.enterEditMode(null);
		expect(ctrl.editField.value).toBe('');
	});

	it('restores EmptyDisplayText in the label when blurring with an empty value', () => {
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)', AutoPostBack: false }));
		ctrl.enterEditMode(null);
		ctrl.onTextBoxBlur({});
		expect(label.innerHTML).toBe('(none)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
	});

	it('does not dispatch on blur when the empty value is unchanged (AutoPostBack)', () => {
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)', AutoPostBack: true }));
		ctrl.enterEditMode(null);
		ctrl.onTextBoxBlur({});
		expect(dispatchMock).not.toHaveBeenCalled();
		expect(label.innerHTML).toBe('(none)');
	});

	it('shows the typed value in the label after editing, clearing the marker', () => {
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)', AutoPostBack: false }));
		ctrl.enterEditMode(null);
		ctrl.editField.value = 'Typed';
		ctrl.onTextBoxBlur({});
		expect(label.innerHTML).toBe('Typed');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(false);
	});

	it('onTextChangedSuccess shows EmptyDisplayText when the saved value is empty', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: '(none)' }));
		ctrl.enterEditMode(null);
		ctrl.editField.value = '';
		ctrl.onTextChangedSuccess({}, null);
		expect(label.innerHTML).toBe('(none)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
	});

	it('EmptyDisplayText containing markup survives a browser innerHTML round trip', () => {
		// The marker, not a string compare, decides emptiness.
		label.innerHTML = 'Tom &amp; Jerry';
		label.setAttribute(EMPTY_ATTRIBUTE, '1');
		const ctrl = new TInPlaceTextBox(makeOptions({ EmptyDisplayText: 'Tom & Jerry' }));
		expect(ctrl.getText()).toBe('');
		ctrl.enterEditMode(null);
		expect(ctrl.editField.value).toBe('');
	});

	it('without EmptyDisplayText an empty value leaves the label empty (legacy behavior)', () => {
		label.innerHTML = '';
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoPostBack: false }));
		ctrl.enterEditMode(null);
		ctrl.onTextBoxBlur({});
		expect(label.innerHTML).toBe('');
	});
});

// ─── Accessibility ────────────────────────────────────────────────────────────

describe('TInPlaceTextBox accessibility', () => {
	let container, label;

	beforeEach(() => {
		clearRegistry(); clearTextboxes();
		({ container, label } = buildDOM());
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('Enter on the label enters edit mode', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const enter = vi.spyOn(ctrl, 'enterEditMode');
		ctrl.onLabelKeyDown({ keyCode: 13, preventDefault() {} });
		expect(enter).toHaveBeenCalled();
	});

	it('Space on the label enters edit mode', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const enter = vi.spyOn(ctrl, 'enterEditMode');
		ctrl.onLabelKeyDown({ keyCode: 32, preventDefault() {} });
		expect(enter).toHaveBeenCalled();
	});

	it('other keys on the label do not enter edit mode', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const enter = vi.spyOn(ctrl, 'enterEditMode');
		ctrl.onLabelKeyDown({ keyCode: 65, preventDefault() {} }); // 'a'
		expect(enter).not.toHaveBeenCalled();
	});

	it('names the editor from options.EditorLabel', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ EditorLabel: 'Favorite color' }));
		expect(ctrl.editField.getAttribute('aria-label')).toBe('Favorite color');
	});

	it('does not set an aria-label when EditorLabel is absent', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		expect(ctrl.editField.getAttribute('aria-label')).toBeNull();
	});

	it('focusLabel focuses the label when it is shown', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const focus = vi.spyOn(label, 'focus');
		ctrl.focusLabel();
		expect(focus).toHaveBeenCalled();
	});

	it('focusLabel is a no-op while the label is hidden (mid-edit)', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		label.style.display = 'none';
		const focus = vi.spyOn(label, 'focus');
		ctrl.focusLabel();
		expect(focus).not.toHaveBeenCalled();
	});

	it('maybeReturnFocus focuses the label only after a keyboard-initiated collapse', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		const focus = vi.spyOn(ctrl, 'focusLabel');
		ctrl.maybeReturnFocus();                 // no flag -> nothing
		expect(focus).not.toHaveBeenCalled();
		ctrl.returnFocusOnCollapse = true;
		ctrl.maybeReturnFocus();                 // flag -> focus, and clears
		expect(focus).toHaveBeenCalledTimes(1);
		expect(ctrl.returnFocusOnCollapse).toBe(false);
	});

	it('Escape returns focus to the label', () => {
		const ctrl = new TInPlaceTextBox(makeOptions({ AutoHide: true }));
		ctrl.enterEditMode(null);
		const focus = vi.spyOn(ctrl, 'focusLabel');
		ctrl.onKeyPressed({ keyCode: 27 });
		expect(focus).toHaveBeenCalled();
	});

	it('updateLabelEditable removes the button role when read only', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		label.setAttribute('role', 'button');
		label.setAttribute('tabindex', '0');
		ctrl.readOnly = true;
		ctrl.updateLabelEditable();
		expect(label.hasAttribute('role')).toBe(false);
		expect(label.hasAttribute('tabindex')).toBe(false);
	});

	it('updateLabelEditable restores the button role when editable again', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.readOnly = false;
		ctrl.updateLabelEditable();
		expect(label.getAttribute('role')).toBe('button');
		expect(label.getAttribute('tabindex')).toBe('0');
	});

	it('static setReadOnly keeps the label operability in sync', () => {
		const ctrl = new TInPlaceTextBox(makeOptions());
		ctrl.readOnly = false;
		ctrl.updateLabelEditable();
		TInPlaceTextBox.setReadOnly('tb_lbl1', true);
		expect(ctrl.readOnly).toBe(true);
		expect(label.hasAttribute('role')).toBe(false);
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
