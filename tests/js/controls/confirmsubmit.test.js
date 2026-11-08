/**
 * Tests for Prado.WebUI.TConfirmSubmit (confirmsubmit.js).
 * Source: framework/Web/Javascripts/source/prado/controls/confirmsubmit.js
 *
 * Strategy
 * --------
 * jsdom has no dialog methods, so `showModal()` and `close()` are stubbed; `close()`
 * dispatches the `close` event at once. Each control has a spy on its own event, so
 * a test sees whether the action reached it.
 *
 * ESM note: only tests/js/adapters/confirmsubmit.js changes on ESM conversion.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TConfirmSubmit } from '../adapters/confirmsubmit.js';

let control;
let dialog;
let spies;

const byId = (id) => document.getElementById(id);

function create(options = {}) {
	control = new TConfirmSubmit({ ID: 'wrap', DialogID: 'dlg', OkID: 'ok', AutoPostBack: ['auto', 'pick', 'text', 'list'], ...options });
	return control;
}

beforeEach(() => {
	document.body.innerHTML =
		'<span id="wrap">' +
			'<a id="link" href="#x">Link</a><button id="btn">Button</button><button id="off" disabled>Off</button>' +
			'<input id="sub" type="submit"><input id="auto" type="checkbox"><input id="plain" type="checkbox">' +
			'<select id="pick"><option value="a" selected>A</option><option value="b">B</option></select>' +
			'<input id="text" value="start"><input id="plainText" value="x"><textarea id="area"></textarea>' +
			'<span id="list"><input id="list_c0" type="radio"></span><em id="other">Other</em>' +
		'</span><button id="outside">Out</button>' +
		'<dialog id="dlg"><button id="cancel">Cancel</button><button id="ok">OK</button></dialog>';
	dialog = byId('dlg');
	dialog.showModal = vi.fn(() => dialog.setAttribute('open', ''));
	dialog.close = vi.fn(() => {
		dialog.removeAttribute('open');
		dialog.dispatchEvent(new Event('close'));
	});
	Object.defineProperty(dialog, 'open', { configurable: true, get: () => dialog.hasAttribute('open') });
	spies = {};
	for (const id of ['link', 'btn', 'sub', 'auto', 'plain', 'list_c0', 'other', 'outside']) {
		spies[id] = vi.fn((event) => event.preventDefault());
		byId(id).addEventListener('click', spies[id]);
	}
	spies.change = vi.fn();
	byId('wrap').addEventListener('change', spies.change);
	create();
});

afterEach(() => {
	if (control && control.registered)
		control.deinitialize();
	document.body.innerHTML = '';
});

describe('Prado.WebUI.TConfirmSubmit clicks', () => {
	for (const id of ['link', 'btn', 'sub', 'auto', 'list_c0']) {
		it(`holds a click on ${id} and resumes it on OK`, () => {
			byId(id).click();
			expect(dialog.showModal).toHaveBeenCalledTimes(1);
			expect(spies[id]).not.toHaveBeenCalled();
			byId('ok').click();
			expect(dialog.open).toBe(false);
			expect(spies[id]).toHaveBeenCalledTimes(1);
		});
	}

	it('drops the click on Cancel', () => {
		byId('btn').click();
		dialog.close();
		expect(spies.btn).not.toHaveBeenCalled();
		expect(control.pending).toBeNull();
		byId('ok').click();
		expect(spies.btn).not.toHaveBeenCalled();
	});

	it('cancels the default action of a held click', () => {
		const event = new MouseEvent('click', { bubbles: true, cancelable: true });
		byId('auto').dispatchEvent(event);
		expect(event.defaultPrevented).toBe(true);
		expect(byId('auto').checked).toBe(false);
	});

	it('keeps the modifiers and position of the click', () => {
		byId('btn').dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, ctrlKey: true, clientX: 7, clientY: 9 }));
		byId('ok').click();
		const event = spies.btn.mock.calls[0][0];
		expect([event.ctrlKey, event.clientX, event.clientY]).toEqual([true, 7, 9]);
	});

	it('finds the item of a click on its child', () => {
		byId('link').innerHTML = '<b id="bold">Bold</b>';
		byId('bold').click();
		expect(dialog.showModal).toHaveBeenCalledTimes(1);
		expect(spies.link).not.toHaveBeenCalled();
	});

	it('leaves other clicks alone', () => {
		for (const id of ['plain', 'other', 'outside'])
			byId(id).click();
		byId('off').dispatchEvent(new MouseEvent('click', { bubbles: true }));
		expect(dialog.showModal).not.toHaveBeenCalled();
		expect(spies.plain).toHaveBeenCalledTimes(1);
		expect(spies.outside).toHaveBeenCalledTimes(1);
	});

	it('ignores a click while the dialog is open', () => {
		byId('btn').click();
		byId('link').click();
		expect(dialog.showModal).toHaveBeenCalledTimes(1);
		byId('ok').click();
		expect(spies.btn).toHaveBeenCalledTimes(1);
		expect(spies.link).not.toHaveBeenCalled();
	});

	it('keeps a new action when the close event of the last one arrives late', () => {
		byId('btn').click();
		dialog.removeAttribute('open');
		byId('link').click();
		dialog.dispatchEvent(new Event('close'));
		expect(dialog.open).toBe(true);
		byId('ok').click();
		expect(spies.btn).not.toHaveBeenCalled();
		expect(spies.link).toHaveBeenCalledTimes(1);
	});

	it('runs the drop of an earlier action before asking for the next', () => {
		const text = byId('text');
		text.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
		text.value = 'next';
		text.dispatchEvent(new Event('change', { bubbles: true }));
		dialog.removeAttribute('open');
		byId('btn').click();
		expect(text.value).toBe('start');
	});

	it('does not hold the resumed click again', () => {
		byId('btn').click();
		byId('ok').click();
		expect(dialog.showModal).toHaveBeenCalledTimes(1);
		byId('btn').click();
		expect(dialog.showModal).toHaveBeenCalledTimes(2);
	});
});

describe('Prado.WebUI.TConfirmSubmit changes', () => {
	const change = (el) => el.dispatchEvent(new Event('change', { bubbles: true }));
	const focus = (el) => el.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));

	it('holds a list change, restores it on Cancel, and resumes it on OK', () => {
		const pick = byId('pick');
		focus(pick);
		pick.value = 'b';
		change(pick);
		expect(dialog.showModal).toHaveBeenCalledTimes(1);
		expect(spies.change).not.toHaveBeenCalled();
		dialog.close();
		expect(pick.value).toBe('a');

		focus(pick);
		pick.value = 'b';
		change(pick);
		byId('ok').click();
		expect(spies.change).toHaveBeenCalledTimes(1);
		expect(pick.value).toBe('b');
	});

	it('restores a text box, and returns to the confirmed value after OK', () => {
		const text = byId('text');
		focus(text);
		text.value = 'next';
		change(text);
		byId('ok').click();
		expect(text.value).toBe('next');

		text.value = 'later';
		change(text);
		dialog.close();
		expect(text.value).toBe('next');
	});

	it('does not record a value while the dialog asks', () => {
		const text = byId('text');
		focus(text);
		text.value = 'next';
		change(text);
		focus(text);
		dialog.close();
		expect(text.value).toBe('start');
	});

	it('leaves a field without AutoPostBack alone', () => {
		change(byId('plainText'));
		change(byId('area'));
		expect(dialog.showModal).not.toHaveBeenCalled();
		expect(spies.change).toHaveBeenCalledTimes(2);
	});

	it('leaves the change of a checkbox to its click', () => {
		change(byId('auto'));
		expect(dialog.showModal).not.toHaveBeenCalled();
	});

	it('cancels the default action of Enter in a text box with AutoPostBack only', () => {
		const key = (el, k) => {
			const event = new KeyboardEvent('keydown', { key: k, bubbles: true, cancelable: true });
			el.dispatchEvent(event);
			return event.defaultPrevented;
		};
		expect(key(byId('text'), 'Enter')).toBe(true);
		expect(key(byId('text'), 'a')).toBe(false);
		expect(key(byId('plainText'), 'Enter')).toBe(false);
		expect(key(byId('area'), 'Enter')).toBe(false);
	});
});

describe('Prado.WebUI.TConfirmSubmit lifecycle', () => {
	it('does nothing without its dialog', () => {
		control.deinitialize();
		create({ DialogID: 'missing' });
		byId('btn').click();
		expect(spies.btn).toHaveBeenCalledTimes(1);
	});

	it('stops listening when deinitialized', () => {
		control.deinitialize();
		byId('btn').click();
		expect(dialog.showModal).not.toHaveBeenCalled();
		expect(spies.btn).toHaveBeenCalledTimes(1);
	});
});
