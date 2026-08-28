/**
 * Tests for Prado.WebUI.TInPlaceListBox — the multi-select in-place control.
 *
 * Source:
 *   framework/Web/Javascripts/source/prado/activecontrols/inlineeditor.js
 *
 * The editor is a server-rendered <select multiple>; the label shows the
 * selected option texts joined by options.SelectionSeparator. Network calls
 * are prevented by replacing Prado.CallbackRequest with a vi mock.
 */

import {
	TInPlaceControlBase,
	TInPlaceDropDownList,
	TInPlaceListBox,
	Registry,
} from '../adapters/inlineeditor.js';
import { clearRegistry, clearMap, mockCallbackRequest, restoreMocks } from '../helpers/callbackMock.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function clearListBoxes() {
	clearMap(TInPlaceListBox.listboxes);
}

function makeOptions(overrides = {}) {
	return Object.assign(
		{
			ID:                 'lb1__label',
			EditorID:           'lb1',
			EventTarget:        'lb1',
			ReadOnly:           false,
			AutoPostBack:       true,
			AutoHide:           true,
			EmptyDisplayText:   '',
			SelectionSeparator: ', ',
		},
		overrides,
	);
}

/**
 * Build a label span + a hidden <select multiple> with the given items.
 * `selected` is an array of indices.
 */
function buildDOM(items = ['Alpha', 'Beta', 'Gamma'], selected = [0]) {
	const container = document.createElement('div');
	const label     = document.createElement('span');
	label.id        = 'lb1__label';
	container.appendChild(label);

	const select = document.createElement('select');
	select.id       = 'lb1';
	select.name     = 'lb1[]';
	select.multiple = true;
	select.style.display = 'none';
	for (const [i, text] of items.entries()) {
		const option = document.createElement('option');
		option.value    = String(i);
		option.text     = text;
		option.selected = selected.includes(i);
		select.appendChild(option);
	}
	container.appendChild(select);
	document.body.appendChild(container);
	return { container, label, select };
}

// ─── Class shape ─────────────────────────────────────────────────────────────

describe('TInPlaceListBox class shape', () => {
	it('extends TInPlaceDropDownList', () => {
		expect(Object.getPrototypeOf(TInPlaceListBox.prototype)).toBe(TInPlaceDropDownList.prototype);
	});

	it('shares the base instance registry', () => {
		expect(TInPlaceListBox.listboxes).toBe(TInPlaceControlBase.instances);
	});
});

// ─── Construction ────────────────────────────────────────────────────────────

describe('TInPlaceListBox construction', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearListBoxes();
		({ container, label, select } = buildDOM());
	});

	afterEach(() => {
		restoreMocks();
		container.remove();
	});

	it('registers in the base registry keyed by the editor client ID', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(TInPlaceControlBase.get('lb1')).toBe(ctrl);
	});

	it('attaches to the server-rendered select as editField', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.editField).toBe(select);
	});
});

// ─── getSelectedText: joined ─────────────────────────────────────────────────

describe('TInPlaceListBox getSelectedText', () => {
	let container;

	afterEach(() => { restoreMocks(); container?.remove(); });

	it('joins the selected option texts with the separator', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0, 2]));
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.getSelectedText()).toBe('Alpha, Gamma');
	});

	it('uses a custom separator', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0, 1]));
		const ctrl = new TInPlaceListBox(makeOptions({ SelectionSeparator: ' | ' }));
		expect(ctrl.getSelectedText()).toBe('Alpha | Beta');
	});

	it('is empty when nothing is selected', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['Alpha', 'Beta'], []));
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.getSelectedText()).toBe('');
		expect(ctrl.isEditorEmpty()).toBe(true);
	});

	it('html-encodes each option text', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['a < b', 'c & d'], [0, 1]));
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.getEditorDisplayText()).toBe('a &lt; b, c &amp; d');
	});
});

// ─── captureSelection / applySelection (index-based) ─────────────────────────

describe('TInPlaceListBox selection snapshot', () => {
	let container, select;

	beforeEach(() => {
		clearRegistry();
		clearListBoxes();
		({ container, select } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0, 2]));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('captureSelection returns the selected indices', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.captureSelection()).toEqual([0, 2]);
	});

	it('applySelection restores exactly the given indices', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.applySelection([1]);
		expect(select.options[0].selected).toBe(false);
		expect(select.options[1].selected).toBe(true);
		expect(select.options[2].selected).toBe(false);
	});

	it('ESC reverts to the original multi-selection', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		select.options[0].selected = false;
		select.options[1].selected = true;
		ctrl.onKeyPressed({ keyCode: 27 });
		expect(ctrl.captureSelection()).toEqual([0, 2]);
	});

	it('revert is exact when option values are duplicated', () => {
		clearRegistry(); clearListBoxes(); container.remove();
		// Two options share value "1"; only the first is selected.
		({ container, select } = buildDOM(['Red', 'Blue', 'Green'], [0]));
		select.options[0].value = '1';
		select.options[1].value = '1';
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		select.options[2].selected = true; // change
		ctrl.applySelection(ctrl.originalSelection);
		expect(select.options[0].selected).toBe(true);
		expect(select.options[1].selected).toBe(false); // NOT re-selected despite same value
		expect(select.options[2].selected).toBe(false);
	});

	it('handles a prototype-name option value without corruption', () => {
		clearRegistry(); clearListBoxes(); container.remove();
		({ container, select } = buildDOM(['Proto', 'Other'], [0]));
		select.options[0].value = '__proto__';
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);         // originalSelection = [0]
		select.options[0].selected = false;
		select.options[1].selected = true;
		ctrl.applySelection(ctrl.originalSelection);
		expect(select.options[0].selected).toBe(true);
		expect(select.options[1].selected).toBe(false);
	});
});

// ─── change dispatches through the shared save flow ──────────────────────────

describe('TInPlaceListBox selection change', () => {
	let container, label, select;

	beforeEach(() => {
		clearRegistry();
		clearListBoxes();
		({ container, label, select } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0]));
	});

	afterEach(() => { restoreMocks(); container.remove(); });

	it('does NOT dispatch on each option toggle (multi-select accumulates)', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		// User ctrl-clicks a second, then a third option: each fires change.
		select.options[1].selected = true;
		ctrl.onSelectionChanged({});
		select.options[2].selected = true;
		ctrl.onSelectionChanged({});
		// The editor stays open; nothing posted yet.
		expect(dispatchMock).not.toHaveBeenCalled();
		expect(ctrl.isEditing).toBe(true);
	});

	it('commits the accumulated selection on blur with the original as parameter', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);              // originalSelection = [0]
		select.options[1].selected = true;     // build a multi-selection
		ctrl.onSelectionChanged({});
		select.options[2].selected = true;
		ctrl.onSelectionChanged({});
		ctrl.onDropDownBlur({});               // leaving the editor commits
		expect(setCallbackParameterMock).toHaveBeenCalledWith([0]);
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('does not dispatch on blur when the selection is unchanged', () => {
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		ctrl.onDropDownBlur({});
		expect(dispatchMock).not.toHaveBeenCalled();
		expect(ctrl.isEditing).toBe(false);
	});

	it('does not dispatch a second commit while the first save is in flight', () => {
		// dispatch() returns undefined on success (the real value); isSaving must
		// still latch so a rapid second blur does not stack a concurrent save.
		const { instance, dispatchMock } = mockCallbackRequest();
		instance.dispatch.mockReturnValue(undefined);
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		select.options[1].selected = true;
		ctrl.onSelectionChanged({});
		ctrl.onDropDownBlur({});          // commits: isSaving latches
		expect(ctrl.isSaving).toBe(true);
		expect(select.disabled).toBe(false); // not disabled (lazy form serialization)
		select.options[2].selected = true;
		ctrl.onDropDownBlur({});          // in flight -> must not dispatch again
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('keeps the editor open and the label intact when the commit dispatch does not start', () => {
		// dispatch() returns false (e.g. validation failure): the selection is
		// preserved and the editor stays open for retry; the label is untouched.
		mockCallbackRequest(false);
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		label.innerHTML = 'Alpha';
		select.options[1].selected = true;
		ctrl.onSelectionChanged({});
		ctrl.onDropDownBlur({});
		expect(ctrl.isEditing).toBe(true);   // still editable, retryable
		expect(ctrl.isSaving).toBe(false);
		expect(label.innerHTML).toBe('Alpha'); // label not changed to the uncommitted value
	});

	it('a single-selection list box commits on change like the drop down', () => {
		clearRegistry(); clearListBoxes(); container.remove();
		({ container, label, select } = buildDOM(['Alpha', 'Beta'], [0]));
		select.multiple = false;
		const { dispatchMock } = mockCallbackRequest();
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		select.selectedIndex = 1;
		ctrl.onSelectionChanged({});
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('shows the joined selection in the label on save success', () => {
		const ctrl = new TInPlaceListBox(makeOptions());
		ctrl.enterEditMode(null);
		select.options[1].selected = true; // now 0 and 1
		ctrl.isSaving = true;
		ctrl.onValueChangedSuccess({}, null);
		expect(label.innerHTML).toBe('Alpha, Beta');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(false);
	});

	it('shows EmptyDisplayText and marks the label when the selection is cleared', () => {
		const ctrl = new TInPlaceListBox(makeOptions({ EmptyDisplayText: '(none)' }));
		ctrl.enterEditMode(null);
		select.options[0].selected = false; // nothing selected
		ctrl.isSaving = true;
		ctrl.onValueChangedSuccess({}, null);
		expect(label.innerHTML).toBe('(none)');
		expect(ctrl.isShowingEmptyDisplayText()).toBe(true);
	});
});

// ─── loadItems selection reporting ───────────────────────────────────────────

describe('TInPlaceListBox getEditSelection (OnLoadingItems parameter)', () => {
	let container, select;

	afterEach(() => { restoreMocks(); container?.remove(); });

	it('reports all selected values for a multi-selection, not just the first', () => {
		clearRegistry(); clearListBoxes();
		({ container, select } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0, 2]));
		const ctrl = new TInPlaceListBox(makeOptions());
		expect(ctrl.getEditSelection()).toEqual(['0', '2']);
	});

	it('dispatches the full selection in the load-items callback parameter', () => {
		clearRegistry(); clearListBoxes();
		({ container, select } = buildDOM(['Alpha', 'Beta', 'Gamma'], [0, 2]));
		const { setCallbackParameterMock } = mockCallbackRequest();
		const ctrl = new TInPlaceListBox(makeOptions({ LoadItemsOnEdit: true }));
		ctrl.enterEditMode(null); // triggers loadItems
		expect(setCallbackParameterMock).toHaveBeenCalledWith(['__InlineEditor_loadItems__', ['0', '2']]);
	});
});

// ─── label parity: empty-text options and empty separator ────────────────────

describe('TInPlaceListBox label parity', () => {
	let container;

	afterEach(() => { restoreMocks(); container?.remove(); });

	it('skips selected options with empty text, matching the server', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['', 'Apple', 'Banana'], [0, 1]));
		const ctrl = new TInPlaceListBox(makeOptions());
		// No leading separator from the empty-text option.
		expect(ctrl.getSelectedText()).toBe('Apple');
	});

	it('honors an empty SelectionSeparator', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['A', 'B', 'C'], [0, 1]));
		const ctrl = new TInPlaceListBox(makeOptions({ SelectionSeparator: '' }));
		expect(ctrl.getSelectedText()).toBe('AB');
	});

	it('uses a custom separator', () => {
		clearRegistry(); clearListBoxes();
		({ container } = buildDOM(['A', 'B'], [0, 1]));
		const ctrl = new TInPlaceListBox(makeOptions({ SelectionSeparator: ' / ' }));
		expect(ctrl.getSelectedText()).toBe('A / B');
	});
});
