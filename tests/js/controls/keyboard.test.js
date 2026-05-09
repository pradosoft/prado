/**
 * Tests for Prado.WebUI.TKeyboard.
 * Source: framework/Web/Javascripts/source/prado/controls/keyboard.js
 *
 * ESM note: only tests/js/adapters/keyboard.js changes on ESM conversion;
 * this file stays unchanged.
 */

import { TKeyboard, Registry } from '../adapters/keyboard.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

let idCounter = 0;

/** Create a minimal host element + input element and return {id, host, input}. */
function buildKeyboardDOM() {
	const id = 'kb-test-' + (++idCounter);

	const host = document.createElement('div');
	host.id = id;
	document.body.appendChild(host);

	const input = document.createElement('input');
	input.type = 'text';
	input.id = id + '_input';
	document.body.appendChild(input);

	return { id, host, input };
}

/** Construct a TKeyboard instance with safe defaults. */
function makeKeyboard(overrides = {}) {
	const { id, host, input } = buildKeyboardDOM();
	const options = Object.assign(
		{
			ID: id,
			CssClass: 'Keyboard',
			ForControl: input.id,
			AutoHide: true,
		},
		overrides,
	);
	return new TKeyboard(options);
}

afterEach(() => {
	document.body.innerHTML = '';
	// clear the registry between tests
	for (const k of Object.keys(Registry)) {
		delete Registry[k];
	}
});

// ─── isObject ────────────────────────────────────────────────────────────────

describe('TKeyboard.isObject', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard();
	});

	it('returns true for a plain object', () => {
		expect(kb.isObject({})).toBe(true);
	});

	it('returns true for a DOM element', () => {
		expect(kb.isObject(document.createElement('div'))).toBe(true);
	});

	it('returns true for a function', () => {
		expect(kb.isObject(() => {})).toBe(true);
	});

	it('returns false for null', () => {
		expect(kb.isObject(null)).toBe(false);
	});

	it('returns false for a string', () => {
		expect(kb.isObject('hello')).toBe(false);
	});

	it('returns false for a number', () => {
		expect(kb.isObject(42)).toBe(false);
	});

	it('returns false for undefined', () => {
		expect(kb.isObject(undefined)).toBe(false);
	});
});

// ─── createElement ───────────────────────────────────────────────────────────

describe('TKeyboard.createElement', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard();
	});

	it('creates an element with the given tag name', () => {
		const el = kb.createElement('span', {});
		expect(el.tagName.toLowerCase()).toBe('span');
	});

	it('applies attribute hash to the element', () => {
		const el = kb.createElement('div', { className: 'foo', id: 'bar' });
		expect(el.className).toBe('foo');
		expect(el.id).toBe('bar');
	});

	it('appends the element to the given parent', () => {
		const parent = document.createElement('div');
		document.body.appendChild(parent);
		const el = kb.createElement('span', {}, parent);
		expect(parent.contains(el)).toBe(true);
	});

	it('returns the created element', () => {
		const el = kb.createElement('p', {});
		expect(el instanceof HTMLElement).toBe(true);
	});
});

// ─── render ──────────────────────────────────────────────────────────────────

describe('TKeyboard.render', () => {
	it('creates a keyboard div inside the host element', () => {
		const kb = makeKeyboard();
		const hostEl = kb.element;
		expect(hostEl.querySelector('div.Keyboard, div[class*="Keyboard"]')).not.toBeNull();
	});

	it('renders 4 rows (Line divs)', () => {
		const kb = makeKeyboard();
		const lines = kb.tagKeyboard.querySelectorAll('.Line');
		expect(lines.length).toBe(4);
	});

	it('each key has a Key1 and Key2 child', () => {
		const kb = makeKeyboard();
		const keys = kb.tagKeyboard.querySelectorAll('.Key');
		keys.forEach(key => {
			expect(key.querySelector('.Key1')).not.toBeNull();
			expect(key.querySelector('.Key2')).not.toBeNull();
		});
	});

	it('first row has 14 keys', () => {
		const kb = makeKeyboard();
		const firstLine = kb.tagKeyboard.querySelector('.Line');
		const keys = firstLine.querySelectorAll('.Key');
		expect(keys.length).toBe(14);
	});

	it('sets tagKeyboard.keyboard back-reference', () => {
		const kb = makeKeyboard();
		expect(kb.tagKeyboard.keyboard).toBe(kb);
	});
});

// ─── show / hide / isShown ───────────────────────────────────────────────────

describe('TKeyboard show / hide / isShown', () => {
	it('show sets visibility to visible', () => {
		const kb = makeKeyboard({ AutoHide: true });
		// AutoHide starts hidden
		kb.tagKeyboard.style.visibility = 'hidden';
		kb.show();
		expect(kb.tagKeyboard.style.visibility).toBe('visible');
	});

	it('hide sets visibility to hidden when autoHide is true', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'visible';
		kb.hide();
		expect(kb.tagKeyboard.style.visibility).toBe('hidden');
	});

	it('hide does not hide when autoHide is false', () => {
		const kb = makeKeyboard({ AutoHide: false });
		kb.tagKeyboard.style.visibility = 'visible';
		kb.hide();
		// should remain visible
		expect(kb.tagKeyboard.style.visibility).toBe('visible');
	});

	it('isShown returns false when hidden', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'hidden';
		expect(kb.isShown()).toBe(false);
	});

	it('isShown returns true when visible', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'visible';
		expect(kb.isShown()).toBe(true);
	});

	it('AutoHide false causes show() to be called during init', () => {
		const kb = makeKeyboard({ AutoHide: false });
		// show() is called in constructor for autoHide=false
		expect(kb.tagKeyboard.style.visibility).toBe('visible');
	});
});

// ─── type / insert ───────────────────────────────────────────────────────────

describe('TKeyboard.type — special keys', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'visible';
		kb.forControl.value = 'hello';
		// set selection to end
		kb.forControl.selectionStart = 5;
		kb.forControl.selectionEnd = 5;
	});

	it('type("Exit") hides the keyboard', () => {
		kb.type('Exit');
		expect(kb.isShown()).toBe(false);
	});

	it('type("Bksp") removes the character before the cursor', () => {
		kb.forControl.value = 'hello';
		kb.forControl.selectionStart = 5;
		kb.forControl.selectionEnd = 5;
		kb.type('Bksp');
		expect(kb.forControl.value).toBe('hell');
	});

	it('type("Del") removes the character after the cursor', () => {
		kb.forControl.value = 'hello';
		kb.forControl.selectionStart = 0;
		kb.forControl.selectionEnd = 0;
		kb.type('Del');
		expect(kb.forControl.value).toBe('ello');
	});

	it('type("Shift") toggles the Shift flag', () => {
		expect(kb.flagShift).toBe(false);
		kb.type('Shift');
		expect(kb.flagShift).toBe(true);
		kb.type('Shift');
		expect(kb.flagShift).toBe(false);
	});

	it('type("Caps") toggles the Caps flag', () => {
		const before = !!kb.caps;
		kb.type('Caps');
		expect(!!kb.caps).toBe(!before);
	});
});

describe('TKeyboard.type — regular characters', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard({ AutoHide: true });
		kb.forControl.value = '';
		kb.forControl.selectionStart = 0;
		kb.forControl.selectionEnd = 0;
	});

	it('appends a character to an empty input', () => {
		kb.type('a');
		expect(kb.forControl.value).toBe('a');
	});

	it('inserts at the cursor position', () => {
		kb.forControl.value = 'ac';
		kb.forControl.selectionStart = 1;
		kb.forControl.selectionEnd = 1;
		kb.type('b');
		expect(kb.forControl.value).toBe('abc');
	});

	it('unescapes &gt; to >', () => {
		kb.type('&gt;');
		expect(kb.forControl.value).toContain('>');
	});

	it('unescapes &lt; to <', () => {
		kb.type('&lt;');
		expect(kb.forControl.value).toContain('<');
	});

	it('unescapes &amp; to &', () => {
		kb.type('&amp;');
		expect(kb.forControl.value).toContain('&');
	});

	it('resets the Shift flag after a regular key press', () => {
		kb.flagShift = true;
		kb.type('A');
		expect(kb.flagShift).toBe(false);
	});
});

// ─── insert ──────────────────────────────────────────────────────────────────

describe('TKeyboard.insert', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard({ AutoHide: true });
		kb.forControl.value = 'abcde';
		kb.forControl.selectionStart = 2;
		kb.forControl.selectionEnd = 2;
	});

	it('inserts a character at the current selection', () => {
		kb.insert(kb.forControl, 'X');
		expect(kb.forControl.value).toBe('abXcde');
	});

	it('bksp deletes the character before the cursor', () => {
		kb.insert(kb.forControl, 'bksp');
		expect(kb.forControl.value).toBe('acde');
	});

	it('del deletes the character after the cursor', () => {
		kb.insert(kb.forControl, 'del');
		expect(kb.forControl.value).toBe('abde');
	});

	it('replaces a selected range with the typed character', () => {
		kb.forControl.selectionStart = 1;
		kb.forControl.selectionEnd = 3;
		kb.insert(kb.forControl, 'Z');
		expect(kb.forControl.value).toBe('aZde');
	});
});

// ─── forControl attachment ────────────────────────────────────────────────────

describe('TKeyboard forControl wiring', () => {
	it('attaches .keyboard back-reference to the forControl', () => {
		const kb = makeKeyboard();
		expect(kb.forControl.keyboard).toBe(kb);
	});

	it('attaches onfocus handler that calls show()', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'hidden';
		kb.forControl.onfocus();
		expect(kb.isShown()).toBe(true);
	});

	it('attaches onblur handler that hides when flagHover is false', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'visible';
		kb.flagHover = false;
		kb.forControl.onblur();
		expect(kb.isShown()).toBe(false);
	});

	it('onblur does not hide when flagHover is true', () => {
		const kb = makeKeyboard({ AutoHide: true });
		kb.tagKeyboard.style.visibility = 'visible';
		kb.flagHover = true;
		kb.forControl.onblur();
		expect(kb.isShown()).toBe(true);
	});
});

// ─── mouse event handlers ─────────────────────────────────────────────────────

describe('TKeyboard mouse event helpers', () => {
	let kb;

	beforeEach(() => {
		kb = makeKeyboard();
	});

	it('onmouseover adds Hover class', () => {
		const el = kb.tagKeyboard.querySelector('.Key1');
		el.onmouseover.call(el);
		expect(el.className).toContain('Hover');
	});

	it('onmouseout removes Hover and Active classes', () => {
		const el = kb.tagKeyboard.querySelector('.Key1');
		el.className += ' Hover Active';
		el.onmouseout.call(el);
		expect(el.className).not.toContain('Hover');
		expect(el.className).not.toContain('Active');
	});

	it('onmousedown adds Active class', () => {
		const el = kb.tagKeyboard.querySelector('.Key1');
		el.onmousedown.call(el);
		expect(el.className).toContain('Active');
	});

	it('onmouseup removes Active class', () => {
		const el = kb.tagKeyboard.querySelector('.Key1');
		el.className += ' Active';
		// onmouseup calls this.keyboard.type(this.innerHTML); provide a stub
		el.keyboard = kb;
		el.innerHTML = 'a';
		// set up forControl so type() won't throw
		kb.forControl.value = '';
		kb.forControl.selectionStart = 0;
		kb.forControl.selectionEnd = 0;
		el.onmouseup.call(el);
		expect(el.className).not.toContain('Active');
	});
});

// ─── null / missing ForControl ────────────────────────────────────────────────

describe('TKeyboard with no forControl', () => {
	it('does not throw when ForControl id does not exist in the DOM', () => {
		const id = 'kb-nocontrol-' + (++idCounter);
		const host = document.createElement('div');
		host.id = id;
		document.body.appendChild(host);

		expect(() => {
			new TKeyboard({
				ID: id,
				CssClass: 'Keyboard',
				ForControl: 'nonexistent-id',
				AutoHide: true,
			});
		}).not.toThrow();
	});
});
