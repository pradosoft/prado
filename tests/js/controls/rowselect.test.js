/**
 * Tests for Prado.WebUI.TRowSelectColumn (rowselect.js).
 * Source: framework/Web/Javascripts/source/prado/controls/rowselect.js
 *
 * DOM structure expected by TRowSelectColumn: a select-all checkbox (the wrapper
 * ID) and the row checkboxes listed in `CheckBoxIDs`.
 */

import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { TRowSelectColumn } from '../adapters/rowselect.js';

function buildControl(rows = 4) {
	let html = '<input type="checkbox" id="all">';
	const ids = [];
	for (let i = 0; i < rows; i++) {
		html += `<input type="checkbox" id="row${i}">`;
		ids.push(`row${i}`);
	}
	document.body.innerHTML = html;
	return new TRowSelectColumn({ ID: 'all', CheckBoxIDs: ids });
}

function box(id) {
	return document.getElementById(id);
}

function checkedRows() {
	return [...document.querySelectorAll('input[id^="row"]')].filter((b) => b.checked).map((b) => b.id);
}

function click(element, init = {}) {
	element.dispatchEvent(new MouseEvent('click', Object.assign({ bubbles: true, cancelable: true }, init)));
}

beforeEach(() => {
	document.body.innerHTML = '';
	global.Prado.Registry = {};
});

afterEach(() => {
	document.body.innerHTML = '';
});

describe('TRowSelectColumn', () => {
	it('registers under the select-all checkbox ID', () => {
		const column = buildControl();
		expect(global.Prado.Registry.all).toBe(column);
		expect(column.boxes).toHaveLength(4);
	});

	it('skips missing row checkboxes', () => {
		document.body.innerHTML = '<input type="checkbox" id="all"><input type="checkbox" id="row0">';
		const column = new TRowSelectColumn({ ID: 'all', CheckBoxIDs: ['row0', 'missing'] });
		expect(column.boxes).toHaveLength(1);
	});

	it('checks and clears every row from the select-all checkbox', () => {
		buildControl();
		click(box('all'));
		expect(checkedRows()).toEqual(['row0', 'row1', 'row2', 'row3']);
		click(box('all'));
		expect(checkedRows()).toEqual([]);
	});

	it('leaves disabled rows unchanged', () => {
		buildControl();
		box('row1').disabled = true;
		click(box('all'));
		expect(checkedRows()).toEqual(['row0', 'row2', 'row3']);
	});

	it('shows the select-all checkbox mixed when some rows are checked', () => {
		buildControl();
		click(box('row0'));
		expect(box('all').checked).toBe(false);
		expect(box('all').indeterminate).toBe(true);
	});

	it('shows the select-all checkbox checked when every row is checked', () => {
		buildControl(2);
		click(box('row0'));
		click(box('row1'));
		expect(box('all').checked).toBe(true);
		expect(box('all').indeterminate).toBe(false);
	});

	it('reflects rows checked before initialization', () => {
		document.body.innerHTML = '<input type="checkbox" id="all"><input type="checkbox" id="row0" checked><input type="checkbox" id="row1">';
		new TRowSelectColumn({ ID: 'all', CheckBoxIDs: ['row0', 'row1'] });
		expect(box('all').indeterminate).toBe(true);
	});

	it('sets the range of rows on a shift-click', () => {
		buildControl(5);
		click(box('row1'));
		click(box('row4'), { shiftKey: true });
		expect(checkedRows()).toEqual(['row1', 'row2', 'row3', 'row4']);
	});

	it('clears a range on a shift-click that clears a row', () => {
		buildControl(4);
		click(box('all'));
		click(box('row3'));
		click(box('row0'), { shiftKey: true });
		expect(checkedRows()).toEqual([]);
	});

	it('sets only the clicked row on a shift-click without a previous click', () => {
		buildControl(3);
		click(box('row2'), { shiftKey: true });
		expect(checkedRows()).toEqual(['row2']);
	});

	it('selectAll() and selectedBoxes()', () => {
		const column = buildControl(3);
		column.selectAll(true);
		expect(column.selectedBoxes().map((b) => b.id)).toEqual(['row0', 'row1', 'row2']);
		expect(box('all').checked).toBe(true);
	});
});
