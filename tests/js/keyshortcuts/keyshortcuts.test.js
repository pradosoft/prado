/**
 * Tests for Prado.KeyShortcuts (keyshortcuts.js).
 * Source: framework/Web/Javascripts/source/prado/keyshortcuts/keyshortcuts.js
 *
 * Declared bindings are elements with aria-keyshortcuts and data-prado-keyshortcuts,
 * as TWebControl.KeyShortcuts renders them. Key presses are dispatched as keydown
 * events on the focused element and reach the registry's document listener.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { KeyShortcuts } from '../adapters/keyshortcuts.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

/**
 * Dispatches a keydown on the target (the focused element by default).
 * @returns {KeyboardEvent} the dispatched event
 */
function press(init, target = document.activeElement || document.body) {
	const event = new KeyboardEvent('keydown', Object.assign({ bubbles: true, cancelable: true, composed: true }, init));
	target.dispatchEvent(event);
	return event;
}

let clicks;

// jsdom without a URL has an opaque origin and no localStorage.
const memoryStorage = {
	items: new Map(),
	getItem(key) {
		return this.items.has(key) ? this.items.get(key) : null;
	},
	setItem(key, value) {
		this.items.set(key, String(value));
	},
	clear() {
		this.items.clear();
	},
};
Object.defineProperty(window, 'localStorage', { value: memoryStorage, configurable: true });

function track(id) {
	document.getElementById(id).addEventListener('click', () => clicks.push(id));
}

beforeEach(() => {
	document.body.innerHTML = '';
	KeyShortcuts._bindings = [];
	KeyShortcuts._characterKeys = null;
	window.localStorage.clear();
	clicks = [];
});

afterEach(() => {
	vi.restoreAllMocks();
	document.body.innerHTML = '';
});

// ─── syntax ──────────────────────────────────────────────────────────────────

describe('normalize', () => {
	it('orders modifiers, uppercases letters, and accepts aliases', () => {
		expect(KeyShortcuts.normalize('ctrl+s cmd+s')).toBe('Control+S Meta+S');
		expect(KeyShortcuts.normalize('shift+alt+p')).toBe('Alt+Shift+P');
		expect(KeyShortcuts.normalize('Option+Command+k')).toBe('Alt+Meta+K');
		expect(KeyShortcuts.normalize('  ?  ')).toBe('?');
	});

	it('normalizes named keys and removes repeats', () => {
		expect(KeyShortcuts.normalize('esc return spacebar plus del ins up down left right')).toBe(
			'Escape Enter Space Plus Delete Insert ArrowUp ArrowDown ArrowLeft ArrowRight');
		expect(KeyShortcuts.normalize('f1 F24 pageup PAGEDOWN home end tab backspace')).toBe(
			'F1 F24 PageUp PageDown Home End Tab Backspace');
		expect(KeyShortcuts.normalize('Control+S control+s')).toBe('Control+S');
	});

	it('keeps a letter whose uppercase is two characters', () => {
		expect(KeyShortcuts.normalize('ß é')).toBe('ß É');
	});

	it('returns an empty string for an empty value', () => {
		expect(KeyShortcuts.normalize('')).toBe('');
		expect(KeyShortcuts.normalize(null)).toBe('');
	});

	it('rejects shortcuts without exactly one key, empty tokens, and unknown names', () => {
		for (const value of ['Control', 'Control+Shift', 'A+B', 'Control++', '+', 'F25', 'F0', 'Hyper+A', 'Control+S Bogus']) {
			expect(KeyShortcuts.normalize(value), value).toBeNull();
		}
	});

	it('does not treat inherited object names as keys', () => {
		expect(KeyShortcuts.normalize('constructor')).toBeNull();
		expect(KeyShortcuts.normalize('toString+A')).toBeNull();
	});
});

describe('classification', () => {
	it('identifies reserved shortcuts', () => {
		for (const shortcut of ['Tab', 'Shift+Tab', 'Enter', 'Space', 'ArrowUp', 'Control+C', 'Meta+V', 'Control+Shift+Z', 'Shift+Meta+Z', 'Meta+Plus', 'Control+0', 'F5', 'Alt+ArrowLeft']) {
			expect(KeyShortcuts.isReserved(shortcut), shortcut).toBe(true);
		}
		for (const shortcut of ['Control+S', 'Meta+S', 'Escape', '?', 'Alt+Shift+P', 'F2']) {
			expect(KeyShortcuts.isReserved(shortcut), shortcut).toBe(false);
		}
	});

	it('identifies character-key shortcuts', () => {
		expect(KeyShortcuts.isCharacterKey('B')).toBe(true);
		expect(KeyShortcuts.isCharacterKey('Shift+B')).toBe(true);
		expect(KeyShortcuts.isCharacterKey('?')).toBe(true);
		expect(KeyShortcuts.isCharacterKey('Plus')).toBe(true);
		expect(KeyShortcuts.isCharacterKey('Control+B')).toBe(false);
		expect(KeyShortcuts.isCharacterKey('Escape')).toBe(false);
		expect(KeyShortcuts.isCharacterKey('F2')).toBe(false);
	});
});

describe('eventShortcuts', () => {
	it('builds the shortcut from event.key and the modifiers', () => {
		expect(KeyShortcuts.eventShortcuts({ key: 's', code: 'KeyS', ctrlKey: true })).toEqual(['Control+S']);
		expect(KeyShortcuts.eventShortcuts({ key: 'S', code: 'KeyS', shiftKey: true, altKey: true })).toEqual(['Alt+Shift+S']);
		expect(KeyShortcuts.eventShortcuts({ key: ' ', code: 'Space' })).toEqual(['Space']);
		expect(KeyShortcuts.eventShortcuts({ key: 'Esc' })).toEqual(['Escape']);
	});

	it('matches a shifted symbol without Shift', () => {
		expect(KeyShortcuts.eventShortcuts({ key: '?', code: 'Slash', shiftKey: true })).toEqual(['Shift+?', '?']);
		expect(KeyShortcuts.eventShortcuts({ key: '+', code: 'Equal', shiftKey: true, ctrlKey: true })).toEqual(['Control+Shift+Plus', 'Control+Plus']);
	});

	it('falls back to the physical letter or digit for other characters', () => {
		// macOS Option+S types ß.
		expect(KeyShortcuts.eventShortcuts({ key: 'ß', code: 'KeyS', altKey: true })).toEqual(['Alt+ß', 'Alt+S']);
		// A Cyrillic layout.
		expect(KeyShortcuts.eventShortcuts({ key: 'ы', code: 'KeyS', ctrlKey: true })).toEqual(['Control+Ы', 'Control+S']);
		// Shift+1 types !.
		expect(KeyShortcuts.eventShortcuts({ key: '!', code: 'Digit1', shiftKey: true })).toEqual(['Shift+!', '!', 'Shift+1']);
		// A dead key.
		expect(KeyShortcuts.eventShortcuts({ key: 'Dead', code: 'KeyE', altKey: true })).toEqual(['Alt+E']);
	});

	it('keeps the layout letter when event.key is an ASCII letter', () => {
		// AZERTY: the KeyQ position types a.
		expect(KeyShortcuts.eventShortcuts({ key: 'a', code: 'KeyQ', ctrlKey: true })).toEqual(['Control+A']);
	});

	it('returns nothing for modifier keys and AltGraph', () => {
		expect(KeyShortcuts.eventShortcuts({ key: 'Control', ctrlKey: true })).toEqual([]);
		expect(KeyShortcuts.eventShortcuts({ key: '', code: '' })).toEqual([]);
		expect(KeyShortcuts.eventShortcuts({ key: '@', code: 'KeyQ', ctrlKey: true, altKey: true, getModifierState: (k) => k === 'AltGraph' })).toEqual([]);
	});
});

// ─── declared elements ───────────────────────────────────────────────────────

describe('declared elements', () => {
	it('clicks a button and prevents the browser default', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S Meta+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		const ctrl = press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		const meta = press({ key: 's', code: 'KeyS', metaKey: true }, document.body);
		expect(clicks).toEqual(['save', 'save']);
		expect(ctrl.defaultPrevented).toBe(true);
		expect(meta.defaultPrevented).toBe(true);
	});

	it('ignores an element without the marker', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S">Save</button>';
		track('save');
		const event = press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(clicks).toEqual([]);
		expect(event.defaultPrevented).toBe(false);
	});

	it('focuses a text field', () => {
		document.body.innerHTML = '<input id="q" type="search" aria-keyshortcuts="/" data-prado-keyshortcuts=""><button id="b">b</button>';
		document.getElementById('b').focus();
		press({ key: '/', code: 'Slash' });
		expect(document.activeElement.id).toBe('q');
	});

	it('clicks links, check boxes, summaries, and button roles', () => {
		document.body.innerHTML = `
			<a id="a" href="#x" aria-keyshortcuts="Alt+1" data-prado-keyshortcuts="">a</a>
			<input id="c" type="checkbox" aria-keyshortcuts="Alt+2" data-prado-keyshortcuts="">
			<details><summary id="s" aria-keyshortcuts="Alt+3" data-prado-keyshortcuts="">s</summary></details>
			<span id="r" role="button" tabindex="0" aria-keyshortcuts="Alt+4" data-prado-keyshortcuts="">r</span>`;
		for (const id of ['a', 'c', 's', 'r']) {
			track(id);
		}
		press({ key: '1', code: 'Digit1', altKey: true }, document.body);
		press({ key: '2', code: 'Digit2', altKey: true }, document.body);
		press({ key: '3', code: 'Digit3', altKey: true }, document.body);
		press({ key: '4', code: 'Digit4', altKey: true }, document.body);
		expect(clicks).toEqual(['a', 'c', 's', 'r']);
		expect(document.getElementById('c').checked).toBe(true);
	});

	it('dispatches a cancelable prado:keyshortcut event before acting', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		const details = [];
		document.addEventListener('prado:keyshortcut', (e) => {
			details.push(e.detail.shortcut);
			e.preventDefault();
		}, { once: true });
		const event = press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(details).toEqual(['Control+S']);
		expect(clicks).toEqual([]);
		expect(event.defaultPrevented).toBe(true);
	});

	it('skips disabled, inert, and hidden elements', () => {
		document.body.innerHTML = `
			<button id="d" disabled aria-keyshortcuts="Alt+D" data-prado-keyshortcuts="">d</button>
			<fieldset disabled><button id="f" aria-keyshortcuts="Alt+F" data-prado-keyshortcuts="">f</button></fieldset>
			<div inert><button id="i" aria-keyshortcuts="Alt+I" data-prado-keyshortcuts="">i</button></div>
			<div hidden><button id="h" aria-keyshortcuts="Alt+H" data-prado-keyshortcuts="">h</button></div>`;
		for (const key of ['d', 'f', 'i', 'h']) {
			const event = press({ key, code: 'Key' + key.toUpperCase(), altKey: true }, document.body);
			expect(event.defaultPrevented, key).toBe(false);
		}
	});

	it('uses checkVisibility when the browser has it', () => {
		document.body.innerHTML = '<button id="v" aria-keyshortcuts="Alt+V" data-prado-keyshortcuts="">v</button>';
		track('v');
		const button = document.getElementById('v');
		button.checkVisibility = () => false;
		press({ key: 'v', code: 'KeyV', altKey: true }, document.body);
		button.checkVisibility = () => true;
		press({ key: 'v', code: 'KeyV', altKey: true }, document.body);
		expect(clicks).toEqual(['v']);
	});

	it('follows an element replaced by a callback and stops for a removed one', () => {
		document.body.innerHTML = '<div id="panel"><button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button></div>';
		document.getElementById('panel').innerHTML = '<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(clicks).toEqual(['save']);
		document.getElementById('panel').innerHTML = '';
		const event = press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(event.defaultPrevented).toBe(false);
	});

	it('reads a changed aria-keyshortcuts value', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		document.getElementById('save').setAttribute('aria-keyshortcuts', 'Control+E');
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		press({ key: 'e', code: 'KeyE', ctrlKey: true }, document.body);
		expect(clicks).toEqual(['save', 'save']);
	});

	it('warns once about an invalid value and ignores it', () => {
		const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
		document.body.innerHTML = '<button id="bad" aria-keyshortcuts="Control+" data-prado-keyshortcuts="">bad</button>';
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(warn).toHaveBeenCalledTimes(1);
	});

	it('runs once for a held key and keeps preventing the default', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		const repeat = press({ key: 's', code: 'KeyS', ctrlKey: true, repeat: true }, document.body);
		expect(clicks).toEqual(['save']);
		expect(repeat.defaultPrevented).toBe(true);
	});
});

// ─── typing safety ───────────────────────────────────────────────────────────

describe('typing safety', () => {
	beforeEach(() => {
		document.body.innerHTML = `
			<button id="bold" aria-keyshortcuts="B" data-prado-keyshortcuts="">Bold</button>
			<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>
			<button id="help" aria-keyshortcuts="F2" data-prado-keyshortcuts="">Help</button>
			<input id="text" type="text">
			<textarea id="area"></textarea>
			<div id="rich" contenteditable="true"><span id="inner">x</span></div>
			<div id="combo" role="combobox" tabindex="0"></div>
			<input id="check" type="checkbox">`;
		for (const id of ['bold', 'save', 'help']) {
			track(id);
		}
	});

	it('ignores character keys in editable fields', () => {
		for (const id of ['text', 'area', 'inner', 'combo']) {
			const event = press({ key: 'b', code: 'KeyB' }, document.getElementById(id));
			expect(event.defaultPrevented, id).toBe(false);
		}
		expect(clicks).toEqual([]);
	});

	it('runs modified shortcuts and function keys in editable fields', () => {
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.getElementById('text'));
		press({ key: 'F2', code: 'F2' }, document.getElementById('area'));
		expect(clicks).toEqual(['save', 'help']);
	});

	it('runs character keys outside editable fields', () => {
		press({ key: 'b', code: 'KeyB' }, document.getElementById('check'));
		expect(clicks).toEqual(['bold']);
	});

	it('ignores IME composition and handled events', () => {
		press({ key: 's', code: 'KeyS', ctrlKey: true, isComposing: true }, document.body);
		press({ key: 'Process', code: 'KeyS', ctrlKey: true, keyCode: 229 }, document.body);
		const handled = (e) => e.preventDefault();
		document.body.addEventListener('keydown', handled);
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		document.body.removeEventListener('keydown', handled);
		expect(clicks).toEqual([]);
	});
});

// ─── reserved, character keys, scope, modal ──────────────────────────────────

describe('reserved shortcuts', () => {
	it('need the reserved opt-in', () => {
		document.body.innerHTML = `
			<button id="no" aria-keyshortcuts="Control+Z" data-prado-keyshortcuts="">no</button>
			<button id="yes" aria-keyshortcuts="Control+Y" data-prado-keyshortcuts="" data-prado-keyshortcuts-reserved="true">yes</button>`;
		track('no');
		track('yes');
		const z = press({ key: 'z', code: 'KeyZ', ctrlKey: true }, document.body);
		press({ key: 'y', code: 'KeyY', ctrlKey: true }, document.body);
		expect(z.defaultPrevented).toBe(false);
		expect(clicks).toEqual(['yes']);
	});
});

describe('character keys setting', () => {
	it('turns page-wide character keys off and persists the choice', () => {
		document.body.innerHTML = `
			<button id="bold" aria-keyshortcuts="B" data-prado-keyshortcuts="">Bold</button>
			<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>`;
		track('bold');
		track('save');
		expect(KeyShortcuts.getCharacterKeysEnabled()).toBe(true);
		KeyShortcuts.setCharacterKeysEnabled(false);
		expect(window.localStorage.getItem(KeyShortcuts.STORAGE_KEY)).toBe('false');
		KeyShortcuts._characterKeys = null;
		expect(KeyShortcuts.getCharacterKeysEnabled()).toBe(false);
		press({ key: 'b', code: 'KeyB' }, document.body);
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(clicks).toEqual(['save']);
		KeyShortcuts.setCharacterKeysEnabled(true);
		press({ key: 'b', code: 'KeyB' }, document.body);
		expect(clicks).toEqual(['save', 'bold']);
	});

	it('defaults to enabled when storage throws', () => {
		vi.spyOn(memoryStorage, 'getItem').mockImplementation(() => {
			throw new Error('blocked');
		});
		vi.spyOn(memoryStorage, 'setItem').mockImplementation(() => {
			throw new Error('blocked');
		});
		expect(KeyShortcuts.getCharacterKeysEnabled()).toBe(true);
		KeyShortcuts.setCharacterKeysEnabled(false);
		expect(KeyShortcuts.getCharacterKeysEnabled()).toBe(false);
	});

	it('keeps scoped character keys working while page-wide ones are off', () => {
		document.body.innerHTML = `
			<div id="toolbar" role="toolbar"><button id="first">first</button>
			<button id="bold" aria-keyshortcuts="B" data-prado-keyshortcuts="toolbar">Bold</button></div>`;
		track('bold');
		KeyShortcuts.setCharacterKeysEnabled(false);
		press({ key: 'b', code: 'KeyB' }, document.getElementById('first'));
		expect(clicks).toEqual(['bold']);
	});
});

describe('scope', () => {
	beforeEach(() => {
		document.body.innerHTML = `
			<button id="outside">outside</button>
			<button id="global" aria-keyshortcuts="Alt+B" data-prado-keyshortcuts="">global</button>
			<div id="toolbar"><button id="in">in</button>
				<button id="scoped" aria-keyshortcuts="Alt+B" data-prado-keyshortcuts="toolbar">scoped</button>
				<div id="group"><button id="deep">deep</button>
					<button id="deeper" aria-keyshortcuts="Alt+B" data-prado-keyshortcuts="group">deeper</button></div>
			</div>
			<button id="lost" aria-keyshortcuts="Alt+L" data-prado-keyshortcuts="missing">lost</button>`;
		for (const id of ['global', 'scoped', 'deeper', 'lost']) {
			track(id);
		}
	});

	it('runs the deepest scope that contains focus, then page-wide', () => {
		press({ key: 'b', code: 'KeyB', altKey: true }, document.getElementById('deep'));
		press({ key: 'b', code: 'KeyB', altKey: true }, document.getElementById('in'));
		press({ key: 'b', code: 'KeyB', altKey: true }, document.getElementById('outside'));
		expect(clicks).toEqual(['deeper', 'scoped', 'global']);
	});

	it('skips a binding whose scope element is missing', () => {
		const event = press({ key: 'l', code: 'KeyL', altKey: true }, document.getElementById('outside'));
		expect(event.defaultPrevented).toBe(false);
	});

	it('uses the first declared element in document order', () => {
		document.body.innerHTML = `
			<button id="one" aria-keyshortcuts="Alt+O" data-prado-keyshortcuts="">one</button>
			<button id="two" aria-keyshortcuts="Alt+O" data-prado-keyshortcuts="">two</button>`;
		track('one');
		track('two');
		press({ key: 'o', code: 'KeyO', altKey: true }, document.body);
		expect(clicks).toEqual(['one']);
	});
});

describe('modal dialog', () => {
	it('runs only bindings inside the open modal dialog', () => {
		document.body.innerHTML = `
			<button id="page" aria-keyshortcuts="Alt+P" data-prado-keyshortcuts="">page</button>
			<dialog id="dlg" open><button id="ok" aria-keyshortcuts="Alt+O" data-prado-keyshortcuts="">ok</button></dialog>`;
		track('page');
		track('ok');
		const handler = vi.fn();
		KeyShortcuts.register('Alt+G', handler);
		vi.spyOn(KeyShortcuts, 'openModal').mockReturnValue(document.getElementById('dlg'));
		press({ key: 'p', code: 'KeyP', altKey: true }, document.getElementById('ok'));
		press({ key: 'g', code: 'KeyG', altKey: true }, document.getElementById('ok'));
		press({ key: 'o', code: 'KeyO', altKey: true }, document.getElementById('ok'));
		expect(clicks).toEqual(['ok']);
		expect(handler).not.toHaveBeenCalled();
	});

	it('finds no modal dialog when :modal is unsupported', () => {
		vi.spyOn(document, 'querySelector').mockImplementation(() => {
			throw new SyntaxError('unsupported');
		});
		expect(KeyShortcuts.openModal()).toBeNull();
	});
});

// ─── registrations ───────────────────────────────────────────────────────────

describe('register', () => {
	it('calls a function with the event and shortcut, and removes the binding', () => {
		const handler = vi.fn();
		const remove = KeyShortcuts.register('control+k meta+k', handler);
		const event = press({ key: 'k', code: 'KeyK', metaKey: true }, document.body);
		expect(handler).toHaveBeenCalledWith(event, 'Meta+K');
		expect(event.defaultPrevented).toBe(true);
		remove();
		remove();
		press({ key: 'k', code: 'KeyK', metaKey: true }, document.body);
		expect(handler).toHaveBeenCalledTimes(1);
	});

	it('activates an element given by reference or ID, following a replacement', () => {
		document.body.innerHTML = '<div id="wrap"><button id="go">go</button></div>';
		const button = document.getElementById('go');
		KeyShortcuts.register('Alt+G', button);
		KeyShortcuts.register('Alt+H', 'go');
		document.getElementById('wrap').innerHTML = '<button id="go">go</button>';
		track('go');
		press({ key: 'g', code: 'KeyG', altKey: true }, document.body);
		press({ key: 'h', code: 'KeyH', altKey: true }, document.body);
		expect(clicks).toEqual(['go', 'go']);
	});

	it('skips a removed element without an ID', () => {
		document.body.innerHTML = '<button>anon</button>';
		const button = document.querySelector('button');
		KeyShortcuts.register('Alt+N', button);
		button.remove();
		const event = press({ key: 'n', code: 'KeyN', altKey: true }, document.body);
		expect(event.defaultPrevented).toBe(false);
	});

	it('wins over a declared element, and a later registration wins', () => {
		document.body.innerHTML = '<button id="save" aria-keyshortcuts="Control+S" data-prado-keyshortcuts="">Save</button>';
		track('save');
		const first = vi.fn();
		const second = vi.fn();
		KeyShortcuts.register('Control+S', first);
		KeyShortcuts.register('Control+S', second);
		press({ key: 's', code: 'KeyS', ctrlKey: true }, document.body);
		expect(second).toHaveBeenCalledTimes(1);
		expect(first).not.toHaveBeenCalled();
		expect(clicks).toEqual([]);
	});

	it('honors scope and allowReserved options', () => {
		document.body.innerHTML = '<div id="list" tabindex="0"><span id="item" tabindex="0">item</span></div><button id="out">out</button>';
		const handler = vi.fn();
		KeyShortcuts.register('ArrowDown', handler, { scope: 'list', allowReserved: true });
		const blocked = vi.fn();
		KeyShortcuts.register('ArrowUp', blocked, { scope: document.getElementById('list') });
		press({ key: 'ArrowDown', code: 'ArrowDown' }, document.getElementById('out'));
		press({ key: 'ArrowDown', code: 'ArrowDown' }, document.getElementById('item'));
		press({ key: 'ArrowUp', code: 'ArrowUp' }, document.getElementById('item'));
		expect(handler).toHaveBeenCalledTimes(1);
		expect(blocked).not.toHaveBeenCalled();
	});

	it('throws for invalid or empty shortcuts', () => {
		expect(() => KeyShortcuts.register('Control+', () => {})).toThrow(/invalid shortcuts/);
		expect(() => KeyShortcuts.register('', () => {})).toThrow(/invalid shortcuts/);
	});
});

describe('helpers', () => {
	it('resolves the focused element when the event target is not an element', () => {
		document.body.innerHTML = '<input id="f">';
		document.getElementById('f').focus();
		expect(KeyShortcuts.eventTarget({ target: document, composedPath: () => [document] }).id).toBe('f');
		expect(KeyShortcuts.eventTarget({ target: document.body })).toBe(document.body);
	});

	it('classifies non-elements and contenteditable="false"', () => {
		document.body.innerHTML = '<div contenteditable="false"><span id="s">x</span></div><input id="r" type="radio">';
		expect(KeyShortcuts.isEditable(null)).toBe(false);
		expect(KeyShortcuts.isEditable(document)).toBe(false);
		expect(KeyShortcuts.isEditable(document.getElementById('s'))).toBe(false);
		expect(KeyShortcuts.isEditable(document.getElementById('r'))).toBe(false);
		expect(KeyShortcuts.isActivatable(document.getElementById('s'))).toBe(false);
		expect(KeyShortcuts.resolveElement(null)).toBeNull();
	});
});
