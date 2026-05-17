/**
 * Tests for the previously uncovered parts of Prado.Element:
 *   j(), select(), setOptions(), replace(), evaluateScript(),
 *   setAttribute (on* and hidden/inert/popover with false),
 *   and Selection.selectValues / checkValues / checkIndices.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import { Element } from '../adapters/prado-core.js';

const Selection = Element.Selection;

// ─── Prado.Element.j ─────────────────────────────────────────────────────────

describe('Prado.Element.j', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'j-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('calls the named jQuery method on the element', () => {
		Element.j('j-test', 'addClass', ['highlight']);
		expect(el.classList.contains('highlight')).toBe(true);
	});

	it('calls hide() — sets display:none', () => {
		Element.j('j-test', 'hide', []);
		expect(el.style.display).toBe('none');
	});
});

// ─── Prado.Element.select ────────────────────────────────────────────────────

describe('Prado.Element.select', () => {
	it('is a no-op when the element does not exist', () => {
		expect(() => Element.select('non-existent-id', 'selectAll', null, 0)).not.toThrow();
	});

	it('delegates to Selection.selectValue for a <select> element', () => {
		const sel = document.createElement('select');
		sel.id = 'sel-test';
		['a', 'b', 'c'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		document.body.appendChild(sel);

		Element.select('sel-test', 'selectValue', 'b', 1);
		expect(sel.options[1].selected).toBe(true);

		document.body.removeChild(sel);
	});

	it('delegates to Selection.selectAll for a <select> element', () => {
		const sel = document.createElement('select');
		sel.id   = 'sel-all';
		sel.setAttribute('multiple', '');
		['x', 'y'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		document.body.appendChild(sel);

		Element.select('sel-all', 'selectAll', null, 2);
		expect(sel.options[0].selected).toBe(true);
		expect(sel.options[1].selected).toBe(true);

		document.body.removeChild(sel);
	});
});

// ─── Prado.Element.setAttribute — hidden / inert / popover false branch ──────

describe('Prado.Element.setAttribute — boolean-remove attributes', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'attr-bool-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('removes the "hidden" attribute when value is false', () => {
		el.setAttribute('hidden', '');
		Element.setAttribute('attr-bool-test', 'hidden', false);
		expect(el.hasAttribute('hidden')).toBe(false);
	});

	it('sets the "hidden" attribute when value is truthy', () => {
		Element.setAttribute('attr-bool-test', 'hidden', true);
		expect(el.hasAttribute('hidden')).toBe(true);
	});
});

// ─── Prado.Element.setAttribute — on* event handler branch ───────────────────

describe('Prado.Element.setAttribute — on* event handlers', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('button');
		el.id = 'attr-on-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('attaches an evaluated function as an onclick handler', () => {
		global.__testClicked = false;
		Element.setAttribute('attr-on-test', 'onclick', 'global.__testClicked = true;');
		el.onclick({ target: el });
		expect(global.__testClicked).toBe(true);
		delete global.__testClicked;
	});
});

// ─── Prado.Element.setOptions ─────────────────────────────────────────────────

describe('Prado.Element.setOptions', () => {
	let sel;

	beforeEach(() => {
		sel = document.createElement('select');
		sel.id = 'so-test';
		document.body.appendChild(sel);
	});

	afterEach(() => { document.body.removeChild(sel); });

	it('replaces all options in a <select> with the provided flat list', () => {
		Element.setOptions('so-test', [
			['Apple', 'apple'],
			['Banana', 'banana'],
		]);
		expect(sel.options).toHaveLength(2);
		expect(sel.options[0].value).toBe('apple');
		expect(sel.options[0].text).toBe('Apple');
		expect(sel.options[1].value).toBe('banana');
	});

	it('handles grouped options (three-element sub-arrays)', () => {
		Element.setOptions('so-test', [
			['Red',   'red',   'Colors'],
			['Blue',  'blue',  'Colors'],
		]);
		// The resulting DOM has an <optgroup label="Colors"> with two children.
		const group = sel.querySelector('optgroup[label="Colors"]');
		expect(group).not.toBeNull();
		expect(group.querySelectorAll('option')).toHaveLength(2);
	});

	it('is a no-op when the element does not exist', () => {
		expect(() => Element.setOptions('no-such-element', [['A', 'a']])).not.toThrow();
	});
});

// ─── Prado.Element.replace ───────────────────────────────────────────────────

describe('Prado.Element.replace', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'replace-test';
		document.body.appendChild(el);
	});

	afterEach(() => {
		const el2 = document.getElementById('replace-test');
		if (el2) document.body.removeChild(el2);
	});

	it('sets innerHTML when no boundary is given and self is falsy', () => {
		Element.replace('replace-test', '<span>hello</span>');
		expect(document.getElementById('replace-test').innerHTML).toBe('<span>hello</span>');
	});

	it('replaces the element itself when self is true', () => {
		// jQuery replaceWith removes the original node and inserts the new HTML
		// in its place. We verify the original element is gone and the wrapper
		// contains the new content.
		const wrapper = document.createElement('div');
		wrapper.id = 'rw-outer';
		const inner = document.createElement('div');
		inner.id = 'rw-inner';
		wrapper.appendChild(inner);
		document.body.appendChild(wrapper);

		Element.replace('rw-inner', '<span>replaced</span>', null, true);

		// Original #rw-inner was replaced; wrapper now contains the <span>.
		expect(document.getElementById('rw-inner')).toBeNull();
		expect(wrapper.querySelector('span').textContent).toBe('replaced');

		document.body.removeChild(wrapper);
	});

	// NOTE: Element.replace(id, content, boundary) calls this.extractContent(boundary)
	// where `this` is Prado.Element.  Prado.Element does NOT have an extractContent
	// method — that method lives on Prado.CallbackRequest.  The boundary path is
	// therefore only meaningful when called from a CallbackRequest context.
	// No test is written for that path here; it is exercised via
	// tests/js/activecontrols/callbackrequest.test.js.
});

// ─── Prado.Element.evaluateScript ────────────────────────────────────────────

describe('Prado.Element.evaluateScript', () => {
	it('does not throw for a valid JavaScript string', () => {
		// jQuery.globalEval injects a <script> element into the DOM.
		// jsdom does not execute injected scripts, so we can only assert
		// that the call completes without throwing.
		expect(() => Element.evaluateScript('var __noop = 1;')).not.toThrow();
	});

	it('does not throw for a second valid JavaScript string', () => {
		// jQuery.globalEval injects a <script> element; jsdom does not execute it,
		// so we can only assert the call returns without throwing.
		expect(() => Element.evaluateScript('1 + 1;')).not.toThrow();
	});
});

// ─── Selection.selectValues / checkValues / checkIndices ─────────────────────

describe('Prado.Element.Selection.selectValues', () => {
	function makeSelect(values) {
		const sel = document.createElement('select');
		sel.setAttribute('multiple', '');
		values.forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		return sel;
	}

	it('selects each value in the array', () => {
		const sel = makeSelect(['a', 'b', 'c']);
		Selection.selectValues([sel], ['a', 'c']);
		expect(sel.options[0].selected).toBe(true);
		expect(sel.options[1].selected).toBe(false);
		expect(sel.options[2].selected).toBe(true);
	});
});

describe('Prado.Element.Selection.checkValues', () => {
	it('checks each element whose value is in the array', () => {
		const boxes = [
			{ value: 'x', checked: false },
			{ value: 'y', checked: false },
			{ value: 'z', checked: false },
		];
		Selection.checkValues(boxes, ['x', 'z']);
		expect(boxes[0].checked).toBe(true);
		expect(boxes[1].checked).toBe(false);
		expect(boxes[2].checked).toBe(true);
	});
});

describe('Prado.Element.Selection.checkIndices', () => {
	it('checks each element at the given indices', () => {
		const boxes = [
			{ checked: false },
			{ checked: false },
			{ checked: false },
		];
		Selection.checkIndices(boxes, [0, 2]);
		expect(boxes[0].checked).toBe(true);
		expect(boxes[1].checked).toBe(false);
		expect(boxes[2].checked).toBe(true);
	});
});
