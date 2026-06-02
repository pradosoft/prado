/**
 * Tests for the jQuery.fn.trigger → native dispatchEvent bridge and the
 * companion Prado.Class.prototype.trigger helper.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * Why these tests exist:
 *   PR #1154 changed Prado's `observe` from `jQuery(el).bind(...)` to
 *   `addEventListener(...)`. jQuery's `.trigger()` only walks its own
 *   handler list and does NOT dispatch a real DOM event for non-activation
 *   events (see jquery/jquery#2476), so every downstream control still
 *   calling `jQuery(this.control).trigger('change')` became a no-op against
 *   handlers registered through the framework. The bridge in prado.js fixes
 *   that by detecting Prado-managed elements (via Prado.Registry +
 *   `constructor.isPradoClass`) and dispatching a native CustomEvent.
 *
 *   These tests cover:
 *     1. The factory marker (every Prado.Class constructor is tagged).
 *     2. Prado.Class.prototype.trigger (the new native helper).
 *     3. The jQuery.fn.trigger bridge:
 *        - the failure mode that pre-existed without the bridge
 *        - the fix the bridge provides
 *        - non-klass elements stay on the original jQuery code path
 *        - activation events (click / focus / blur) run defaults
 *        - form submit goes through el.submit()
 *        - CustomEvent detail payload survives
 *        - passing a real Event object is dispatched verbatim
 *        - elements without an id / not in the registry / without
 *          dispatchEvent fall back to the original trigger
 */

import '../adapters/prado-core.js';

// ─── helpers ──────────────────────────────────────────────────────────────────

const newId = (() => {
	let n = 0;
	return (prefix = 'tb') => `${prefix}_${++n}`;
})();

function makeEl(tag = 'input') {
	const el = document.createElement(tag);
	el.id = newId();
	document.body.appendChild(el);
	return el;
}

// Construct a klass-registered control wired to a real DOM element. Mirrors
// what Prado.WebUI.Control does (registers `this.element` in Prado.Registry
// under `options.ID`) without dragging in the whole controls.js machinery.
function makeKlassControl(el, extra = {}) {
	const K = $.klass(Object.assign({
		initialize(opts) {
			this.options = opts;
			this.ID = opts.ID;
			this.element = document.getElementById(opts.ID);
			Prado.Registry[opts.ID] = this;
		},
	}, extra));
	return new K({ ID: el.id });
}

function cleanupRegistry() {
	for (const k of Object.keys(Prado.Registry)) delete Prado.Registry[k];
}

afterEach(() => {
	cleanupRegistry();
	// Remove any leftover elements from earlier tests.
	while (document.body.firstChild) document.body.removeChild(document.body.firstChild);
});

// ─── 1. Factory marker ────────────────────────────────────────────────────────

describe('Prado.Class — isPradoClass marker', () => {
	it('tags every constructor produced by the factory', () => {
		const K = $.klass({});
		expect(K.isPradoClass).toBe(true);
	});

	it('tags subclasses too (single-level inheritance)', () => {
		const A = $.klass({});
		const B = $.klass(A, {});
		expect(B.isPradoClass).toBe(true);
	});

	it('tags subclasses at any inheritance depth', () => {
		const A = $.klass({});
		const B = $.klass(A, {});
		const C = $.klass(B, {});
		expect(C.isPradoClass).toBe(true);
	});

	it('marker survives the prototype-replacement pattern used by AssetManager and Rico.Color', () => {
		// ajax3.js does `Prado.AssetManagerClass = $.klass(); Prado.AssetManagerClass.prototype = {...}`
		const K = $.klass();
		K.prototype = { initialize() {}, foo() { return 1; } };
		// The marker lives on the constructor, not on the prototype, so a
		// wholesale prototype assignment must not remove it.
		expect(K.isPradoClass).toBe(true);
	});

	it('does NOT tag plain (non-Prado) constructors', () => {
		function NotAKlass() {}
		expect(NotAKlass.isPradoClass).toBeUndefined();
		// Also: instances of plain classes have a constructor without the marker.
		expect(new NotAKlass().constructor.isPradoClass).toBeUndefined();
	});
});

// ─── 2. Prado.Class.prototype.trigger ─────────────────────────────────────────

describe('Prado.Class.prototype.trigger', () => {
	it('is installed on every klass prototype', () => {
		const K = $.klass({});
		expect(typeof new K().trigger).toBe('function');
	});

	it('dispatches a CustomEvent on this.element', () => {
		const el = makeEl();
		const inst = makeKlassControl(el);
		let received = null;
		el.addEventListener('change', (e) => { received = e; });

		inst.trigger('change');

		expect(received).not.toBeNull();
		expect(received.type).toBe('change');
		expect(received instanceof CustomEvent).toBe(true);
		expect(received.bubbles).toBe(true);
		expect(received.cancelable).toBe(true);
	});

	it('passes the detail payload through', () => {
		const el = makeEl();
		const inst = makeKlassControl(el);
		let received = null;
		el.addEventListener('mything', (e) => { received = e.detail; });

		inst.trigger('mything', { foo: 42, bar: 'baz' });

		expect(received).toEqual({ foo: 42, bar: 'baz' });
	});

	it('is a no-op when this.element is missing (degraded init)', () => {
		const K = $.klass({ initialize() {} });
		const inst = new K();
		// No element attached; should not throw.
		expect(() => inst.trigger('change')).not.toThrow();
	});

	it('is a no-op when eventName is empty / undefined', () => {
		const el = makeEl();
		const inst = makeKlassControl(el);
		const spy = vi.fn();
		el.addEventListener('change', spy);

		inst.trigger();
		inst.trigger('');

		expect(spy).not.toHaveBeenCalled();
	});

	it('does not override a subclass that defines its own trigger', () => {
		const Base = $.klass({});
		const Derived = $.klass(Base, {
			trigger(eventName) { return `custom-${eventName}`; },
		});
		const inst = new Derived();
		expect(inst.trigger('foo')).toBe('custom-foo');
	});
});

// ─── 3. jQuery.fn.trigger bridge ──────────────────────────────────────────────

describe('jQuery.fn.trigger bridge — the bug from PR #1154', () => {
	// Reproduces the original failure: framework's `observe` uses
	// addEventListener (since PR #1154), but downstream controls still call
	// jQuery(el).trigger(name). Without the bridge, native handlers never fire.

	it('FIXED: native addEventListener handler fires when jQuery.trigger is called on a klass element', () => {
		const el = makeEl();
		makeKlassControl(el);
		const handler = vi.fn();
		// This is how Prado.WebUI.Control.observe registers handlers post-#1154.
		el.addEventListener('change', handler);

		// This is what legacy custom controls do to fire the event.
		jQuery(el).trigger('change');

		expect(handler).toHaveBeenCalledTimes(1);
		expect(handler.mock.calls[0][0].type).toBe('change');
	});

	it('jQuery-bound handlers also fire (jQuery itself routes via addEventListener)', () => {
		const el = makeEl();
		makeKlassControl(el);
		const handler = vi.fn();
		jQuery(el).on('change', handler);

		jQuery(el).trigger('change');

		expect(handler).toHaveBeenCalledTimes(1);
	});

	it('mixed handler setup: addEventListener + jQuery.on both fire on a klass element', () => {
		const el = makeEl();
		makeKlassControl(el);
		const native = vi.fn();
		const jq = vi.fn();
		el.addEventListener('change', native);
		jQuery(el).on('change', jq);

		jQuery(el).trigger('change');

		expect(native).toHaveBeenCalledTimes(1);
		expect(jq).toHaveBeenCalledTimes(1);
	});
});

describe('jQuery.fn.trigger bridge — passes through for non-klass elements', () => {
	it('falls back to jQuery original trigger when element is not in Prado.Registry', () => {
		const el = makeEl();
		// No makeKlassControl(el) — element is not klass-registered.
		const handler = vi.fn();
		jQuery(el).on('change', handler);

		jQuery(el).trigger('change');

		// jQuery's own pipeline still runs.
		expect(handler).toHaveBeenCalledTimes(1);
	});

	it('falls back when element has no id', () => {
		const el = document.createElement('input'); // not appended, no id
		document.body.appendChild(el);
		const handler = vi.fn();
		jQuery(el).on('change', handler);

		jQuery(el).trigger('change');

		expect(handler).toHaveBeenCalledTimes(1);
	});

	it('falls back when registry entry exists but is not a Prado.Class instance', () => {
		const el = makeEl();
		// Plant a non-klass object into the registry under this id.
		Prado.Registry[el.id] = { not: 'a klass' };
		const handler = vi.fn();
		jQuery(el).on('change', handler);

		jQuery(el).trigger('change');

		expect(handler).toHaveBeenCalledTimes(1);
	});
});

describe('jQuery.fn.trigger bridge — activation events (defaults must fire)', () => {
	it('"click" on a klass element calls el.click() so default activation runs', () => {
		const el = makeEl('button');
		makeKlassControl(el);
		const clickSpy = vi.spyOn(el, 'click');

		jQuery(el).trigger('click');

		expect(clickSpy).toHaveBeenCalledTimes(1);
		clickSpy.mockRestore();
	});

	it('"focus" on a klass element calls el.focus() so the element actually focuses', () => {
		const el = makeEl();
		makeKlassControl(el);
		const focusSpy = vi.spyOn(el, 'focus');

		jQuery(el).trigger('focus');

		expect(focusSpy).toHaveBeenCalledTimes(1);
		focusSpy.mockRestore();
	});

	it('"blur" on a klass element calls el.blur()', () => {
		const el = makeEl();
		makeKlassControl(el);
		const blurSpy = vi.spyOn(el, 'blur');

		jQuery(el).trigger('blur');

		expect(blurSpy).toHaveBeenCalledTimes(1);
		blurSpy.mockRestore();
	});

	it('"submit" on a klass FORM calls form.submit() so the form actually submits', () => {
		const form = makeEl('form');
		makeKlassControl(form);
		const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});
		const submitHandler = vi.fn();
		form.addEventListener('submit', submitHandler);

		jQuery(form).trigger('submit');

		// Both the submit event fires AND form.submit() is invoked.
		expect(submitHandler).toHaveBeenCalledTimes(1);
		expect(submitSpy).toHaveBeenCalledTimes(1);
		submitSpy.mockRestore();
	});

	it('"submit" on a klass FORM respects preventDefault() (does NOT call submit())', () => {
		const form = makeEl('form');
		makeKlassControl(form);
		const submitSpy = vi.spyOn(form, 'submit').mockImplementation(() => {});
		form.addEventListener('submit', (e) => { e.preventDefault(); });

		jQuery(form).trigger('submit');

		expect(submitSpy).not.toHaveBeenCalled();
		submitSpy.mockRestore();
	});

	it('"submit" on a non-FORM klass element does NOT call .submit() (no such method)', () => {
		const div = makeEl('div');
		makeKlassControl(div);
		// Plain divs have no submit() — bridge must not throw.
		expect(() => jQuery(div).trigger('submit')).not.toThrow();
	});
});

describe('jQuery.fn.trigger bridge — CustomEvent payload', () => {
	it('preserves a detail object passed as extraParameters', () => {
		const el = makeEl();
		makeKlassControl(el);
		let received = null;
		el.addEventListener('mything', (e) => { received = e.detail; });

		jQuery(el).trigger('mything', { value: 7 });

		expect(received).toEqual({ value: 7 });
	});

	it('uses CustomEvent (not plain Event) so detail is actually delivered', () => {
		const el = makeEl();
		makeKlassControl(el);
		let received = null;
		el.addEventListener('thing', (e) => { received = e; });

		jQuery(el).trigger('thing', 'payload-string');

		expect(received instanceof CustomEvent).toBe(true);
		expect(received.detail).toBe('payload-string');
	});
});

describe('jQuery.fn.trigger bridge — Event object passthrough', () => {
	it('dispatches a real Event instance verbatim instead of wrapping it', () => {
		const el = makeEl();
		makeKlassControl(el);
		let received = null;
		el.addEventListener('keydown', (e) => { received = e; });

		const realEvent = new Event('keydown', { bubbles: true, cancelable: true });
		jQuery(el).trigger(realEvent);

		expect(received).toBe(realEvent);
	});

	it('preserves a real Event instance over a CustomEvent wrap (no detail loss into CustomEvent)', () => {
		const el = makeEl();
		makeKlassControl(el);
		let received = null;
		el.addEventListener('keydown', (e) => { received = e; });

		const realEvent = new Event('keydown', { bubbles: true });
		jQuery(el).trigger(realEvent);

		// Did NOT get re-wrapped into a CustomEvent.
		expect(received instanceof CustomEvent).toBe(false);
	});
});

describe('jQuery.fn.trigger bridge — chainability', () => {
	it('returns the jQuery wrapper so .trigger() remains chainable', () => {
		const el = makeEl();
		makeKlassControl(el);
		const $el = jQuery(el);
		const ret = $el.trigger('change');
		expect(ret).toBe($el);
	});

	it('works on multiset selectors (each element processed independently)', () => {
		const a = makeEl();
		const b = makeEl();
		makeKlassControl(a);
		// b is intentionally NOT klass-registered.
		const onA = vi.fn();
		const onB = vi.fn();
		a.addEventListener('change', onA);
		b.addEventListener('change', onB); // jQuery installs an addEventListener for .on() too
		jQuery(b).on('change', onB);

		jQuery(a).add(b).trigger('change');

		expect(onA).toHaveBeenCalledTimes(1); // bridge path
		expect(onB).toHaveBeenCalled();        // original-trigger path (and also addEventListener)
	});
});

// ─── 4b. Exactly-once dispatch across all overlap combinations ──────────────
//
// These are the "no double call" tests. The bridge MUST call dispatchEvent
// without additionally invoking originalTrigger on the klass path — doing
// both would fire jQuery-bound handlers twice (once via jQuery's internal
// addEventListener registration, once via originalTrigger walking the same
// handler list). These tests pin that contract.

describe('jQuery.fn.trigger bridge — exactly-once dispatch (no overlap double-fire)', () => {
	it('one addEventListener handler fires exactly once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const fn = vi.fn();
		el.addEventListener('change', fn);
		jQuery(el).trigger('change');
		expect(fn).toHaveBeenCalledTimes(1);
	});

	it('one jQuery.on handler fires exactly once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const fn = vi.fn();
		jQuery(el).on('change', fn);
		jQuery(el).trigger('change');
		expect(fn).toHaveBeenCalledTimes(1);
	});

	it('mixed: addEventListener + jQuery.on — each fires exactly once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const native = vi.fn();
		const jq = vi.fn();
		el.addEventListener('change', native);
		jQuery(el).on('change', jq);
		jQuery(el).trigger('change');
		expect(native).toHaveBeenCalledTimes(1);
		expect(jq).toHaveBeenCalledTimes(1);
	});

	it('multiple addEventListener handlers (distinct functions) — each fires exactly once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const a = vi.fn(), b = vi.fn(), c = vi.fn();
		el.addEventListener('change', a);
		el.addEventListener('change', b);
		el.addEventListener('change', c);
		jQuery(el).trigger('change');
		expect(a).toHaveBeenCalledTimes(1);
		expect(b).toHaveBeenCalledTimes(1);
		expect(c).toHaveBeenCalledTimes(1);
	});

	it('multiple jQuery.on handlers (distinct functions) — each fires exactly once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const a = vi.fn(), b = vi.fn(), c = vi.fn();
		jQuery(el).on('change', a);
		jQuery(el).on('change', b);
		jQuery(el).on('change', c);
		jQuery(el).trigger('change');
		expect(a).toHaveBeenCalledTimes(1);
		expect(b).toHaveBeenCalledTimes(1);
		expect(c).toHaveBeenCalledTimes(1);
	});

	it('the matrix: 2 addEventListener + 2 jQuery.on — exactly 4 total calls, one per handler', () => {
		const el = makeEl();
		makeKlassControl(el);
		const n1 = vi.fn(), n2 = vi.fn(), j1 = vi.fn(), j2 = vi.fn();
		el.addEventListener('change', n1);
		el.addEventListener('change', n2);
		jQuery(el).on('change', j1);
		jQuery(el).on('change', j2);
		jQuery(el).trigger('change');
		expect(n1).toHaveBeenCalledTimes(1);
		expect(n2).toHaveBeenCalledTimes(1);
		expect(j1).toHaveBeenCalledTimes(1);
		expect(j2).toHaveBeenCalledTimes(1);
		// Total call budget — no spurious extra dispatch slipped in.
		expect(n1.mock.calls.length + n2.mock.calls.length + j1.mock.calls.length + j2.mock.calls.length).toBe(4);
	});

	it('addEventListener dedupes when same fn registered twice (browser spec), still fires once', () => {
		const el = makeEl();
		makeKlassControl(el);
		const fn = vi.fn();
		el.addEventListener('change', fn);
		el.addEventListener('change', fn); // browser spec: this is a no-op
		jQuery(el).trigger('change');
		expect(fn).toHaveBeenCalledTimes(1);
	});

	it('repeated jQuery.on with the same fn fires twice (jQuery behavior, not introduced by bridge)', () => {
		// Documenting jQuery's behavior, NOT the bridge's. jQuery's handler
		// list does not dedupe by default; the bridge must not paper over that.
		const el = makeEl();
		makeKlassControl(el);
		const fn = vi.fn();
		jQuery(el).on('change', fn);
		jQuery(el).on('change', fn);
		jQuery(el).trigger('change');
		expect(fn).toHaveBeenCalledTimes(2);
	});

	it('repeated trigger() calls fire each handler N times (no leftover state from prior triggers)', () => {
		const el = makeEl();
		makeKlassControl(el);
		const native = vi.fn();
		const jq = vi.fn();
		el.addEventListener('change', native);
		jQuery(el).on('change', jq);
		jQuery(el).trigger('change');
		jQuery(el).trigger('change');
		jQuery(el).trigger('change');
		expect(native).toHaveBeenCalledTimes(3);
		expect(jq).toHaveBeenCalledTimes(3);
	});

	it('two different event names do not bleed into each other', () => {
		const el = makeEl();
		makeKlassControl(el);
		const onChange = vi.fn();
		const onInput = vi.fn();
		el.addEventListener('change', onChange);
		jQuery(el).on('input', onInput);
		jQuery(el).trigger('change');
		expect(onChange).toHaveBeenCalledTimes(1);
		expect(onInput).not.toHaveBeenCalled();
	});

	it('stopPropagation in a native handler stops jQuery-bound bubble handlers', () => {
		// Confirms the native dispatch path obeys standard event-flow rules.
		// If the bridge somehow ran the jQuery path independently, this
		// assertion would fail.
		const parent = makeEl('div');
		const child = makeEl('input');
		parent.appendChild(child);
		makeKlassControl(child);
		const childNative = vi.fn((e) => e.stopPropagation());
		const parentJq = vi.fn();
		child.addEventListener('change', childNative);
		jQuery(parent).on('change', parentJq);
		jQuery(child).trigger('change');
		expect(childNative).toHaveBeenCalledTimes(1);
		expect(parentJq).not.toHaveBeenCalled();
	});
});

describe('jQuery.fn.trigger bridge — interplay with Prado.Class.prototype.trigger', () => {
	// Verifies the recommended migration path: existing callers using
	// jQuery .trigger() keep working; new code can switch to this.trigger()
	// and reach the same handlers.

	it('the same addEventListener handler fires whether dispatched via jQuery or via Prado.Class.trigger', () => {
		const el = makeEl();
		const inst = makeKlassControl(el);
		const handler = vi.fn();
		el.addEventListener('change', handler);

		jQuery(el).trigger('change');  // pre-4.4 idiom
		inst.trigger('change');         // 4.4+ idiom

		expect(handler).toHaveBeenCalledTimes(2);
	});
});
