/**
 * Tests for Prado.WebUI.TJuiAutoComplete — completely uncovered class.
 * Source: framework/Web/Javascripts/source/prado/activecontrols/activecontrols3.js
 *
 * TJuiAutoComplete requires jQuery UI autocomplete, which is not present in
 * jsdom.  We stub the jQuery plugin and Prado.Callback before constructing
 * instances, then test the pure-logic methods directly on the prototype.
 */

import { WebUI } from '../adapters/activecontrols.js';

const TJuiAutoComplete = WebUI.TJuiAutoComplete;

// ─── Stub jQuery UI autocomplete so the constructor doesn't throw ─────────────

beforeAll(() => {
	// $.fn.autocomplete stub: returns a jQuery-like chain with .data() → object.
	const fakeWidget = { _renderItem: null };
	jQuery.fn.autocomplete = function (opts) {
		return { data: () => fakeWidget };
	};
});

// ─── Minimal instance factory ─────────────────────────────────────────────────

function makeInstance(extra = {}) {
	const el = document.createElement('input');
	el.id = extra.ID || 'jac-input';
	document.body.appendChild(el);

	const inst = Object.create(TJuiAutoComplete.prototype);
	inst.options   = { ID: el.id, EventTarget: 'theTarget', Separators: '', ...extra };
	inst.observers = [];
	inst.active    = false;
	inst.element   = el;
	return inst;
}

// ─── extractLastTerm ──────────────────────────────────────────────────────────

describe('TJuiAutoComplete.extractLastTerm', () => {
	it('returns the whole string when Separators is empty', () => {
		const inst = makeInstance({ Separators: '' });
		expect(inst.extractLastTerm('hello world')).toBe('hello world');
	});

	it('splits on the configured separator and returns the last token', () => {
		const inst = makeInstance({ Separators: ',' });
		expect(inst.extractLastTerm('apple, banana, cherry')).toBe('cherry');
	});

	it('trims whitespace from the extracted term', () => {
		const inst = makeInstance({ Separators: ',' });
		expect(inst.extractLastTerm('alpha,  beta  ')).toBe('beta');
	});

	it('returns the original string when separator is absent in the input', () => {
		const inst = makeInstance({ Separators: ',' });
		expect(inst.extractLastTerm('nospace')).toBe('nospace');
	});

	it('handles multiple separator characters', () => {
		const inst = makeInstance({ Separators: ',;' });
		expect(inst.extractLastTerm('a,b;c')).toBe('c');
	});
});

// ─── selectEntry ─────────────────────────────────────────────────────────────

describe('TJuiAutoComplete.selectEntry', () => {
	it('strips the incomplete last term and appends the selected item value', () => {
		const inst = makeInstance({ Separators: ',' });

		// Simulate: user typed "apple, ban" → selects "banana" (id: 7, value: 'banana')
		const event = { target: { value: 'apple, ban' } };
		const ui    = { item: { id: '7', value: 'banana' } };

		// Stub Prado.Callback to capture what value is passed.
		let cbParams = null;
		global.Prado.Callback = (_target, params, _cb, _opts) => { cbParams = params; };

		inst.selectEntry(event, ui);

		// The item.value should have been updated to strip "ban" and prepend previous terms.
		expect(ui.item.value).toBe('apple, banana');
		// A callback should have been dispatched with the item id and action.
		expect(cbParams[0]).toBe('7');
		expect(cbParams[1]).toBe('__TJuiAutoComplete_onSuggestionSelected__');
	});
});

// ─── onComplete ───────────────────────────────────────────────────────────────

describe('TJuiAutoComplete.onComplete', () => {
	it('extracts text content for value when textCssClass is undefined', () => {
		const inst = makeInstance();
		delete inst.options.textCssClass;

		const result = [
			{ label: '<b>Hello</b> World', value: '' },
		];
		const request = { options: { autocompleteCallback: (r) => { /* noop */ } } };
		inst.onComplete(request, result);

		// value should be the plain text of the label HTML
		expect(result[0].value).toBe('Hello World');
	});

	it('extracts text from the named CSS class element when textCssClass is set', () => {
		const inst = makeInstance({ textCssClass: 'display-name' });

		const result = [
			{ label: '<span class="display-name">Jane</span><span class="extra">Doe</span>', value: '' },
		];
		const request = { options: { autocompleteCallback: (r) => { /* noop */ } } };
		inst.onComplete(request, result);

		expect(result[0].value).toBe('Jane');
	});

	it('handles empty result array without throwing', () => {
		const inst = makeInstance();
		const request = { options: { autocompleteCallback: () => {} } };
		expect(() => inst.onComplete(request, [])).not.toThrow();
	});

	it('calls the autocompleteCallback with the processed result', () => {
		const inst = makeInstance();
		delete inst.options.textCssClass;

		const result = [{ label: 'Test', value: '' }];
		let calledWith = null;
		const request = { options: { autocompleteCallback: (r) => { calledWith = r; } } };
		inst.onComplete(request, result);

		expect(calledWith).toBe(result);
	});
});

// ─── doCallback ───────────────────────────────────────────────────────────────

describe('TJuiAutoComplete.doCallback', () => {
	it('does not dispatch when this.active is true', () => {
		const inst = makeInstance();
		inst.active = true;

		let dispatched = false;
		// If CallbackRequest were constructed, dispatch would be called.
		const origCR = global.Prado.CallbackRequest;
		global.Prado.CallbackRequest = function () {
			this.dispatch = function () { dispatched = true; };
		};

		const event = { stopPropagation: vi.fn() };
		inst.doCallback(event, {});

		global.Prado.CallbackRequest = origCR;
		expect(dispatched).toBe(false);
		expect(event.stopPropagation).not.toHaveBeenCalled();
	});

	it('dispatches and stops propagation when this.active is false', () => {
		const inst = makeInstance();
		inst.active = false;

		let dispatched = false;
		const origCR = global.Prado.CallbackRequest;
		global.Prado.CallbackRequest = function () {
			this.dispatch = function () { dispatched = true; };
		};

		const event = { stopPropagation: vi.fn() };
		inst.doCallback(event, {});

		global.Prado.CallbackRequest = origCR;
		expect(dispatched).toBe(true);
		expect(event.stopPropagation).toHaveBeenCalled();
	});
});
