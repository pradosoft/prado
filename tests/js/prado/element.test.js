/**
 * Tests for Prado.Element and Prado.Element.Selection.
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * DOM tests use the jsdom environment provided by Vitest.
 * ESM note: only tests/js/adapters/prado-core.js changes on ESM conversion.
 */

import { Element } from '../adapters/prado-core.js';

// ─── Prado.Element.createOptions ──────────────────────────────────────────────

describe('Prado.Element.createOptions', () => {
	it('returns empty array for empty input', () => {
		expect(Element.createOptions([])).toEqual([]);
	});

	it('creates flat <option> elements', () => {
		const opts = Element.createOptions([
			['Apple', 'apple'],
			['Banana', 'banana'],
		]);
		expect(opts).toHaveLength(2);
		expect(opts[0].tagName.toLowerCase()).toBe('option');
		expect(opts[0].text).toBe('Apple');
		expect(opts[0].value).toBe('apple');
		expect(opts[1].text).toBe('Banana');
		expect(opts[1].value).toBe('banana');
	});

	it('groups options under <optgroup> when a third array element is present', () => {
		const opts = Element.createOptions([
			['Red',   'red',   'Colors'],
			['Blue',  'blue',  'Colors'],
			['Volvo', 'volvo', 'Cars'],
		]);
		expect(opts).toHaveLength(2);
		expect(opts[0].tagName.toLowerCase()).toBe('optgroup');
		expect(opts[0].label).toBe('Colors');
		expect(opts[0].children).toHaveLength(2);
		expect(opts[1].tagName.toLowerCase()).toBe('optgroup');
		expect(opts[1].label).toBe('Cars');
		expect(opts[1].children).toHaveLength(1);
	});

	it('handles mixed grouped and ungrouped options', () => {
		const opts = Element.createOptions([
			['Plain', 'plain'],
			['Red',   'red',  'Colors'],
		]);
		// 'Plain' is a bare option; 'Colors' becomes an optgroup
		expect(opts).toHaveLength(2);
		expect(opts[0].tagName.toLowerCase()).toBe('option');
		expect(opts[1].tagName.toLowerCase()).toBe('optgroup');
	});
});

// ─── Prado.Element.setAttribute ───────────────────────────────────────────────

describe('Prado.Element.setAttribute', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('input');
		el.id   = 'test-setAttribute';
		el.type = 'text';
		document.body.appendChild(el);
	});

	afterEach(() => {
		document.body.removeChild(el);
	});

	it('sets a regular attribute by element id', () => {
		Element.setAttribute('test-setAttribute', 'placeholder', 'Enter text');
		expect(el.getAttribute('placeholder')).toBe('Enter text');
	});

	it('removes the "disabled" attribute when value is false', () => {
		el.setAttribute('disabled', '');
		Element.setAttribute('test-setAttribute', 'disabled', false);
		expect(el.hasAttribute('disabled')).toBe(false);
	});

	it('removes the "readonly" attribute when value is false', () => {
		el.setAttribute('readonly', '');
		Element.setAttribute('test-setAttribute', 'readonly', false);
		expect(el.hasAttribute('readonly')).toBe(false);
	});

	it('sets "disabled" attribute when value is truthy', () => {
		Element.setAttribute('test-setAttribute', 'disabled', true);
		expect(el.hasAttribute('disabled')).toBe(true);
	});
});

// ─── Prado.Element.removeAttribute ────────────────────────────────────────────

describe('Prado.Element.removeAttribute', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('button');
		el.id = 'test-removeAttribute';
		el.setAttribute('disabled', '');
		document.body.appendChild(el);
	});

	afterEach(() => {
		document.body.removeChild(el);
	});

	it('removes the named attribute from the element', () => {
		Element.removeAttribute('test-removeAttribute', 'disabled');
		expect(el.hasAttribute('disabled')).toBe(false);
	});

	it('is a no-op when the attribute does not exist', () => {
		expect(() => Element.removeAttribute('test-removeAttribute', 'data-nonexistent')).not.toThrow();
	});
});

// ─── Prado.Element.Selection ─────────────────────────────────────────────────

const Selection = Element.Selection;

describe('Prado.Element.Selection.isSelectable', () => {
	it.each([
		['checkbox'],
		['radio'],
		['select'],
		['select-multiple'],
		['select-one'],
	])('returns true for type "%s"', (type) => {
		expect(Selection.isSelectable({ type })).toBe(true);
	});

	it('returns false for text input', () => {
		expect(Selection.isSelectable({ type: 'text' })).toBe(false);
	});

	it('returns false for null', () => {
		expect(Selection.isSelectable(null)).toBe(false);
	});

	it('returns false for element without a type property', () => {
		expect(Selection.isSelectable({})).toBe(false);
	});
});

describe('Prado.Element.Selection.inputValue', () => {
	it('sets checked = true on a checkbox', () => {
		const el = { type: 'checkbox', checked: false };
		Selection.inputValue(el, true);
		expect(el.checked).toBe(true);
	});

	it('sets checked = false on a checkbox', () => {
		const el = { type: 'checkbox', checked: true };
		Selection.inputValue(el, false);
		expect(el.checked).toBe(false);
	});

	it('sets checked on a radio button', () => {
		const el = { type: 'radio', checked: false };
		Selection.inputValue(el, true);
		expect(el.checked).toBe(true);
	});
});

describe('Prado.Element.Selection.selectValue', () => {
	function makeSelect(values, multiple = false) {
		const sel = document.createElement('select');
		if (multiple) {
			sel.setAttribute('multiple', '');
		}
		values.forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		return sel;
	}

	it('selects the option whose value matches', () => {
		const sel = makeSelect(['a', 'b', 'c']);
		Selection.selectValue([sel], 'b');
		expect(sel.options[1].selected).toBe(true);
		expect(sel.options[0].selected).toBe(false);
		expect(sel.options[2].selected).toBe(false);
	});

	it('sets all options when value is a boolean true', () => {
		const sel = makeSelect(['a', 'b'], true);
		Selection.selectValue([sel], true);
		expect(sel.options[0].selected).toBe(true);
		expect(sel.options[1].selected).toBe(true);
	});

	it('deselects all options when value is a boolean false', () => {
		const sel = makeSelect(['a', 'b'], true);
		sel.options[0].selected = true;
		Selection.selectValue([sel], false);
		expect(sel.options[0].selected).toBe(false);
		expect(sel.options[1].selected).toBe(false);
	});
});

describe('Prado.Element.Selection.selectAll', () => {
	it('selects every option in a multi-select', () => {
		const sel = document.createElement('select');
		sel.setAttribute('multiple', '');
		['a', 'b', 'c'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		Selection.selectAll([sel]);
		Array.from(sel.options).forEach((opt) => expect(opt.selected).toBe(true));
	});
});

describe('Prado.Element.Selection.selectInvert', () => {
	it('toggles each option selection state', () => {
		const sel = document.createElement('select');
		sel.setAttribute('multiple', '');
		['a', 'b'].forEach((v, i) => {
			const opt = document.createElement('option');
			opt.value    = v;
			opt.selected = i === 0; // only first selected
			sel.appendChild(opt);
		});
		Selection.selectInvert([sel]);
		expect(sel.options[0].selected).toBe(false);
		expect(sel.options[1].selected).toBe(true);
	});
});

describe('Prado.Element.Selection.selectIndex', () => {
	it('selects option at the given index in a select-one', () => {
		const sel = document.createElement('select');
		['a', 'b', 'c'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		Selection.selectIndex([sel], 2);
		expect(sel.selectedIndex).toBe(2);
	});
});

describe('Prado.Element.Selection.selectClear', () => {
	it('sets selectedIndex to -1', () => {
		const sel = document.createElement('select');
		sel.setAttribute('multiple', '');
		['a', 'b'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			opt.selected = true;
			sel.appendChild(opt);
		});
		Selection.selectClear([sel]);
		expect(sel.selectedIndex).toBe(-1);
	});
});

describe('Prado.Element.Selection.checkValue', () => {
	it('checks the element whose value matches', () => {
		const boxes = [
			{ value: 'x', checked: false },
			{ value: 'y', checked: false },
		];
		Selection.checkValue(boxes, 'y');
		expect(boxes[0].checked).toBe(false);
		expect(boxes[1].checked).toBe(true);
	});

	it('sets all elements when value is boolean true', () => {
		const boxes = [{ checked: false }, { checked: false }];
		Selection.checkValue(boxes, true);
		expect(boxes[0].checked).toBe(true);
		expect(boxes[1].checked).toBe(true);
	});

	it('clears all elements when value is boolean false', () => {
		const boxes = [{ checked: true }, { checked: true }];
		Selection.checkValue(boxes, false);
		expect(boxes[0].checked).toBe(false);
		expect(boxes[1].checked).toBe(false);
	});
});

describe('Prado.Element.Selection.checkAll', () => {
	it('marks every element as checked', () => {
		const boxes = [{ checked: false }, { checked: false }];
		Selection.checkAll(boxes);
		boxes.forEach((b) => expect(b.checked).toBe(true));
	});
});

describe('Prado.Element.Selection.checkClear', () => {
	it('unchecks every element', () => {
		const boxes = [{ checked: true }, { checked: true }];
		Selection.checkClear(boxes);
		boxes.forEach((b) => expect(b.checked).toBe(false));
	});
});

describe('Prado.Element.Selection.checkInvert', () => {
	it('inverts each element checked state', () => {
		const boxes = [{ checked: true }, { checked: false }];
		Selection.checkInvert(boxes);
		expect(boxes[0].checked).toBe(false);
		expect(boxes[1].checked).toBe(true);
	});
});

describe('Prado.Element.Selection.checkIndex', () => {
	it('checks only the element at the given index', () => {
		const boxes = [{ checked: false }, { checked: false }, { checked: false }];
		Selection.checkIndex(boxes, 1);
		expect(boxes[0].checked).toBe(false);
		expect(boxes[1].checked).toBe(true);
		expect(boxes[2].checked).toBe(false);
	});
});
