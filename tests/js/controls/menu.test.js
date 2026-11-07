/**
 * Tests for Prado.WebUI.TDropdownMenu (menu.js).
 * Source: framework/Web/Javascripts/source/prado/controls/menu.js
 *
 * Strategy
 * --------
 * jsdom has no Popover API, so the menu element gets stand-in `showPopover()`,
 * `hidePopover()`, and `matches(':popover-open')`. `showPopover()` dispatches the
 * `toggle` event with `newState: 'open'`, as the browser does after opening.
 *
 * ESM note: only tests/js/adapters/menu.js changes on ESM conversion.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TDropdownMenu } from '../adapters/menu.js';

let control;
let menu;
let button;

function build(body) {
	document.body.innerHTML =
		'<button id="actions_button" commandfor="actions" command="toggle-popover">Actions</button>' +
		`<div id="actions" popover="auto">${body}</div>` +
		'<button id="other" commandfor="actions" command="toggle-popover">Other</button>';
	menu = document.getElementById('actions');
	button = document.getElementById('actions_button');
	menu.open = false;
	menu.showPopover = vi.fn((options) => {
		menu.open = true;
		menu.source = options?.source ?? null;
		const event = new Event('toggle');
		event.newState = 'open';
		menu.dispatchEvent(event);
	});
	menu.hidePopover = vi.fn(() => { menu.open = false; });
	const matches = menu.matches.bind(menu);
	menu.matches = (selector) => (selector === ':popover-open' ? menu.open : matches(selector));
}

const ITEMS = '<ul><li><button id="dup">Duplicate</button></li><li><button id="arc">Archive</button></li>' +
	'<li><button id="del" disabled>Delete</button></li></ul><hr><a id="exp" href="#export">Export</a>';

const key = (el, k, options = {}) => {
	const event = new KeyboardEvent('keydown', { key: k, bubbles: true, cancelable: true, ...options });
	el.dispatchEvent(event);
	return event;
};
const focused = () => document.activeElement.id;

beforeEach(() => {
	build(ITEMS);
	control = new TDropdownMenu({ ID: 'actions' });
});

afterEach(() => {
	if (control && control.registered)
		control.deinitialize();
	document.body.innerHTML = '';
});

describe('Prado.WebUI.TDropdownMenu', () => {
	it('gives the menu, its items, and the lists their roles', () => {
		expect(menu.getAttribute('role')).toBe('menu');
		expect(control.items.map((el) => el.id)).toEqual(['dup', 'arc', 'exp']);
		for (const id of ['dup', 'arc', 'exp']) {
			const item = document.getElementById(id);
			expect(item.getAttribute('role')).toBe('menuitem');
			expect(item.getAttribute('tabindex')).toBe('-1');
		}
		expect(document.getElementById('del').hasAttribute('role')).toBe(false);
		expect(menu.querySelector('ul').getAttribute('role')).toBe('none');
		expect(menu.querySelector('li').getAttribute('role')).toBe('none');
	});

	it('marks every invoker as a menu button', () => {
		expect(button.getAttribute('aria-haspopup')).toBe('menu');
		expect(document.getElementById('other').getAttribute('aria-haspopup')).toBe('menu');
	});

	it('keeps an explicit menu item role', () => {
		build('<button id="bold" role="menuitemcheckbox" aria-checked="false">Bold</button>');
		control.deinitialize();
		control = new TDropdownMenu({ ID: 'actions' });
		expect(document.getElementById('bold').getAttribute('role')).toBe('menuitemcheckbox');
	});

	it('focuses the first item when the menu opens', () => {
		menu.showPopover({ source: button });
		expect(focused()).toBe('dup');
	});

	it('opens on the first item with Down Arrow and the last with Up Arrow', () => {
		expect(key(button, 'ArrowDown').defaultPrevented).toBe(true);
		expect(menu.source).toBe(button);
		expect(focused()).toBe('dup');
		menu.open = false;
		key(button, 'ArrowUp');
		expect(focused()).toBe('exp');
	});

	it('moves between the items with the arrows, wrapping, and Home and End', () => {
		menu.showPopover({ source: button });
		key(document.activeElement, 'ArrowDown');
		expect(focused()).toBe('arc');
		key(document.activeElement, 'ArrowDown');
		expect(focused()).toBe('exp');
		key(document.activeElement, 'ArrowDown');
		expect(focused()).toBe('dup');
		key(document.activeElement, 'ArrowUp');
		expect(focused()).toBe('exp');
		key(document.activeElement, 'Home');
		expect(focused()).toBe('dup');
		key(document.activeElement, 'End');
		expect(focused()).toBe('exp');
	});

	it('moves to the next item that starts with a typed character', () => {
		menu.showPopover({ source: button });
		key(document.activeElement, 'e');
		expect(focused()).toBe('exp');
		key(document.activeElement, 'a');
		expect(focused()).toBe('arc');
		const event = key(document.activeElement, 'z');
		expect(event.defaultPrevented).toBe(false);
		expect(focused()).toBe('arc');
	});

	it('leaves modified characters alone', () => {
		menu.showPopover({ source: button });
		expect(key(document.activeElement, 'a', { ctrlKey: true }).defaultPrevented).toBe(false);
		expect(focused()).toBe('dup');
	});

	it('closes on Tab without preventing it', () => {
		menu.showPopover({ source: button });
		expect(key(document.activeElement, 'Tab').defaultPrevented).toBe(false);
		expect(menu.hidePopover).toHaveBeenCalled();
	});

	it('closes when an item is activated', () => {
		menu.showPopover({ source: button });
		document.getElementById('arc').click();
		expect(menu.open).toBe(false);
	});

	it('stays open on a click that is not on an item', () => {
		menu.showPopover({ source: button });
		menu.querySelector('hr').click();
		expect(menu.open).toBe(true);
	});
});
