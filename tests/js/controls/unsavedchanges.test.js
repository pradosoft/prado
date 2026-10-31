/**
 * Tests for Prado.WebUI.TUnsavedChangesGuard (unsavedchanges.js).
 * Source: framework/Web/Javascripts/source/prado/controls/unsavedchanges.js
 *
 * The guard element sits in a form; its scope is the form, or the element ScopeID names.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TUnsavedChangesGuard } from '../adapters/unsavedchanges.js';

const FIELDS = `
	<input type="text" id="name" value="Ann">
	<textarea id="notes">line</textarea>
	<input type="checkbox" id="agree">
	<input type="radio" name="size" id="small" value="s" checked>
	<input type="radio" name="size" id="large" value="l">
	<select id="color"><option value="r">Red</option><option value="g">Green</option></select>
	<select id="tags" multiple><option value="a" selected>A</option><option value="b">B</option></select>
	<input type="hidden" id="state" value="x">
	<input type="submit" id="save" value="Save">
`;

function build(options = {}, fields = FIELDS) {
	document.body.innerHTML = `<form id="form">${fields}<span id="guard"></span></form>`;
	return new TUnsavedChangesGuard(Object.assign({ ID: 'guard', WarnOnLeave: true }, options));
}

function el(id) {
	return document.getElementById(id);
}

function type(input, value) {
	input.value = value;
	input.dispatchEvent(new Event('input', { bubbles: true }));
}

function change(input) {
	input.dispatchEvent(new Event('change', { bubbles: true }));
}

function beforeUnload() {
	const event = new Event('beforeunload', { cancelable: true });
	window.dispatchEvent(event);
	return event.defaultPrevented;
}

function formData() {
	el('form').dispatchEvent(new Event('formdata'));
}

beforeEach(() => {
	for (const k of Object.keys(global.Prado.Registry)) {
		const guard = global.Prado.Registry[k];
		if (guard && guard.deinitialize) {
			guard.deinitialize();
		}
	}
	document.body.innerHTML = '';
});

afterEach(() => {
	vi.useRealTimers();
});

describe('TUnsavedChangesGuard scope', () => {
	it('registers and watches the form that holds the guard', () => {
		const guard = build();
		expect(global.Prado.Registry.guard).toBe(guard);
		expect(guard.scope).toBe(el('form'));
		expect(guard.isDirty()).toBe(false);
	});

	it('watches only the ScopeID element', () => {
		document.body.innerHTML = '<form id="form"><div id="panel"><input id="inside" value="a"></div>'
			+ '<input id="outside" value="b"><span id="guard"></span></form>';
		const guard = new TUnsavedChangesGuard({ ID: 'guard', ScopeID: 'panel' });
		type(el('outside'), 'changed');
		expect(guard.isDirty()).toBe(false);
		type(el('inside'), 'changed');
		expect(guard.isDirty()).toBe(true);
		expect(el('panel').hasAttribute('data-prado-unsaved')).toBe(true);
		expect(el('form').hasAttribute('data-prado-unsaved')).toBe(false);
	});

	it('does nothing without a scope', () => {
		document.body.innerHTML = '<span id="guard"></span>';
		const guard = new TUnsavedChangesGuard({ ID: 'guard' });
		expect(guard.update()).toBe(false);
		expect(guard.fields()).toEqual([]);
	});

	it('skips hidden and button inputs and fields under data-prado-dirty-ignore', () => {
		const guard = build({}, FIELDS + '<div data-prado-dirty-ignore><input id="search" value=""></div>');
		const ids = guard.fields().map((f) => f.id);
		expect(ids).toEqual(['name', 'notes', 'agree', 'small', 'large', 'color', 'tags']);
		type(el('search'), 'query');
		expect(guard.isDirty()).toBe(false);
	});
});

describe('TUnsavedChangesGuard dirty detection', () => {
	it('starts clean on the rendered defaults', () => {
		const guard = build();
		expect(guard.dirtyFields()).toEqual([]);
		expect(el('form').hasAttribute('data-prado-unsaved')).toBe(false);
	});

	it('marks a changed text field and clears the mark when the change is undone', () => {
		const guard = build({ DirtyCssClass: 'changed edited' });
		type(el('name'), 'Bob');
		expect(guard.isDirty()).toBe(true);
		expect(el('name').hasAttribute('data-prado-dirty')).toBe(true);
		expect(el('name').classList.contains('changed')).toBe(true);
		expect(el('name').classList.contains('edited')).toBe(true);
		expect(el('form').hasAttribute('data-prado-unsaved')).toBe(true);
		type(el('name'), 'Ann');
		expect(guard.isDirty()).toBe(false);
		expect(el('name').hasAttribute('data-prado-dirty')).toBe(false);
		expect(el('name').classList.contains('changed')).toBe(false);
	});

	it('compares a textarea with its default text', () => {
		const guard = build();
		type(el('notes'), 'line\nmore');
		expect(guard.dirtyFields()).toEqual([el('notes')]);
		type(el('notes'), 'line');
		expect(guard.isDirty()).toBe(false);
	});

	it('marks a checkbox in both directions', () => {
		const guard = build();
		el('agree').checked = true;
		change(el('agree'));
		expect(el('agree').hasAttribute('data-prado-dirty')).toBe(true);
		el('agree').checked = false;
		change(el('agree'));
		expect(guard.isDirty()).toBe(false);
	});

	it('marks only the checked radio of a changed group', () => {
		const guard = build();
		el('large').checked = true;
		change(el('large'));
		expect(guard.isDirty()).toBe(true);
		expect(el('large').hasAttribute('data-prado-dirty')).toBe(true);
		expect(el('small').hasAttribute('data-prado-dirty')).toBe(false);
		el('small').checked = true;
		change(el('small'));
		expect(guard.isDirty()).toBe(false);
		expect(el('large').hasAttribute('data-prado-dirty')).toBe(false);
	});

	it('treats the first option of a select without a selected option as its default', () => {
		const guard = build();
		expect(guard.isFieldDirty(el('color'))).toBe(false);
		el('color').selectedIndex = 1;
		change(el('color'));
		expect(guard.dirtyFields()).toEqual([el('color')]);
		el('color').selectedIndex = 0;
		change(el('color'));
		expect(guard.isDirty()).toBe(false);
	});

	it('skips a disabled first option for the default of a select', () => {
		const guard = build({}, '<select id="pick"><option disabled>Choose</option><option>One</option></select>');
		expect(el('pick').selectedIndex).toBe(1);
		expect(guard.isDirty()).toBe(false);
	});

	it('compares each option of a multiple select', () => {
		const guard = build();
		el('tags').options[1].selected = true;
		change(el('tags'));
		expect(guard.dirtyFields()).toEqual([el('tags')]);
		el('tags').options[1].selected = false;
		change(el('tags'));
		expect(guard.isDirty()).toBe(false);
	});

	it('compares a list box without a selected option with no selection', () => {
		const guard = build({}, '<select id="list" size="3"><option>One</option><option>Two</option></select>');
		expect(guard.isDirty()).toBe(false);
		el('list').selectedIndex = 0;
		change(el('list'));
		expect(guard.isDirty()).toBe(true);
	});

	it('sanitizes the default of an input', () => {
		const guard = build({}, '<input type="color" id="tint" value="#FF0000"><input type="range" id="level">');
		expect(guard.isDirty()).toBe(false);
		expect(guard.defaultValueOf(el('tint'))).toBe(el('tint').value);
		type(el('tint'), '#00ff00');
		expect(guard.dirtyFields()).toEqual([el('tint')]);
	});

	it('compares chosen files with the baseline', () => {
		const guard = build();
		const file = {};
		expect(guard.sameFiles(null, undefined)).toBe(true);
		expect(guard.sameFiles([file], undefined)).toBe(false);
		expect(guard.sameFiles([file], [file])).toBe(true);
		expect(guard.sameFiles([file], [{}])).toBe(false);
	});

	it('removes a mark from a field that is no longer watched', () => {
		const guard = build();
		type(el('name'), 'Bob');
		el('name').setAttribute('data-prado-dirty-ignore', '');
		guard.update();
		expect(el('name').hasAttribute('data-prado-dirty')).toBe(false);
		expect(guard.isDirty()).toBe(false);
	});
});

describe('TUnsavedChangesGuard state', () => {
	it('dispatches prado:dirtyChange on each change of the scope state', () => {
		build();
		const states = [];
		el('form').addEventListener('prado:dirtyChange', (e) => states.push(e.detail.dirty));
		type(el('name'), 'B');
		type(el('name'), 'Bo');
		type(el('name'), 'Ann');
		expect(states).toEqual([true, false]);
	});

	it('shows StatusText in the guard element while dirty', () => {
		build({ StatusText: 'Unsaved changes' });
		type(el('name'), 'Bob');
		expect(el('guard').textContent).toBe('Unsaved changes');
		type(el('name'), 'Ann');
		expect(el('guard').textContent).toBe('');
	});

	it('leaves the guard element alone without StatusText', () => {
		build();
		el('guard').textContent = 'app text';
		type(el('name'), 'Bob');
		expect(el('guard').textContent).toBe('app text');
	});

	it('starts dirty with the Dirty option until marked clean', () => {
		const guard = build({ Dirty: true, StatusText: 'Unsaved' });
		expect(guard.isDirty()).toBe(true);
		expect(guard.dirtyFields()).toEqual([]);
		expect(el('guard').textContent).toBe('Unsaved');
		guard.markClean();
		expect(guard.isDirty()).toBe(false);
	});

	it('markDirty keeps the scope dirty without a changed field', () => {
		const guard = build();
		guard.markDirty();
		type(el('name'), 'Ann');
		expect(guard.isDirty()).toBe(true);
	});

	it('markClean takes the current state as the baseline', () => {
		const guard = build();
		type(el('name'), 'Bob');
		type(el('notes'), 'new');
		el('agree').checked = true;
		el('large').checked = true;
		el('color').selectedIndex = 1;
		el('tags').options[1].selected = true;
		change(el('tags'));
		expect(guard.dirtyFields()).toHaveLength(6);
		guard.markClean();
		expect(guard.isDirty()).toBe(false);
		expect(document.querySelectorAll('[data-prado-dirty]')).toHaveLength(0);
		expect(el('name').defaultValue).toBe('Bob');
		expect(el('large').defaultChecked).toBe(true);
		expect(el('small').defaultChecked).toBe(false);
		expect(el('color').options[1].defaultSelected).toBe(true);
		type(el('name'), 'Ann');
		expect(guard.dirtyFields()).toEqual([el('name')]);
	});

	it('re-checks after a form reset', async () => {
		const guard = build();
		type(el('name'), 'Bob');
		el('form').reset();
		await new Promise((r) => setTimeout(r, 0));
		expect(guard.isDirty()).toBe(false);
	});

	it('re-checks after a callback response updates the fields', () => {
		const guard = build();
		type(el('name'), 'Bob');
		el('name').setAttribute('value', 'Bob');
		expect(guard.isDirty()).toBe(true);
		document.dispatchEvent(new CustomEvent('prado:callbackUpdated'));
		expect(guard.isDirty()).toBe(false);
	});

	it('marks clean and dirty by ID through the static functions', () => {
		const guard = build();
		TUnsavedChangesGuard.markDirty('guard');
		expect(guard.isDirty()).toBe(true);
		TUnsavedChangesGuard.markClean('guard');
		expect(guard.isDirty()).toBe(false);
		expect(() => TUnsavedChangesGuard.markClean('missing')).not.toThrow();
	});
});

describe('TUnsavedChangesGuard leave warning', () => {
	it('warns through beforeunload only while dirty', () => {
		const add = vi.spyOn(window, 'addEventListener');
		build();
		expect(add.mock.calls.filter((c) => c[0] === 'beforeunload')).toHaveLength(0);
		expect(beforeUnload()).toBe(false);
		type(el('name'), 'Bob');
		expect(add.mock.calls.filter((c) => c[0] === 'beforeunload')).toHaveLength(1);
		expect(beforeUnload()).toBe(true);
		type(el('name'), 'Ann');
		expect(beforeUnload()).toBe(false);
		add.mockRestore();
	});

	it('does not warn with WarnOnLeave false', () => {
		build({ WarnOnLeave: false });
		type(el('name'), 'Bob');
		expect(beforeUnload()).toBe(false);
	});

	it('lets a submission of its form leave', () => {
		build();
		type(el('name'), 'Bob');
		formData();
		expect(beforeUnload()).toBe(false);
		expect(beforeUnload()).toBe(true);
	});

	it('warns after a submission to another window', () => {
		build();
		type(el('name'), 'Bob');
		el('form').setAttribute('target', 'upload_frame');
		formData();
		expect(beforeUnload()).toBe(true);
		el('form').setAttribute('target', '_self');
		formData();
		expect(beforeUnload()).toBe(false);
	});

	it('uses the formtarget of the submitter', () => {
		build();
		type(el('name'), 'Bob');
		el('save').setAttribute('formtarget', '_blank');
		const submit = new Event('submit');
		Object.defineProperty(submit, 'submitter', { value: el('save') });
		el('form').dispatchEvent(submit);
		formData();
		expect(beforeUnload()).toBe(true);
	});

	it('ignores formdata of another form', () => {
		build();
		type(el('name'), 'Bob');
		const other = document.createElement('form');
		document.body.appendChild(other);
		other.dispatchEvent(new Event('formdata', { bubbles: true }));
		expect(beforeUnload()).toBe(true);
	});

	it('keeps the leave through an edit in the task of the submission', async () => {
		build();
		el('agree').checked = true;
		change(el('agree'));
		formData();
		change(el('agree'));
		expect(beforeUnload()).toBe(false);
	});

	it('ends the leave on an edit after the submission task', async () => {
		build();
		type(el('name'), 'Bob');
		formData();
		await new Promise((r) => setTimeout(r, 0));
		type(el('name'), 'Bobby');
		expect(beforeUnload()).toBe(true);
	});

	it('lets a callback redirect leave', () => {
		build();
		type(el('name'), 'Bob');
		document.dispatchEvent(new CustomEvent('prado:callbackRedirect'));
		expect(beforeUnload()).toBe(false);
	});

	it('ends the leave when the page is shown again', () => {
		build();
		type(el('name'), 'Bob');
		formData();
		window.dispatchEvent(new Event('pageshow'));
		expect(beforeUnload()).toBe(true);
	});

	it('removes its listeners when a new guard replaces it', () => {
		const old = build();
		type(el('name'), 'Bob');
		const remove = vi.spyOn(window, 'removeEventListener');
		const guard = new TUnsavedChangesGuard({ ID: 'guard', WarnOnLeave: true });
		expect(remove).toHaveBeenCalledWith('beforeunload', old.unloadHandler);
		expect(old.captures).toEqual([]);
		expect(global.Prado.Registry.guard).toBe(guard);
		expect(guard.isDirty()).toBe(true);
		remove.mockRestore();
	});

	it('a replacing guard clears the state the replaced one left', () => {
		build();
		type(el('name'), 'Bob');
		el('name').setAttribute('value', 'Bob');
		const guard = new TUnsavedChangesGuard({ ID: 'guard', WarnOnLeave: true, StatusText: 'Unsaved' });
		expect(guard.isDirty()).toBe(false);
		expect(el('form').hasAttribute('data-prado-unsaved')).toBe(false);
		expect(el('name').hasAttribute('data-prado-dirty')).toBe(false);
	});

	it('a replacing guard keeps the markDirty state and warns', () => {
		const old = build();
		old.markDirty();
		const states = [];
		el('form').addEventListener('prado:dirtyChange', (e) => states.push(e.detail.dirty));
		const guard = new TUnsavedChangesGuard({ ID: 'guard', WarnOnLeave: true });
		expect(guard.isDirty()).toBe(true);
		expect(states).toEqual([]);
		expect(beforeUnload()).toBe(true);
	});
});
