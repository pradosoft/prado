/**
 * Tests for Prado.WebUI.TDrawer (drawer.js).
 * Source: framework/Web/Javascripts/source/prado/controls/drawer.js
 *
 * Strategy
 * --------
 * jsdom has no `matchMedia`, no `:modal`, and no dialog methods, so the tests stub
 * them on the element: `show()`, `showModal()`, `close()`, and `requestClose()`
 * track the `open` attribute and a modal flag. Touch gestures are plain events
 * with `touches` assigned, and `timeStamp` is read from the event.
 *
 * ESM note: only tests/js/adapters/drawer.js changes on ESM conversion.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TDrawer } from '../adapters/drawer.js';

let control;
let drawer;
let media;

function build(body = '<p id="inner">Body</p>') {
	document.body.innerHTML = `<dialog id="nav">${body}</dialog>`;
	drawer = document.getElementById('nav');
	drawer.modal = false;
	drawer.show = vi.fn(() => drawer.setAttribute('open', ''));
	drawer.showModal = vi.fn(() => { drawer.setAttribute('open', ''); drawer.modal = true; });
	drawer.close = vi.fn(() => { drawer.removeAttribute('open'); drawer.modal = false; });
	drawer.requestClose = vi.fn(() => drawer.close());
	const matches = drawer.matches.bind(drawer);
	drawer.matches = (selector) => (selector === ':modal' ? drawer.modal : matches(selector));
	Object.defineProperty(drawer, 'open', { configurable: true, get: () => drawer.hasAttribute('open') });
	drawer.getBoundingClientRect = () => ({ left: 0, top: 0, right: 300, bottom: 600, width: 300, height: 600 });
}

function create(options = {}) {
	control = new TDrawer({ ID: 'nav', Placement: 'start', ClosedBy: 'any', Swipe: true, ...options });
	return control;
}

function stubMedia(matches) {
	media = { matches, listeners: [], addEventListener(type, fn) { this.listeners.push(fn); }, removeEventListener(type, fn) { this.listeners = this.listeners.filter((l) => l !== fn); } };
	vi.stubGlobal('matchMedia', vi.fn(() => media));
}

function touch(el, type, x, y, timeStamp = 0) {
	const event = new Event(type, { bubbles: true, cancelable: true });
	event.touches = type === 'touchend' || type === 'touchcancel' ? [] : [{ clientX: x, clientY: y }];
	Object.defineProperty(event, 'timeStamp', { value: timeStamp });
	el.dispatchEvent(event);
	return event;
}

/** Drags slowly from one point to another and lifts the finger. */
function drag(el, from, to) {
	touch(el, 'touchstart', from[0], from[1], 0);
	const move = touch(el, 'touchmove', to[0], to[1], 2000);
	touch(el, 'touchend', to[0], to[1], 4000);
	return move;
}

beforeEach(() => {
	build();
	control = null;
});

afterEach(() => {
	if (control && control.registered)
		control.deinitialize();
	vi.unstubAllGlobals();
	document.body.innerHTML = '';
});

describe('Prado.WebUI.TDrawer dock', () => {
	it('stays open and takes no focus when wide, with no closedby', () => {
		stubMedia(true);
		drawer.setAttribute('open', '');
		drawer.setAttribute('closedby', 'any');
		create({ DockAt: '64rem' });
		expect(matchMedia).toHaveBeenCalledWith('(min-width: 64rem)');
		expect(drawer.show).not.toHaveBeenCalled();
		expect(drawer.close).not.toHaveBeenCalled();
		expect(drawer.hasAttribute('closedby')).toBe(false);
	});

	it('closes when narrow and applies closedby', () => {
		stubMedia(false);
		drawer.setAttribute('open', '');
		create({ DockAt: '64rem' });
		expect(drawer.open).toBe(false);
		expect(drawer.getAttribute('closedby')).toBe('any');
	});

	it('leaves a modal drawer open when narrow', () => {
		stubMedia(false);
		drawer.showModal();
		create({ DockAt: '64rem' });
		expect(drawer.close).not.toHaveBeenCalled();
	});

	it('docks a modal drawer when the width grows, and closes it when it shrinks', () => {
		stubMedia(false);
		create({ DockAt: '64rem' });
		drawer.showModal();
		media.matches = true;
		media.listeners[0]();
		expect(drawer.modal).toBe(false);
		expect(drawer.show).toHaveBeenCalledTimes(1);
		expect(drawer.open).toBe(true);
		expect(drawer.hasAttribute('closedby')).toBe(false);

		media.matches = false;
		media.listeners[0]();
		expect(drawer.open).toBe(false);
		expect(drawer.getAttribute('closedby')).toBe('any');
	});

	it('removes the media listener when deinitialized', () => {
		stubMedia(true);
		create({ DockAt: '64rem' });
		expect(media.listeners).toHaveLength(1);
		control.deinitialize();
		expect(media.listeners).toHaveLength(0);
	});

	it('does not use matchMedia without DockAt', () => {
		stubMedia(true);
		create();
		expect(matchMedia).not.toHaveBeenCalled();
	});
});

describe('Prado.WebUI.TDrawer swipe', () => {
	beforeEach(() => drawer.showModal());

	it('drags with the finger and closes past a third of its size', () => {
		create();
		touch(drawer, 'touchstart', 200, 100, 0);
		const move = touch(drawer, 'touchmove', 50, 100, 2000);
		expect(move.defaultPrevented).toBe(true);
		expect(drawer.style.transform).toBe('translateX(-150px)');
		touch(drawer, 'touchend', 50, 100, 4000);
		expect(drawer.requestClose).toHaveBeenCalledTimes(1);
		expect(drawer.style.transform).toBe('');
		expect(drawer.style.transition).toBe('');
	});

	it('snaps back under a third at low speed', () => {
		create();
		drag(drawer, [200, 100], [150, 100]);
		expect(drawer.requestClose).not.toHaveBeenCalled();
		expect(drawer.style.transform).toBe('');
	});

	it('closes a short swipe at speed', () => {
		create();
		touch(drawer, 'touchstart', 200, 100, 0);
		touch(drawer, 'touchmove', 150, 100, 20);
		touch(drawer, 'touchend', 150, 100, 40);
		expect(drawer.requestClose).toHaveBeenCalledTimes(1);
	});

	it('does not drag past the open position', () => {
		create();
		touch(drawer, 'touchstart', 100, 100, 0);
		touch(drawer, 'touchmove', 60, 100, 100);
		touch(drawer, 'touchmove', 120, 100, 200);
		expect(drawer.style.transform).toBe('translateX(0px)');
	});

	it('leaves a swipe away from the edge to the browser', () => {
		create();
		const move = drag(drawer, [100, 100], [200, 100]);
		expect(move.defaultPrevented).toBe(false);
		expect(drawer.requestClose).not.toHaveBeenCalled();
	});

	it('leaves a mostly vertical gesture on a side drawer to the browser', () => {
		create();
		const move = drag(drawer, [200, 100], [170, 300]);
		expect(move.defaultPrevented).toBe(false);
		expect(drawer.requestClose).not.toHaveBeenCalled();
	});

	it('ignores movement within the slop', () => {
		create();
		touch(drawer, 'touchstart', 200, 100, 0);
		expect(touch(drawer, 'touchmove', 195, 100, 10).defaultPrevented).toBe(false);
		expect(drawer.style.transform).toBe('');
	});

	it('follows each placement', () => {
		for (const [placement, from, to, transform] of [
			['end', [50, 100], [200, 100], 'translateX(150px)'],
			['top', [100, 300], [100, 100], 'translateY(-200px)'],
			['bottom', [100, 100], [100, 300], 'translateY(200px)'],
		]) {
			if (control)
				control.deinitialize();
			build();
			drawer.showModal();
			create({ Placement: placement });
			touch(drawer, 'touchstart', from[0], from[1], 0);
			touch(drawer, 'touchmove', to[0], to[1], 1000);
			expect(drawer.style.transform).toBe(transform);
		}
	});

	it('reverses Start in a right-to-left page', () => {
		drawer.setAttribute('dir', 'rtl');
		drawer.style.direction = 'rtl';
		create();
		touch(drawer, 'touchstart', 50, 100, 0);
		touch(drawer, 'touchmove', 200, 100, 1000);
		expect(drawer.style.transform).toBe('translateX(150px)');
	});

	it('closes a bottom sheet on a downward swipe only when it is scrolled to the top', () => {
		build('<div id="list" style="overflow-y: auto"><p id="row">Row</p></div>');
		drawer.showModal();
		create({ Placement: 'bottom' });
		const list = document.getElementById('list');
		Object.defineProperty(list, 'scrollHeight', { value: 500 });
		Object.defineProperty(list, 'clientHeight', { value: 100 });
		list.scrollTop = 80;
		const row = document.getElementById('row');
		expect(drag(row, [100, 100], [100, 300]).defaultPrevented).toBe(false);
		expect(drawer.requestClose).not.toHaveBeenCalled();

		list.scrollTop = 0;
		expect(drag(row, [100, 100], [100, 400]).defaultPrevented).toBe(true);
		expect(drawer.requestClose).toHaveBeenCalledTimes(1);
	});

	it('closes directly where requestClose is missing', () => {
		drawer.requestClose = undefined;
		create();
		drag(drawer, [250, 100], [50, 100]);
		expect(drawer.close).toHaveBeenCalledTimes(1);
	});

	it('restores the drawer on touchcancel', () => {
		create();
		touch(drawer, 'touchstart', 200, 100, 0);
		touch(drawer, 'touchmove', 100, 100, 100);
		touch(drawer, 'touchcancel', 0, 0, 200);
		expect(drawer.style.transform).toBe('');
		expect(drawer.requestClose).not.toHaveBeenCalled();
	});

	it('ignores a second finger', () => {
		create();
		const start = new Event('touchstart', { bubbles: true });
		start.touches = [{ clientX: 1, clientY: 1 }, { clientX: 2, clientY: 2 }];
		drawer.dispatchEvent(start);
		expect(control.swipe).toBeNull();
	});

	it('does not swipe when docked', () => {
		stubMedia(true);
		create({ DockAt: '64rem' });
		const move = drag(drawer, [200, 100], [50, 100]);
		expect(move.defaultPrevented).toBe(false);
		expect(drawer.requestClose).not.toHaveBeenCalled();
	});

	it('does not listen with Swipe off', () => {
		create({ Swipe: false });
		const move = drag(drawer, [200, 100], [50, 100]);
		expect(move.defaultPrevented).toBe(false);
	});
});

describe('Prado.WebUI.TDrawer backdrop fallback', () => {
	const click = (target, x, y) => target.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: x, clientY: y }));

	beforeEach(() => {
		drawer.showModal();
	});

	it('closes on a click outside the box where closedby is missing', () => {
		delete HTMLDialogElement.prototype.closedBy;
		create();
		click(drawer, 500, 100);
		expect(drawer.close).toHaveBeenCalledTimes(1);
	});

	it('stays open on a click inside the box or on its content', () => {
		create();
		click(drawer, 100, 100);
		click(document.getElementById('inner'), 500, 100);
		expect(drawer.close).not.toHaveBeenCalled();
	});

	it('does nothing when the browser supports closedby', () => {
		HTMLDialogElement.prototype.closedBy = '';
		try {
			create();
			click(drawer, 500, 100);
			expect(drawer.close).not.toHaveBeenCalled();
		} finally {
			delete HTMLDialogElement.prototype.closedBy;
		}
	});

	it('keeps a ClosedBy None drawer open on a close request where closedby is missing', () => {
		create({ ClosedBy: 'none' });
		const cancel = new Event('cancel', { cancelable: true });
		drawer.dispatchEvent(cancel);
		expect(cancel.defaultPrevented).toBe(true);
	});

	it('does nothing unless ClosedBy is Any', () => {
		create({ ClosedBy: 'closerequest' });
		click(drawer, 500, 100);
		expect(drawer.close).not.toHaveBeenCalled();
	});
});
