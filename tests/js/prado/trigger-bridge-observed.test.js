/**
 * Tests for the jQuery.fn.trigger bridge on elements that a Prado control
 * wrapper observes without being registered under their id, and for the
 * Prado.Observed record that Prado.WebUI.Control maintains.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *         framework/Web/Javascripts/source/prado/controls/controls.js
 *
 * A validator observes its ControlToValidate, a TEventTriggeredCallback its
 * ControlID, and a TDatePicker its day/month/year selects. Each wrapper is
 * registered under its own id, so `jQuery(control).trigger('change')` must
 * reach the native listener through Prado.Observed.
 */

import { Control } from '../adapters/controls.js';

// A wrapper registered under its own id that observes another element.
const Watcher = Prado.Class(Control, {
	onInit(options) {
		this.handler = vi.fn();
		this.observe(options.Target, 'change', this.handler);
	},
});

function makeEl(id, tag = 'input') {
	const el = document.createElement(tag);
	el.id = id;
	document.body.appendChild(el);
	return el;
}

afterEach(() => {
	for (const k of Object.keys(Prado.Registry)) delete Prado.Registry[k];
	document.body.innerHTML = '';
});

describe('jQuery.fn.trigger bridge — observed elements', () => {
	it('reaches the observer of an element registered under another id', () => {
		makeEl('watcher', 'span');
		const target = makeEl('plain');
		const watcher = new Watcher({ ID: 'watcher', Target: 'plain' });

		jQuery(target).trigger('change');

		expect(watcher.handler).toHaveBeenCalledTimes(1);
	});

	it('reaches the observer through Prado.Element.j, the path of raiseClientEvent', () => {
		makeEl('watcher', 'span');
		makeEl('plain');
		const watcher = new Watcher({ ID: 'watcher', Target: 'plain' });

		Prado.Element.j('plain', 'trigger', ['change']);

		expect(watcher.handler).toHaveBeenCalledTimes(1);
	});

	it('fires a jQuery-bound handler on an observed element once', () => {
		makeEl('watcher', 'span');
		const target = makeEl('plain');
		new Watcher({ ID: 'watcher', Target: 'plain' });
		const jq = vi.fn();
		jQuery(target).on('change', jq);

		jQuery(target).trigger('change');

		expect(jq).toHaveBeenCalledTimes(1);
	});

	it('leaves an element without observers on the jQuery trigger path', () => {
		const target = makeEl('plain');
		const native = vi.fn();
		target.addEventListener('change', native);

		jQuery(target).trigger('change');

		expect(Prado.Observed.has(target)).toBe(false);
		expect(native).not.toHaveBeenCalled();
	});
});

describe('Prado.Observed', () => {
	it('records an element while a wrapper observes it', () => {
		makeEl('watcher', 'span');
		const target = makeEl('plain');
		const watcher = new Watcher({ ID: 'watcher', Target: 'plain' });

		expect(Prado.Observed.has(target)).toBe(true);

		watcher.deinitialize();

		expect(Prado.Observed.has(target)).toBe(false);
	});

	it('keeps an element observed until its last observer stops', () => {
		makeEl('w1', 'span');
		makeEl('w2', 'span');
		const target = makeEl('plain');
		const first = new Watcher({ ID: 'w1', Target: 'plain' });
		const second = new Watcher({ ID: 'w2', Target: 'plain' });

		first.stopObserving(target, 'change', first.handler);
		expect(Prado.Observed.has(target)).toBe(true);

		second.deinitialize();
		expect(Prado.Observed.has(target)).toBe(false);
	});

	it('ignores a removal of an element it does not hold', () => {
		const target = makeEl('plain');
		expect(() => Prado.Observed.remove(target)).not.toThrow();
		expect(Prado.Observed.has(target)).toBe(false);
	});
});
