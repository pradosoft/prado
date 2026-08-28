import {
	TInPlaceControlBase,
	TInPlaceTextBox,
	TInPlaceDropDownList,
	EMPTY_ATTRIBUTE,
	Registry,
} from '../adapters/inlineeditor.js';
import { clearRegistry, clearMap, mockCallbackRequest, restoreMocks } from '../helpers/callbackMock.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

/** Clear the shared in-place control registry between tests. */
function clearDropDowns() {
	clearMap(TInPlaceDropDownList.dropdowns);
}

/** The registry is shared; the TextMode tests below build text boxes. */
const clearTextboxes = clearDropDowns;

/** Standard options for TInPlaceDropDownList construction. */
function makeOptions(overrides = {}) {
	return Object.assign(
		{
			ID:           'ddl1__label',
			EditorID:     'ddl1',
			EventTarget:  'ddl1',
			ReadOnly:     false,
			AutoPostBack: true,
			AutoHide:     true,
			EmptyDisplayText: '',
		},
		overrides,
	);
}

/**
 * Build the DOM TInPlaceDropDownList expects: a label span followed by a
 * hidden select with options, as the server renders them.
 */
function buildDOM(items = ['Alpha', 'Beta', 'Gamma'], selectedIndex = 0) {
	const container = document.createElement('div');
	const label     = document.createElement('span');
	label.id        = 'ddl1__label';
	label.innerHTML = items[selectedIndex] ?? '';
	container.appendChild(label);

	const select = document.createElement('select');
	select.id    = 'ddl1';
	select.name  = 'ddl1';
	select.style.display = 'none';
	for (const [i, text] of items.entries()) {
		const option = document.createElement('option');
		option.value    = String(i);
		option.text     = text;
		option.selected = i === selectedIndex;
		select.appendChild(option);
	}
	container.appendChild(select);
	document.body.appendChild(container);
	return { container, label, select };
}

// ─── Class hierarchy ─────────────────────────────────────────────────────────

describe('TInPlaceControlBase hierarchy', () => {
	it('TInPlaceControlBase is a function (constructor)', () => {
		expect(typeof TInPlaceControlBase).toBe('function');
	});

	it('TInPlaceTextBox extends TInPlaceControlBase', () => {
		expect(Object.getPrototypeOf(TInPlaceTextBox.prototype)).toBe(TInPlaceControlBase.prototype);
	});

	it('TInPlaceDropDownList extends TInPlaceControlBase', () => {
		expect(Object.getPrototypeOf(TInPlaceDropDownList.prototype)).toBe(TInPlaceControlBase.prototype);
	});

	it('TInPlaceDropDownList has static dropdowns registry and helpers', () => {
		expect(typeof TInPlaceDropDownList.dropdowns).toBe('object');
		expect(typeof TInPlaceDropDownList.register).toBe('function');
		expect(typeof TInPlaceDropDownList.setDisplayEditor).toBe('function');
		expect(typeof TInPlaceDropDownList.setReadOnly).toBe('function');
	});
});

// ─── Construction ────────────────────────────────────────────────────────────

describe('TInPlaceDropDownList construction', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('registers in Prado.Registry on construction', () => {
		new TInPlaceDropDownList(makeOptions());
		expect(Registry['ddl1__label']).toBeDefined();
	});

	it('registers in TInPlaceDropDownList.dropdowns keyed by EditorID', () => {
		new TInPlaceDropDownList(makeOptions());
		expect(TInPlaceDropDownList.dropdowns['ddl1']).toBeDefined();
	});

	it('attaches to the server-rendered select as editField', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		expect(ctrl.editField).toBe(select);
	});

	it('does not create a new element', () => {
		new TInPlaceDropDownList(makeOptions());
		expect(document.querySelectorAll('select').length).toBe(1);
	});

	it('sets readOnly from options.ReadOnly', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ ReadOnly: true }));
		expect(ctrl.readOnly).toBe(true);
	});
});

// ─── enterEditMode / exitEditMode ────────────────────────────────────────────

describe('TInPlaceDropDownList edit mode', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('enterEditMode shows the select and hides the label', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		expect(ctrl.isEditing).toBe(true);
		expect(label.style.display).toBe('none');
		expect(select.style.display).not.toBe('none');
	});

	it('enterEditMode records the original selection for revert', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		expect(ctrl.originalSelection).toBe('0');
	});

	it('enterEditMode is a no-op when readOnly', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ ReadOnly: true }));
		ctrl.enterEditMode(null);
		expect(ctrl.isEditing).toBe(false);
	});

	it('exitEditMode copies the selected option text to the label', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.exitEditMode(null);
		expect(label.innerHTML).toBe('Beta');
		expect(label.style.display).not.toBe('none');
		expect(select.style.display).toBe('none');
	});
});

// ─── getEditorDisplayText ────────────────────────────────────────────────────

describe('TInPlaceDropDownList getEditorDisplayText', () => {
	let container, select;

	afterEach(() => {
		restoreMocks();
		container?.remove();
	});

	it('returns the selected option text', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM(['Alpha', 'Beta'], 1));
		const ctrl = new TInPlaceDropDownList(makeOptions());
		expect(ctrl.getEditorDisplayText()).toBe('Beta');
	});

	it('html-encodes the option text', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM(['a < b & c'], 0));
		const ctrl = new TInPlaceDropDownList(makeOptions());
		expect(ctrl.getEditorDisplayText()).toBe('a &lt; b &amp; c');
	});

	it('reports an empty editor when the selected option text is empty', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM([''], 0));
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		expect(ctrl.isEditorEmpty()).toBe(true);
	});

	it('reports an empty editor when the select has no options', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM([], -1));
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		expect(ctrl.isEditorEmpty()).toBe(true);
	});

	it('refreshLabel shows EmptyDisplayText and marks the label when empty', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM([''], 0));
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		ctrl.refreshLabel();
		expect(ctrl.element.innerHTML).toBe('(none)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
		expect(ctrl.getText()).toBe('');
	});

	it('refreshLabel shows the selected text and clears the mark', () => {
		clearRegistry(); clearDropDowns();
		({ container, select } = buildDOM(['Alpha', 'Beta'], 1));
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		ctrl.refreshLabel();
		expect(ctrl.element.innerHTML).toBe('Beta');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(false);
	});
});

// ─── onSelectionChanged / onValueChanged ─────────────────────────────────────

describe('TInPlaceDropDownList selection change', () => {
	let container, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('dispatches a CallbackRequest with the original value as parameter', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(setCallbackParameterMock).toHaveBeenCalledWith('0');
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('sets isSaving while dispatching (the select is not disabled)', () => {
		mockCallbackRequest(true);
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(ctrl.isSaving).toBe(true);
		// Not disabled: a queued request serializes the form after this returns.
		expect(select.disabled).toBe(false);
	});

	it('sets isSaving when dispatch returns undefined (the real success value)', () => {
		// Prado.CallbackRequest.dispatch() returns undefined on a dispatched
		// request and false only on validation failure; the guard must treat
		// undefined as dispatched.
		const { instance } = mockCallbackRequest();
		instance.dispatch.mockReturnValue(undefined);
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(ctrl.isSaving).toBe(true);
		expect(select.disabled).toBe(false);
	});

	it('flags focus-return to the label on a change commit', () => {
		mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(ctrl.returnFocusOnCollapse).toBe(true);
	});

	it('does not dispatch when AutoPostBack is false', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions({ AutoPostBack: false }));
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('does not dispatch when not in edit mode', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.onSelectionChanged({});
		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('does not dispatch again while saving', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		ctrl.isSaving = true;
		ctrl.onSelectionChanged({});
		expect(dispatchMock).not.toHaveBeenCalled();
	});
});

// ─── onDropDownBlur ──────────────────────────────────────────────────────────

describe('TInPlaceDropDownList onDropDownBlur', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('is a no-op while saving', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		ctrl.isSaving = true;
		ctrl.onDropDownBlur({});
		expect(ctrl.isEditing).toBe(true);
	});

	it('exits edit mode without a callback when the value is unchanged', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		ctrl.onDropDownBlur({});
		expect(dispatchMock).not.toHaveBeenCalled();
		expect(ctrl.isEditing).toBe(false);
		expect(label.style.display).not.toBe('none');
	});

	it('does not dispatch on blur; the change event already posted the value', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.onSelectionChanged({});
		expect(dispatchMock).toHaveBeenCalledTimes(1);
		// Saving is in flight, so the following blur is a no-op.
		ctrl.onDropDownBlur({});
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('shows the selection in the label when the change dispatch did not start', () => {
		mockCallbackRequest(false);
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.onSelectionChanged({});
		ctrl.onDropDownBlur({});
		expect(ctrl.isEditing).toBe(false);
		expect(label.innerHTML).toBe('Beta');
	});

	it('applies the selection to the label when AutoPostBack is false', () => {
		mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions({ AutoPostBack: false }));
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.onDropDownBlur({});
		expect(label.innerHTML).toBe('Beta');
		expect(ctrl.isEditing).toBe(false);
	});
});

// ─── onKeyPressed ────────────────────────────────────────────────────────────

describe('TInPlaceDropDownList onKeyPressed', () => {
	let container, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('reverts the value and exits edit mode on ESC', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.onKeyPressed({ keyCode: 27 });
		expect(select.value).toBe('0');
		expect(ctrl.isEditing).toBe(false);
		expect(showLabel).toHaveBeenCalled();
	});

	it('blurs the select on ENTER', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		const blur = vi.spyOn(select, 'blur');
		const evt = { keyCode: 13, preventDefault: vi.fn() };
		ctrl.onKeyPressed(evt);
		expect(evt.preventDefault).toHaveBeenCalled();
		expect(blur).toHaveBeenCalled();
	});
});

// ─── onValueChanged success/failure ──────────────────────────────────────────

describe('TInPlaceDropDownList value changed handlers', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('onValueChangedSuccess updates the label with the selected text', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.isSaving = true;
		ctrl.onValueChangedSuccess({}, null);
		expect(label.innerHTML).toBe('Beta');
		expect(ctrl.isSaving).toBe(false);
		expect(ctrl.isEditing).toBe(false);
		expect(select.disabled).toBe(false);
	});

	it('onValueChangedSuccess uses the callback parameter as label html when provided', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		ctrl.onValueChangedSuccess({}, '<b>Server</b>');
		expect(label.innerHTML).toBe('<b>Server</b>');
	});

	it('onValueChangedSuccess resets originalSelection to the current selection', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 2;
		ctrl.onValueChangedSuccess({}, null);
		expect(ctrl.originalSelection).toBe('2');
	});

	it('onValueChangedSuccess hides the select when AutoHide is true', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ AutoHide: true }));
		ctrl.enterEditMode(null);
		ctrl.onValueChangedSuccess({}, null);
		expect(select.style.display).toBe('none');
	});

	it('onValueChangedSuccess calls options.onSuccess when defined', () => {
		const onSuccess = vi.fn();
		const ctrl = new TInPlaceDropDownList(makeOptions({ onSuccess }));
		ctrl.onValueChangedSuccess({}, null);
		expect(onSuccess).toHaveBeenCalled();
	});

	it('onValueChangedFailure re-enables the select and resets state', () => {
		const onFailure = vi.fn();
		const ctrl = new TInPlaceDropDownList(makeOptions({ onFailure }));
		ctrl.enterEditMode(null);
		ctrl.isSaving = true;
		select.disabled = true;
		ctrl.onValueChangedFailure({}, 'err');
		expect(select.disabled).toBe(false);
		expect(ctrl.isSaving).toBe(false);
		expect(ctrl.isEditing).toBe(false);
		expect(onFailure).toHaveBeenCalled();
	});
});

// ─── loadItems ───────────────────────────────────────────────────────────────

describe('TInPlaceDropDownList loadItems', () => {
	let container, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('enterEditMode dispatches the load items callback when LoadItemsOnEdit', () => {
		const { dispatchMock, setCallbackParameterMock, setCausesValidationMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions({ LoadItemsOnEdit: true }));
		ctrl.enterEditMode(null);
		expect(select.disabled).toBe(true);
		expect(setCausesValidationMock).toHaveBeenCalledWith(false);
		expect(setCallbackParameterMock).toHaveBeenCalledWith(['__InlineEditor_loadItems__', '0']);
		expect(dispatchMock).toHaveBeenCalled();
	});

	it('does not dispatch the load items callback without LoadItemsOnEdit', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		expect(dispatchMock).not.toHaveBeenCalled();
	});

	it('onLoadItemsSuccess re-enables editing and refreshes originalSelection', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		select.disabled = true;
		select.selectedIndex = 1;
		ctrl.onLoadItemsSuccess({}, null);
		expect(ctrl.isEditing).toBe(true);
		expect(select.disabled).toBe(false);
		expect(ctrl.originalSelection).toBe('1');
	});

	it('onLoadItemsFailure resets state and shows the label', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		ctrl.enterEditMode(null);
		const showLabel = vi.spyOn(ctrl, 'showLabel');
		ctrl.onLoadItemsFailure({}, 'err');
		expect(ctrl.isEditing).toBe(false);
		expect(showLabel).toHaveBeenCalled();
	});
});

// ─── Static helpers ──────────────────────────────────────────────────────────

describe('TInPlaceDropDownList static helpers', () => {
	let container;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('setDisplayEditor(id, true) calls enterEditMode on the registered instance', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		const enter = vi.spyOn(ctrl, 'enterEditMode');
		TInPlaceDropDownList.setDisplayEditor('ddl1', true);
		expect(enter).toHaveBeenCalledWith(null);
	});

	it('setDisplayEditor(id, false) calls exitEditMode on the registered instance', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		const exit = vi.spyOn(ctrl, 'exitEditMode');
		TInPlaceDropDownList.setDisplayEditor('ddl1', false);
		expect(exit).toHaveBeenCalledWith(null);
	});

	it('setReadOnly updates readOnly on the registered instance', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ ReadOnly: false }));
		TInPlaceDropDownList.setReadOnly('ddl1', true);
		expect(ctrl.readOnly).toBe(true);
	});

	it('helpers are no-ops for unknown IDs', () => {
		expect(() => TInPlaceDropDownList.setDisplayEditor('unknown', true)).not.toThrow();
		expect(() => TInPlaceDropDownList.setReadOnly('unknown', true)).not.toThrow();
	});
});

// ─── Shared base behavior ────────────────────────────────────────────────────

describe('TInPlaceControlBase shared registry and DisplayEditor', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearDropDowns();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('registers into the base registry keyed by the editor client ID', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		expect(TInPlaceControlBase.get('ddl1')).toBe(ctrl);
	});

	it('base statics drive a control registered by a subclass', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		TInPlaceControlBase.setReadOnly('ddl1', true);
		expect(ctrl.readOnly).toBe(true);
	});

	it('setLabelText applies a value through the base static', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		TInPlaceControlBase.setLabelText('ddl1', 'Server text');
		expect(label.innerHTML).toBe('Server text');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(false);
	});

	it('setLabelText with an empty value shows EmptyDisplayText and marks the label', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		TInPlaceControlBase.setLabelText('ddl1', '');
		expect(label.innerHTML).toBe('(none)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
	});

	it('setEmptyDisplayText updates the option and refreshes a label showing the placeholder', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		TInPlaceControlBase.setLabelText('ddl1', '');
		expect(label.innerHTML).toBe('(none)');
		TInPlaceControlBase.setEmptyDisplayText('ddl1', '(pick one)');
		expect(ctrl.options.EmptyDisplayText).toBe('(pick one)');
		expect(label.innerHTML).toBe('(pick one)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
	});

	it('setEmptyDisplayText leaves a label showing a value untouched', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		TInPlaceControlBase.setLabelText('ddl1', 'Alpha');
		TInPlaceControlBase.setEmptyDisplayText('ddl1', '(pick one)');
		expect(label.innerHTML).toBe('Alpha');
		expect(ctrl.options.EmptyDisplayText).toBe('(pick one)');
	});

	it('setEmptyDisplayText applies to the next empty value', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ EmptyDisplayText: '(none)' }));
		TInPlaceControlBase.setEmptyDisplayText('ddl1', '(pick one)');
		TInPlaceControlBase.setLabelText('ddl1', '');
		expect(label.innerHTML).toBe('(pick one)');
	});

	it('DisplayEditor option enters edit mode at construction', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions({ DisplayEditor: true }));
		expect(ctrl.isEditing).toBe(true);
		expect(select.style.display).not.toBe('none');
		expect(label.style.display).toBe('none');
	});

	it('DisplayEditor records originalSelection so a later change posts correctly', () => {
		const { setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TInPlaceDropDownList(makeOptions({ DisplayEditor: true }));
		select.selectedIndex = 2;
		ctrl.onSelectionChanged({});
		expect(setCallbackParameterMock).toHaveBeenCalledWith('0');
	});

	it('without DisplayEditor the control starts showing the label', () => {
		const ctrl = new TInPlaceDropDownList(makeOptions());
		expect(ctrl.isEditing).toBe(false);
		expect(select.style.display).toBe('none');
	});
});

// ─── TInPlaceTextBox HTML5 TextMode mapping ──────────────────────────────────

describe('TInPlaceTextBox HTML5 TextMode input types', () => {
	let container;

	function buildLabelDOM() {
		const div   = document.createElement('div');
		const label = document.createElement('span');
		label.id    = 'lbl1';
		label.innerHTML = 'Hello';
		div.appendChild(label);
		document.body.appendChild(div);
		return div;
	}

	function textBoxOptions(textMode) {
		return {
			ID:          'lbl1',
			TextBoxID:   'tb_lbl1',
			EditorID:    'tb_lbl1',
			EventTarget: 'lbl1',
			TextMode:    textMode,
			ReadOnly:    false,
			AutoPostBack: false,
			AutoHide:    false,
			LoadTextOnEdit: false,
		};
	}

	beforeEach(() => {
		clearRegistry();
		clearTextboxes();
		container = buildLabelDOM();
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it.each([
		['SingleLine', 'text'],
		['Password', 'password'],
		['Color', 'color'],
		['Date', 'date'],
		['DatetimeLocal', 'datetime-local'],
		['Email', 'email'],
		['Month', 'month'],
		['Number', 'number'],
		['Range', 'range'],
		['Search', 'search'],
		['Tel', 'tel'],
		['Time', 'time'],
		['Url', 'url'],
		['Week', 'week'],
	])('TextMode %s creates an input of type %s', (mode, type) => {
		new TInPlaceTextBox(textBoxOptions(mode));
		const field = document.getElementById('tb_lbl1');
		expect(field.tagName.toLowerCase()).toBe('input');
		expect(field.type).toBe(type);
	});

	it('unknown TextMode falls back to a text input', () => {
		new TInPlaceTextBox(textBoxOptions('Bogus'));
		expect(document.getElementById('tb_lbl1').type).toBe('text');
	});

	it('MultiLine still creates a textarea', () => {
		new TInPlaceTextBox(textBoxOptions('MultiLine'));
		expect(document.getElementById('tb_lbl1').tagName.toLowerCase()).toBe('textarea');
	});
});
