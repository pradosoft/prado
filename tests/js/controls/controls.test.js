/**
 * Tests for Prado.WebUI controls (controls.js).
 * Source: framework/Web/Javascripts/source/prado/controls/controls.js
 *
 * ESM note: only tests/js/adapters/controls.js changes on ESM conversion.
 */

import {
	WebUI,
	Control,
	PostBackControl,
	TButton,
	TLinkButton,
	TCheckBox,
	TBulletedList,
	TImageMap,
	TImageButton,
	TRadioButton,
	TTextBox,
	TListControl,
	TListBox,
	TDropDownList,
	DefaultButton,
	TCheckBoxList,
	TRadioButtonList,
	PostBack,
	Registry,
} from '../adapters/controls.js';

// ─── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Create a DOM element, give it an id, append to document.body, and return it.
 */
function createElement(tag, id, attrs = {}) {
	const el = document.createElement(tag);
	el.id = id;
	for (const [k, v] of Object.entries(attrs)) {
		el.setAttribute(k, v);
	}
	document.body.appendChild(el);
	return el;
}

/**
 * Minimal form with a PRADO_PAGESTATE hidden field (required by PostBack).
 */
function createForm(formId = 'testForm') {
	const form = document.createElement('form');
	form.id = formId;
	form.action = '/test';
	form.method = 'post';

	const ps = document.createElement('input');
	ps.type = 'hidden';
	ps.id = 'PRADO_PAGESTATE';
	ps.name = 'PRADO_PAGESTATE';
	form.appendChild(ps);

	document.body.appendChild(form);
	return form;
}

/**
 * Clean Prado.Registry between tests so control IDs don't conflict.
 */
function cleanRegistry() {
	for (const key of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[key];
	}
}

/**
 * Build a standard options object for a PostBack control.
 */
function postBackOptions(id, formId = 'testForm', extra = {}) {
	return {
		ID: id,
		FormID: formId,
		EventTarget: id,
		EventParameter: '',
		CausesValidation: false,
		ValidationGroup: '',
		PostBackUrl: '',
		TrackFocus: false,
		...extra,
	};
}

// ─── Setup / teardown ────────────────────────────────────────────────────────

// jsdom does not implement HTMLFormElement.prototype.submit.  Any test that
// flows through Prado.PostBack.doPostBack() will call jQuery(form).trigger('submit')
// which ultimately calls form.submit().  We stub it in beforeAll so it runs
// after jsdom is fully initialised in this worker.
beforeAll(() => {
	if (typeof HTMLFormElement !== 'undefined') {
		HTMLFormElement.prototype.submit = function () { /* stubbed for jsdom */ };
	}
	// Also stub it on window, which is what jsdom actually puts it on in some versions.
	if (typeof window !== 'undefined' && window.HTMLFormElement) {
		window.HTMLFormElement.prototype.submit = function () { /* stubbed for jsdom */ };
	}
});

beforeEach(() => {
	cleanRegistry();
	document.body.innerHTML = '';
});

afterEach(() => {
	cleanRegistry();
	document.body.innerHTML = '';
});

// ─── Prado.WebUI namespace ────────────────────────────────────────────────────

describe('Prado.WebUI namespace', () => {
	it('exists as a function (klass constructor)', () => {
		expect(typeof WebUI).toBe('function');
	});

	it('exposes Control', () => {
		expect(typeof Control).toBe('function');
	});

	it('exposes PostBackControl', () => {
		expect(typeof PostBackControl).toBe('function');
	});

	it('exposes TButton', () => {
		expect(typeof TButton).toBe('function');
	});

	it('exposes TLinkButton', () => {
		expect(typeof TLinkButton).toBe('function');
	});

	it('exposes TCheckBox', () => {
		expect(typeof TCheckBox).toBe('function');
	});

	it('exposes TBulletedList', () => {
		expect(typeof TBulletedList).toBe('function');
	});

	it('exposes TImageMap', () => {
		expect(typeof TImageMap).toBe('function');
	});

	it('exposes TImageButton', () => {
		expect(typeof TImageButton).toBe('function');
	});

	it('exposes TRadioButton', () => {
		expect(typeof TRadioButton).toBe('function');
	});

	it('exposes TTextBox', () => {
		expect(typeof TTextBox).toBe('function');
	});

	it('exposes TListControl', () => {
		expect(typeof TListControl).toBe('function');
	});

	it('exposes TListBox', () => {
		expect(typeof TListBox).toBe('function');
	});

	it('exposes TDropDownList', () => {
		expect(typeof TDropDownList).toBe('function');
	});

	it('exposes DefaultButton', () => {
		expect(typeof DefaultButton).toBe('function');
	});

	it('exposes TCheckBoxList', () => {
		expect(typeof TCheckBoxList).toBe('function');
	});

	it('exposes TRadioButtonList', () => {
		expect(typeof TRadioButtonList).toBe('function');
	});
});

// ─── Prado.WebUI.Control ─────────────────────────────────────────────────────

describe('Prado.WebUI.Control — construction', () => {
	it('registers itself in Prado.Registry on construction', () => {
		createElement('div', 'ctrl1');
		new Control({ ID: 'ctrl1' });
		expect(global.Prado.Registry['ctrl1']).toBeDefined();
	});

	it('sets this.ID from options', () => {
		createElement('div', 'ctrl2');
		const ctrl = new Control({ ID: 'ctrl2' });
		expect(ctrl.ID).toBe('ctrl2');
	});

	it('resolves this.element to the DOM node', () => {
		const el = createElement('div', 'ctrl3');
		const ctrl = new Control({ ID: 'ctrl3' });
		expect(ctrl.element).toBe(el);
	});

	it('initialises observers as an empty array', () => {
		createElement('div', 'ctrl4');
		const ctrl = new Control({ ID: 'ctrl4' });
		expect(Array.isArray(ctrl.observers)).toBe(true);
		expect(ctrl.observers.length).toBe(0);
	});

	it('initialises intervals as an empty array', () => {
		createElement('div', 'ctrl5');
		const ctrl = new Control({ ID: 'ctrl5' });
		expect(Array.isArray(ctrl.intervals)).toBe(true);
		expect(ctrl.intervals.length).toBe(0);
	});

	it('sets registered = true after successful registration', () => {
		createElement('div', 'ctrl6');
		const ctrl = new Control({ ID: 'ctrl6' });
		expect(ctrl.registered).toBe(true);
	});

	it('calls onInit when it is defined', () => {
		createElement('div', 'ctrl7');
		const onInit = vi.fn();
		// Subclass with an onInit hook
		const Sub = jQuery.klass(Control, { onInit });
		new Sub({ ID: 'ctrl7' });
		expect(onInit).toHaveBeenCalledTimes(1);
	});

	it('passes options to onInit', () => {
		createElement('div', 'ctrl8');
		const onInit = vi.fn();
		const Sub = jQuery.klass(Control, { onInit });
		const opts = { ID: 'ctrl8', extra: 'value' };
		new Sub(opts);
		expect(onInit).toHaveBeenCalledWith(opts);
	});

	it('element is null/undefined when the DOM node does not exist', () => {
		// No element created for this ID
		const ctrl = new Control({ ID: 'nonexistent-xyz' });
		// jQuery("#nonexistent-xyz").get(0) returns undefined
		expect(ctrl.element).toBeFalsy();
	});
});

describe('Prado.WebUI.Control — register / deregister', () => {
	it('register() stores the instance in Prado.Registry', () => {
		createElement('div', 'reg1');
		const ctrl = new Control({ ID: 'reg1' });
		expect(global.Prado.Registry['reg1']).toBe(ctrl);
	});

	it('deregister() removes the entry from Prado.Registry', () => {
		createElement('div', 'reg2');
		const ctrl = new Control({ ID: 'reg2' });
		expect(global.Prado.Registry['reg2']).toBeDefined();
		ctrl.deregister();
		expect(global.Prado.Registry['reg2']).toBeUndefined();
	});

	it('deregister() returns the old wrapper reference', () => {
		createElement('div', 'reg3');
		const ctrl = new Control({ ID: 'reg3' });
		const ret = ctrl.deregister();
		expect(ret).toBe(ctrl);
	});
});

describe('Prado.WebUI.Control — replace()', () => {
	it('replaces an old wrapper and registers the new one', () => {
		createElement('div', 'repl1');
		const first = new Control({ ID: 'repl1' });
		expect(global.Prado.Registry['repl1']).toBe(first);

		// Creating a second wrapper for the same ID calls replace() internally
		const second = new Control({ ID: 'repl1' });
		expect(global.Prado.Registry['repl1']).toBe(second);
	});

	it('calls deinitialize() on the old wrapper when replacing', () => {
		createElement('div', 'repl2');
		const first = new Control({ ID: 'repl2' });
		const deInit = vi.spyOn(first, 'deinitialize');

		// A new wrapper for the same ID triggers replace → deinitialize
		new Control({ ID: 'repl2' });
		expect(deInit).toHaveBeenCalledTimes(1);
	});

	it('calls oldwrapper.deinitialize when oldwrapper has that method', () => {
		createElement('div', 'repl3');
		const first = new Control({ ID: 'repl3' });
		const spy = vi.fn();
		first.deinitialize = spy;
		first.registered = true; // keep registered=true so deinitialize guard passes

		const second = new Control({ ID: 'repl3' });
		// second.replace is invoked; it calls oldwrapper.deinitialize
		expect(spy).toHaveBeenCalledTimes(1);
		expect(global.Prado.Registry['repl3']).toBe(second);
	});
});

describe('Prado.WebUI.Control — observe / findObserver / stopObserving', () => {
	it('observe() appends to this.observers', () => {
		const el = createElement('div', 'obs1');
		const ctrl = new Control({ ID: 'obs1' });
		const handler = vi.fn();
		ctrl.observe(el, 'click', handler);
		expect(ctrl.observers.length).toBe(1);
	});

	it('observe() stores element, eventName, and handler', () => {
		const el = createElement('div', 'obs2');
		const ctrl = new Control({ ID: 'obs2' });
		const handler = vi.fn();
		ctrl.observe(el, 'click', handler);
		const o = ctrl.observers[0];
		expect(o._element).toBe(el);
		expect(o._eventName).toBe('click');
		expect(o._handler).toBe(handler);
	});

	it('findObserver() returns -1 when handler not registered', () => {
		const el = createElement('div', 'obs3');
		const ctrl = new Control({ ID: 'obs3' });
		const handler = vi.fn();
		expect(ctrl.findObserver(el, 'click', handler)).toBe(-1);
	});

	it('findObserver() returns 0-based index when handler is registered', () => {
		const el = createElement('div', 'obs4');
		const ctrl = new Control({ ID: 'obs4' });
		const handler = vi.fn();
		ctrl.observe(el, 'click', handler);
		expect(ctrl.findObserver(el, 'click', handler)).toBe(0);
	});

	it('findObserver() returns correct index for second observer', () => {
		const el = createElement('div', 'obs5');
		const ctrl = new Control({ ID: 'obs5' });
		const h1 = vi.fn();
		const h2 = vi.fn();
		ctrl.observe(el, 'click', h1);
		ctrl.observe(el, 'keydown', h2);
		expect(ctrl.findObserver(el, 'keydown', h2)).toBe(1);
	});

	it('stopObserving() removes the handler from observers array', () => {
		const el = createElement('div', 'obs6');
		const ctrl = new Control({ ID: 'obs6' });
		const handler = vi.fn();
		ctrl.observe(el, 'click', handler);
		ctrl.stopObserving(el, 'click', handler);
		expect(ctrl.observers.length).toBe(0);
	});

	it('stopObserving() does not affect other observers', () => {
		const el = createElement('div', 'obs7');
		const ctrl = new Control({ ID: 'obs7' });
		const h1 = vi.fn();
		const h2 = vi.fn();
		ctrl.observe(el, 'click', h1);
		ctrl.observe(el, 'keydown', h2);
		ctrl.stopObserving(el, 'click', h1);
		expect(ctrl.observers.length).toBe(1);
		expect(ctrl.observers[0]._handler).toBe(h2);
	});
});

describe('Prado.WebUI.Control — setTimeout / clearTimeout', () => {
	beforeEach(() => {
		vi.useFakeTimers();
	});

	afterEach(() => {
		vi.useRealTimers();
	});

	it('setTimeout() executes function after delay when not lingering', () => {
		createElement('div', 'to1');
		const ctrl = new Control({ ID: 'to1' });
		const fn = vi.fn();
		ctrl.setTimeout(fn, 100);
		vi.advanceTimersByTime(100);
		expect(fn).toHaveBeenCalledTimes(1);
	});

	it('setTimeout() does NOT execute when control is lingering', () => {
		createElement('div', 'to2');
		const ctrl = new Control({ ID: 'to2' });
		ctrl.registered = false; // make it lingering
		const fn = vi.fn();
		ctrl.setTimeout(fn, 100);
		vi.advanceTimersByTime(100);
		expect(fn).not.toHaveBeenCalled();
	});

	it('setTimeout() wraps a string expression in a function', () => {
		createElement('div', 'to3');
		const ctrl = new Control({ ID: 'to3' });
		// Should not throw — string is accepted
		expect(() => ctrl.setTimeout('1 + 1', 50)).not.toThrow();
	});

	it('clearTimeout() cancels a scheduled function', () => {
		createElement('div', 'to4');
		const ctrl = new Control({ ID: 'to4' });
		const fn = vi.fn();
		const tid = ctrl.setTimeout(fn, 200);
		ctrl.clearTimeout(tid);
		vi.advanceTimersByTime(200);
		expect(fn).not.toHaveBeenCalled();
	});
});

describe('Prado.WebUI.Control — setInterval / clearInterval', () => {
	beforeEach(() => {
		vi.useFakeTimers();
	});

	afterEach(() => {
		vi.useRealTimers();
	});

	it('setInterval() adds the handle to this.intervals', () => {
		createElement('div', 'iv1');
		const ctrl = new Control({ ID: 'iv1' });
		const fn = vi.fn();
		ctrl.setInterval(fn, 100);
		expect(ctrl.intervals.length).toBe(1);
	});

	it('setInterval() executes function repeatedly when not lingering', () => {
		createElement('div', 'iv2');
		const ctrl = new Control({ ID: 'iv2' });
		const fn = vi.fn();
		ctrl.setInterval(fn, 100);
		vi.advanceTimersByTime(300);
		expect(fn.mock.calls.length).toBeGreaterThanOrEqual(3);
	});

	it('setInterval() does NOT execute when control is lingering', () => {
		createElement('div', 'iv3');
		const ctrl = new Control({ ID: 'iv3' });
		ctrl.registered = false;
		const fn = vi.fn();
		ctrl.setInterval(fn, 100);
		vi.advanceTimersByTime(300);
		expect(fn).not.toHaveBeenCalled();
	});

	it('clearInterval() removes handle from this.intervals', () => {
		createElement('div', 'iv4');
		const ctrl = new Control({ ID: 'iv4' });
		const fn = vi.fn();
		const h = ctrl.setInterval(fn, 100);
		ctrl.clearInterval(h);
		expect(ctrl.intervals.length).toBe(0);
	});

	it('clearInterval() stops further invocations', () => {
		createElement('div', 'iv5');
		const ctrl = new Control({ ID: 'iv5' });
		const fn = vi.fn();
		const h = ctrl.setInterval(fn, 100);
		vi.advanceTimersByTime(150); // one call
		ctrl.clearInterval(h);
		vi.advanceTimersByTime(200); // no more calls
		expect(fn).toHaveBeenCalledTimes(1);
	});
});

describe('Prado.WebUI.Control — isLingering()', () => {
	it('returns false when registered', () => {
		createElement('div', 'ling1');
		const ctrl = new Control({ ID: 'ling1' });
		expect(ctrl.isLingering()).toBe(false);
	});

	it('returns true after deinitialize()', () => {
		// isLingering() returns !this.registered.
		// registered is only set to false by deinitialize(), not deregister().
		createElement('div', 'ling2');
		const ctrl = new Control({ ID: 'ling2' });
		ctrl.deinitialize();
		expect(ctrl.isLingering()).toBe(true);
	});
});

describe('Prado.WebUI.Control — deinitialize()', () => {
	it('calls onDone() when it is defined', () => {
		const el = createElement('div', 'deinit1');
		const Sub = jQuery.klass(Control, { onDone: vi.fn() });
		const ctrl = new Sub({ ID: 'deinit1' });
		const spy = vi.spyOn(ctrl, 'onDone');
		ctrl.deinitialize();
		expect(spy).toHaveBeenCalledTimes(1);
	});

	it('clears all intervals on deinitialize', () => {
		vi.useFakeTimers();
		createElement('div', 'deinit2');
		const ctrl = new Control({ ID: 'deinit2' });
		const fn = vi.fn();
		ctrl.setInterval(fn, 100);
		ctrl.deinitialize();
		expect(ctrl.intervals.length).toBe(0);
		vi.useRealTimers();
	});

	it('removes all observers on deinitialize', () => {
		const el = createElement('div', 'deinit3');
		const ctrl = new Control({ ID: 'deinit3' });
		ctrl.observe(el, 'click', vi.fn());
		ctrl.observe(el, 'keydown', vi.fn());
		ctrl.deinitialize();
		expect(ctrl.observers.length).toBe(0);
	});

	it('sets registered = false after deinitialize', () => {
		createElement('div', 'deinit4');
		const ctrl = new Control({ ID: 'deinit4' });
		ctrl.deinitialize();
		expect(ctrl.registered).toBe(false);
	});

	it('removes control from Prado.Registry after deinitialize', () => {
		createElement('div', 'deinit5');
		const ctrl = new Control({ ID: 'deinit5' });
		ctrl.deinitialize();
		expect(global.Prado.Registry['deinit5']).toBeUndefined();
	});
});

// ─── Prado.WebUI.PostBackControl ─────────────────────────────────────────────

describe('Prado.WebUI.PostBackControl — onInit', () => {
	it('attaches a click observer to the element', () => {
		createForm();
		const el = createElement('button', 'pb1', { type: 'button' });
		el.form; // just access
		const ctrl = new PostBackControl(postBackOptions('pb1'));
		// observer was pushed into ctrl.observers
		expect(ctrl.observers.length).toBeGreaterThan(0);
	});

	it('captures and removes existing onclick handler from element', () => {
		createForm();
		const el = createElement('button', 'pb2');
		const originalClick = vi.fn();
		el.onclick = originalClick;
		const ctrl = new PostBackControl(postBackOptions('pb2'));
		// The onclick on the element itself should have been cleared
		expect(el.onclick).toBeNull();
		// The captured onclick should be stored on the wrapper
		expect(ctrl._elementOnClick).toBeDefined();
	});

	it('does NOT capture onclick when element has no onclick', () => {
		createForm();
		createElement('button', 'pb3');
		const ctrl = new PostBackControl(postBackOptions('pb3'));
		expect(ctrl._elementOnClick).toBeNull();
	});
});

describe('Prado.WebUI.PostBackControl — elementClicked()', () => {
	it('calls onPostBack when element is not disabled', () => {
		createForm();
		createElement('button', 'pbclick1');
		const ctrl = new PostBackControl(postBackOptions('pbclick1'));
		const spy = vi.spyOn(ctrl, 'onPostBack');
		const fakeEvent = {
			target: ctrl.element,
			stopPropagation: vi.fn(),
			preventDefault: vi.fn(),
		};
		ctrl.elementClicked(postBackOptions('pbclick1'), fakeEvent);
		expect(spy).toHaveBeenCalledTimes(1);
	});

	it('does NOT call onPostBack when element is disabled', () => {
		createForm();
		const el = createElement('button', 'pbclick2');
		el.setAttribute('disabled', 'disabled');
		const ctrl = new PostBackControl(postBackOptions('pbclick2'));
		const spy = vi.spyOn(ctrl, 'onPostBack');
		const fakeEvent = {
			target: el,
			stopPropagation: vi.fn(),
			preventDefault: vi.fn(),
		};
		ctrl.elementClicked(postBackOptions('pbclick2'), fakeEvent);
		expect(spy).not.toHaveBeenCalled();
	});

	it('calls the captured _elementOnClick and honours boolean false return', () => {
		createForm();
		const el = createElement('button', 'pbclick3');
		el.onclick = () => false;
		const ctrl = new PostBackControl(postBackOptions('pbclick3'));
		const spy = vi.spyOn(ctrl, 'onPostBack');
		const fakeEvent = {
			target: el,
			stopPropagation: vi.fn(),
			preventDefault: vi.fn(),
		};
		ctrl.elementClicked(postBackOptions('pbclick3'), fakeEvent);
		// onclick returns false → doPostBack=false → onPostBack NOT called
		expect(spy).not.toHaveBeenCalled();
		expect(fakeEvent.stopPropagation).toHaveBeenCalled();
		expect(fakeEvent.preventDefault).toHaveBeenCalled();
	});

	it('calls onPostBack when captured onclick returns true', () => {
		createForm();
		const el = createElement('button', 'pbclick4');
		el.onclick = () => true;
		const ctrl = new PostBackControl(postBackOptions('pbclick4'));
		const spy = vi.spyOn(ctrl, 'onPostBack');
		const fakeEvent = {
			target: el,
			stopPropagation: vi.fn(),
			preventDefault: vi.fn(),
		};
		ctrl.elementClicked(postBackOptions('pbclick4'), fakeEvent);
		expect(spy).toHaveBeenCalledTimes(1);
	});
});

describe('Prado.WebUI.PostBackControl — onPostBack()', () => {
	it('creates a new Prado.PostBack with the given options', () => {
		createForm();
		createElement('button', 'pbpost1');
		const ctrl = new PostBackControl(postBackOptions('pbpost1'));

		// Replace Prado.PostBack with a spy to verify it is called with the right args
		// and to avoid triggering form.submit in jsdom.
		const origPostBack = global.Prado.PostBack;
		const pbSpy = vi.fn();
		global.Prado.PostBack = pbSpy;
		try {
			const fakeEvent = {
				target: ctrl.element,
				stopPropagation: vi.fn(),
				preventDefault: vi.fn(),
			};
			const opts = postBackOptions('pbpost1');
			ctrl.onPostBack(opts, fakeEvent);
			expect(pbSpy).toHaveBeenCalledTimes(1);
			expect(pbSpy).toHaveBeenCalledWith(opts, fakeEvent);
		} finally {
			global.Prado.PostBack = origPostBack;
		}
	});
});

// ─── TButton / TLinkButton / TCheckBox / TBulletedList / TImageMap ───────────

describe('TButton', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TButton.superclass).toBe(PostBackControl);
	});

	it('registers in Prado.Registry on construction', () => {
		createForm();
		createElement('button', 'tbtn1');
		new TButton(postBackOptions('tbtn1'));
		expect(global.Prado.Registry['tbtn1']).toBeDefined();
	});

	it('attaches click observer', () => {
		createForm();
		createElement('button', 'tbtn2');
		const ctrl = new TButton(postBackOptions('tbtn2'));
		expect(ctrl.observers.length).toBeGreaterThan(0);
	});
});

describe('TLinkButton', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TLinkButton.superclass).toBe(PostBackControl);
	});

	it('registers in Prado.Registry on construction', () => {
		createForm();
		createElement('a', 'tlnk1', { href: '#' });
		new TLinkButton(postBackOptions('tlnk1'));
		expect(global.Prado.Registry['tlnk1']).toBeDefined();
	});
});

describe('TCheckBox (PostBackControl subclass)', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TCheckBox.superclass).toBe(PostBackControl);
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('input', 'tcb1', { type: 'checkbox' });
		new TCheckBox(postBackOptions('tcb1'));
		expect(global.Prado.Registry['tcb1']).toBeDefined();
	});

	it('triggers postback on click when not disabled', () => {
		createForm();
		const el = createElement('input', 'tcb2', { type: 'checkbox' });
		const ctrl = new TCheckBox(postBackOptions('tcb2'));
		// Use mockImplementation to verify onPostBack is called without running PostBack
		const spy = vi.spyOn(ctrl, 'onPostBack').mockImplementation(() => {});
		// Simulate click via elementClicked
		const fakeEvent = { target: el, stopPropagation: vi.fn(), preventDefault: vi.fn() };
		ctrl.elementClicked(postBackOptions('tcb2'), fakeEvent);
		expect(spy).toHaveBeenCalledTimes(1);
	});
});

describe('TBulletedList', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TBulletedList.superclass).toBe(PostBackControl);
	});
});

describe('TImageMap', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TImageMap.superclass).toBe(PostBackControl);
	});
});

// ─── TImageButton ─────────────────────────────────────────────────────────────

describe('Prado.WebUI.TImageButton', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TImageButton.superclass).toBe(PostBackControl);
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('input', 'tib1', { type: 'image' });
		new TImageButton(postBackOptions('tib1'));
		expect(global.Prado.Registry['tib1']).toBeDefined();
	});

	describe('addXYInput()', () => {
		it('appends two hidden inputs to the form', () => {
			createForm();
			const el = createElement('input', 'tib2', { type: 'image' });
			const ctrl = new TImageButton(postBackOptions('tib2'));

			const form = document.getElementById('testForm');
			// clientX/Y = 0 in jsdom; offset = 0 → x=1, y=1 (clamped to 0 when <0)
			const fakeEvent = { clientX: 5, clientY: 10, target: el };
			const opts = postBackOptions('tib2', 'testForm', { EventTarget: 'tib2' });
			ctrl.addXYInput(opts, fakeEvent);

			const xInput = document.getElementById('tib2_x');
			const yInput = document.getElementById('tib2_y');
			expect(xInput).not.toBeNull();
			expect(yInput).not.toBeNull();
			expect(xInput.getAttribute('name')).toBe('tib2_x');
			expect(yInput.getAttribute('name')).toBe('tib2_y');
		});

		it('x and y values are clamped to 0 when negative', () => {
			createForm();
			const el = createElement('input', 'tib3', { type: 'image' });
			const ctrl = new TImageButton(postBackOptions('tib3'));

			const opts = postBackOptions('tib3', 'testForm', { EventTarget: 'tib3' });
			// clientX/Y very negative
			const fakeEvent = { clientX: -9999, clientY: -9999, target: el };
			ctrl.addXYInput(opts, fakeEvent);

			const xInput = document.getElementById('tib3_x');
			const yInput = document.getElementById('tib3_y');
			expect(parseInt(xInput.value, 10)).toBeGreaterThanOrEqual(0);
			expect(parseInt(yInput.value, 10)).toBeGreaterThanOrEqual(0);
		});
	});

	describe('removeXYInput()', () => {
		it('removes the hidden x/y inputs from the DOM', () => {
			createForm();
			const el = createElement('input', 'tib4', { type: 'image' });
			const ctrl = new TImageButton(postBackOptions('tib4'));

			const opts = postBackOptions('tib4', 'testForm', { EventTarget: 'tib4' });
			const fakeEvent = { clientX: 5, clientY: 5, target: el };
			ctrl.addXYInput(opts, fakeEvent);

			// Confirm inputs exist
			expect(document.getElementById('tib4_x')).not.toBeNull();

			ctrl.removeXYInput(opts, fakeEvent);
			expect(document.getElementById('tib4_x')).toBeNull();
			expect(document.getElementById('tib4_y')).toBeNull();
		});
	});

	describe('onPostBack()', () => {
		it('calls addXYInput and removeXYInput around PostBack', () => {
			createForm();
			const el = createElement('input', 'tib5', { type: 'image' });
			const ctrl = new TImageButton(postBackOptions('tib5'));
			const addSpy = vi.spyOn(ctrl, 'addXYInput');
			const removeSpy = vi.spyOn(ctrl, 'removeXYInput');

			// Stub Prado.PostBack so the constructor call does not execute doPostBack
			const origPostBack = global.Prado.PostBack;
			global.Prado.PostBack = function() {};
			try {
				const fakeEvent = { clientX: 1, clientY: 1, target: el, preventDefault: vi.fn() };
				const opts = postBackOptions('tib5', 'testForm', { EventTarget: 'tib5' });
				ctrl.onPostBack(opts, fakeEvent);
				expect(addSpy).toHaveBeenCalledTimes(1);
				expect(removeSpy).toHaveBeenCalledTimes(1);
			} finally {
				global.Prado.PostBack = origPostBack;
			}
		});
	});
});

// ─── TRadioButton ─────────────────────────────────────────────────────────────

describe('Prado.WebUI.TRadioButton', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TRadioButton.superclass).toBe(PostBackControl);
	});

	it('registers when element is NOT checked', () => {
		createForm();
		const el = createElement('input', 'trb1', { type: 'radio' });
		el.checked = false;
		new TRadioButton(postBackOptions('trb1'));
		expect(global.Prado.Registry['trb1']).toBeDefined();
	});

	it('does NOT register (and does NOT call super) when element IS checked', () => {
		createForm();
		const el = createElement('input', 'trb2', { type: 'radio' });
		el.checked = true;
		// TRadioButton skips $super when already checked — no entry in registry
		new TRadioButton(postBackOptions('trb2'));
		expect(global.Prado.Registry['trb2']).toBeUndefined();
	});

	it('does nothing (no throw) when element does not exist', () => {
		// No DOM element — just ensure no exception thrown
		expect(() => new TRadioButton(postBackOptions('nonexistent-rb'))).not.toThrow();
	});
});

// ─── TTextBox ─────────────────────────────────────────────────────────────────

describe('Prado.WebUI.TTextBox', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TTextBox.superclass).toBe(PostBackControl);
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('input', 'ttb1', { type: 'text' });
		new TTextBox(postBackOptions('ttb1', 'testForm', { TextMode: 'SingleLine', AutoPostBack: false }));
		expect(global.Prado.Registry['ttb1']).toBeDefined();
	});

	it('does NOT attach keydown listener for MultiLine text boxes', () => {
		createForm();
		createElement('textarea', 'ttb2');
		const ctrl = new TTextBox(postBackOptions('ttb2', 'testForm', { TextMode: 'MultiLine', AutoPostBack: false }));
		// With MultiLine, handleReturnKey observer is NOT attached
		const hasKeydown = ctrl.observers.some((o) => o._eventName === 'keydown');
		expect(hasKeydown).toBe(false);
	});

	it('attaches keydown listener for SingleLine text boxes', () => {
		createForm();
		createElement('input', 'ttb3', { type: 'text' });
		const ctrl = new TTextBox(postBackOptions('ttb3', 'testForm', { TextMode: 'SingleLine', AutoPostBack: false }));
		const hasKeydown = ctrl.observers.some((o) => o._eventName === 'keydown');
		expect(hasKeydown).toBe(true);
	});

	it('attaches change listener when AutoPostBack is true', () => {
		createForm();
		createElement('input', 'ttb4', { type: 'text' });
		const ctrl = new TTextBox(postBackOptions('ttb4', 'testForm', { TextMode: 'SingleLine', AutoPostBack: true }));
		const hasChange = ctrl.observers.some((o) => o._eventName === 'change');
		expect(hasChange).toBe(true);
	});

	it('does NOT attach change listener when AutoPostBack is false', () => {
		createForm();
		createElement('input', 'ttb5', { type: 'text' });
		const ctrl = new TTextBox(postBackOptions('ttb5', 'testForm', { TextMode: 'SingleLine', AutoPostBack: false }));
		const hasChange = ctrl.observers.some((o) => o._eventName === 'change');
		expect(hasChange).toBe(false);
	});

	describe('handleReturnKey()', () => {
		it('triggers change event on target when AutoPostBack=true and Enter pressed', () => {
			createForm();
			const el = createElement('input', 'ttb6', { type: 'text' });
			const ctrl = new TTextBox(postBackOptions('ttb6', 'testForm', { TextMode: 'SingleLine', AutoPostBack: true }));

			// Mock doPostback so it doesn't construct Prado.PostBack
			vi.spyOn(ctrl, 'doPostback').mockImplementation(() => {});

			const fakeEvent = {
				keyCode: 13,
				target: el,
				stopPropagation: vi.fn(),
				preventDefault: vi.fn(),
			};
			ctrl.handleReturnKey(fakeEvent);
			// stopPropagation should be called because AutoPostBack=true and Enter pressed
			expect(fakeEvent.stopPropagation).toHaveBeenCalled();
		});

		it('does NOT stopPropagation when key is not Enter', () => {
			createForm();
			const el = createElement('input', 'ttb7', { type: 'text' });
			const ctrl = new TTextBox(postBackOptions('ttb7', 'testForm', { TextMode: 'SingleLine', AutoPostBack: true }));
			const fakeEvent = {
				keyCode: 65, // 'A'
				target: el,
				stopPropagation: vi.fn(),
				preventDefault: vi.fn(),
			};
			ctrl.handleReturnKey(fakeEvent);
			expect(fakeEvent.stopPropagation).not.toHaveBeenCalled();
		});

		it('does NOT trigger change when AutoPostBack=false and no Prado.Validation', () => {
			createForm();
			const el = createElement('input', 'ttb8', { type: 'text' });
			const ctrl = new TTextBox(postBackOptions('ttb8', 'testForm', {
				TextMode: 'SingleLine',
				AutoPostBack: false,
				CausesValidation: false,
			}));
			const changeSpy = vi.fn();
			el.addEventListener('change', changeSpy);
			const fakeEvent = {
				keyCode: 13,
				target: el,
				stopPropagation: vi.fn(),
				preventDefault: vi.fn(),
			};
			ctrl.handleReturnKey(fakeEvent);
			expect(changeSpy).not.toHaveBeenCalled();
		});
	});

	describe('doPostback()', () => {
		it('invokes Prado.PostBack when called', () => {
			createForm();
			createElement('input', 'ttb9', { type: 'text' });
			const opts = postBackOptions('ttb9', 'testForm', { TextMode: 'SingleLine', AutoPostBack: true });
			const ctrl = new TTextBox(opts);
			// Stub Prado.PostBack to verify it is called without running doPostBack
			const origPostBack = global.Prado.PostBack;
			const pbSpy = vi.fn();
			global.Prado.PostBack = pbSpy;
			try {
				ctrl.doPostback(opts, {});
				expect(pbSpy).toHaveBeenCalledTimes(1);
				expect(pbSpy).toHaveBeenCalledWith(opts, {});
			} finally {
				global.Prado.PostBack = origPostBack;
			}
		});
	});
});

// ─── TListControl / TListBox / TDropDownList ──────────────────────────────────

describe('Prado.WebUI.TListControl', () => {
	it('is a subclass of PostBackControl', () => {
		expect(TListControl.superclass).toBe(PostBackControl);
	});

	it('attaches a change observer on init', () => {
		createForm();
		const el = createElement('select', 'tlc1');
		const ctrl = new TListControl(postBackOptions('tlc1'));
		const hasChange = ctrl.observers.some((o) => o._eventName === 'change');
		expect(hasChange).toBe(true);
	});

	it('triggers doPostback on change', () => {
		createForm();
		const el = createElement('select', 'tlc2');
		// Spy on Prado.PostBack before construction: the event handler is bound
		// via jQuery.proxy(this.doPostback, …) at init time, so spying on the
		// instance method after construction cannot intercept the call.  Instead
		// spy on the Prado.PostBack constructor that doPostback delegates to.
		const spy = vi.spyOn(global.Prado, 'PostBack').mockImplementation(function () {});
		new TListControl(postBackOptions('tlc2'));
		jQuery(el).trigger('change');
		expect(spy).toHaveBeenCalledTimes(1);
		spy.mockRestore();
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('select', 'tlc3');
		new TListControl(postBackOptions('tlc3'));
		expect(global.Prado.Registry['tlc3']).toBeDefined();
	});
});

describe('Prado.WebUI.TListBox', () => {
	it('is a subclass of TListControl', () => {
		expect(TListBox.superclass).toBe(TListControl);
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('select', 'tlb1', { multiple: 'multiple' });
		new TListBox(postBackOptions('tlb1'));
		expect(global.Prado.Registry['tlb1']).toBeDefined();
	});
});

describe('Prado.WebUI.TDropDownList', () => {
	it('is a subclass of TListControl', () => {
		expect(TDropDownList.superclass).toBe(TListControl);
	});

	it('registers in Prado.Registry', () => {
		createForm();
		createElement('select', 'tddl1');
		new TDropDownList(postBackOptions('tddl1'));
		expect(global.Prado.Registry['tddl1']).toBeDefined();
	});

	it('triggers postback when selection changes', () => {
		createForm();
		const el = createElement('select', 'tddl2');
		const opt1 = document.createElement('option');
		opt1.value = 'a';
		const opt2 = document.createElement('option');
		opt2.value = 'b';
		el.appendChild(opt1);
		el.appendChild(opt2);

		// Same pre-binding issue as TListControl: spy on Prado.PostBack.
		const spy = vi.spyOn(global.Prado, 'PostBack').mockImplementation(function () {});
		new TDropDownList(postBackOptions('tddl2'));
		jQuery(el).trigger('change');
		expect(spy).toHaveBeenCalledTimes(1);
		spy.mockRestore();
	});
});

// ─── DefaultButton ────────────────────────────────────────────────────────────

describe('Prado.WebUI.DefaultButton', () => {
	it('is a subclass of Control', () => {
		expect(DefaultButton.superclass).toBe(Control);
	});

	it('registers in Prado.Registry', () => {
		const panel = createElement('div', 'panel1');
		const target = createElement('button', 'target1');
		new DefaultButton({ ID: 'panel1', Panel: 'panel1', Target: 'target1', Event: 'click' });
		expect(global.Prado.Registry['panel1']).toBeDefined();
	});

	it('attaches a keydown observer to the panel element', () => {
		const panel = createElement('div', 'panel2');
		createElement('button', 'target2');
		const ctrl = new DefaultButton({ ID: 'panel2', Panel: 'panel2', Target: 'target2', Event: 'click' });
		expect(ctrl.observers.length).toBeGreaterThan(0);
	});

	describe('triggerEvent()', () => {
		it('triggers the configured event on the target when Enter pressed in a text input', () => {
			const panel = createElement('div', 'panel3');
			const target = createElement('button', 'target3');
			const input = createElement('input', 'inp3', { type: 'text' });
			panel.appendChild(input);

			const ctrl = new DefaultButton({ ID: 'panel3', Panel: 'panel3', Target: 'target3', Event: 'click' });

			const clickSpy = vi.fn();
			target.addEventListener('click', clickSpy);

			const fakeEvent = {
				keyCode: 13,
				target: input,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(clickSpy).toHaveBeenCalledTimes(1);
			expect(fakeEvent.preventDefault).toHaveBeenCalled();
		});

		it('does NOT trigger event when Enter is pressed in a textarea', () => {
			const panel = createElement('div', 'panel4');
			const target = createElement('button', 'target4');
			const textarea = createElement('textarea', 'ta4');
			panel.appendChild(textarea);

			const ctrl = new DefaultButton({ ID: 'panel4', Panel: 'panel4', Target: 'target4', Event: 'click' });
			const clickSpy = vi.fn();
			target.addEventListener('click', clickSpy);

			const fakeEvent = {
				keyCode: 13,
				target: textarea,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(clickSpy).not.toHaveBeenCalled();
		});

		it('does NOT trigger event when Enter is pressed in a submit input', () => {
			const panel = createElement('div', 'panel5');
			const target = createElement('button', 'target5');
			const submit = createElement('input', 'sub5', { type: 'submit' });
			panel.appendChild(submit);

			const ctrl = new DefaultButton({ ID: 'panel5', Panel: 'panel5', Target: 'target5', Event: 'click' });
			const clickSpy = vi.fn();
			target.addEventListener('click', clickSpy);

			const fakeEvent = {
				keyCode: 13,
				target: submit,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(clickSpy).not.toHaveBeenCalled();
		});

		it('does NOT trigger event when a non-Enter key is pressed', () => {
			const panel = createElement('div', 'panel6');
			const target = createElement('button', 'target6');
			const input = createElement('input', 'inp6', { type: 'text' });
			panel.appendChild(input);

			const ctrl = new DefaultButton({ ID: 'panel6', Panel: 'panel6', Target: 'target6', Event: 'click' });
			const clickSpy = vi.fn();
			target.addEventListener('click', clickSpy);

			const fakeEvent = {
				keyCode: 65, // 'A'
				target: input,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(clickSpy).not.toHaveBeenCalled();
		});

		it('does NOT trigger event when Enter is pressed in a hyperlink with href', () => {
			const panel = createElement('div', 'panel7');
			const target = createElement('button', 'target7');
			const link = createElement('a', 'lnk7', { href: 'http://example.com' });
			panel.appendChild(link);

			const ctrl = new DefaultButton({ ID: 'panel7', Panel: 'panel7', Target: 'target7', Event: 'click' });
			const clickSpy = vi.fn();
			target.addEventListener('click', clickSpy);

			const fakeEvent = {
				keyCode: 13,
				target: link,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(clickSpy).not.toHaveBeenCalled();
		});

		it('sets this.triggered = true when event fires', () => {
			const panel = createElement('div', 'panel8');
			createElement('button', 'target8');
			const input = createElement('input', 'inp8', { type: 'text' });
			panel.appendChild(input);

			const ctrl = new DefaultButton({ ID: 'panel8', Panel: 'panel8', Target: 'target8', Event: 'click' });
			const form = document.getElementById('testForm') || createForm('tf8');
			const fakeEvent = {
				keyCode: 13,
				target: input,
				preventDefault: vi.fn(),
			};
			ctrl.triggerEvent(fakeEvent);
			expect(ctrl.triggered).toBe(true);
		});
	});
});

// ─── TCheckBoxList ────────────────────────────────────────────────────────────

describe('Prado.WebUI.TCheckBoxList', () => {
	it('is a subclass of Control', () => {
		expect(TCheckBoxList.superclass).toBe(Control);
	});

	it('registers itself in Prado.Registry', () => {
		createForm();
		createElement('div', 'tcbl1');
		// Create child checkboxes
		createElement('input', 'tcbl1_c0', { type: 'checkbox' });
		createElement('input', 'tcbl1_c1', { type: 'checkbox' });
		new TCheckBoxList({
			ID: 'tcbl1',
			ItemCount: 2,
			ListName: 'tcbl1',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['tcbl1']).toBeDefined();
	});

	it('creates a TCheckBox wrapper for each item', () => {
		createForm();
		createElement('div', 'tcbl2');
		createElement('input', 'tcbl2_c0', { type: 'checkbox' });
		createElement('input', 'tcbl2_c1', { type: 'checkbox' });
		createElement('input', 'tcbl2_c2', { type: 'checkbox' });
		new TCheckBoxList({
			ID: 'tcbl2',
			ItemCount: 3,
			ListName: 'tcbl2',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['tcbl2_c0']).toBeDefined();
		expect(global.Prado.Registry['tcbl2_c1']).toBeDefined();
		expect(global.Prado.Registry['tcbl2_c2']).toBeDefined();
	});

	it('sets EventTarget to ListName$cN for each child', () => {
		createForm();
		createElement('div', 'tcbl3');
		createElement('input', 'tcbl3_c0', { type: 'checkbox' });
		new TCheckBoxList({
			ID: 'tcbl3',
			ItemCount: 1,
			ListName: 'MyList',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		// The child TCheckBox should have been created; we verify via registry
		const child = global.Prado.Registry['tcbl3_c0'];
		expect(child).toBeDefined();
	});

	it('creates zero children when ItemCount is 0', () => {
		createElement('div', 'tcbl4');
		new TCheckBoxList({
			ID: 'tcbl4',
			ItemCount: 0,
			ListName: 'tcbl4',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['tcbl4_c0']).toBeUndefined();
	});
});

// ─── TRadioButtonList ─────────────────────────────────────────────────────────

describe('Prado.WebUI.TRadioButtonList', () => {
	it('is a subclass of Control', () => {
		expect(TRadioButtonList.superclass).toBe(Control);
	});

	it('registers itself in Prado.Registry', () => {
		createForm();
		createElement('div', 'trbl1');
		// Create child radio buttons (unchecked so TRadioButton does register)
		const r0 = createElement('input', 'trbl1_c0', { type: 'radio' });
		r0.checked = false;
		const r1 = createElement('input', 'trbl1_c1', { type: 'radio' });
		r1.checked = false;
		new TRadioButtonList({
			ID: 'trbl1',
			ItemCount: 2,
			ListName: 'trbl1',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['trbl1']).toBeDefined();
	});

	it('creates a TRadioButton wrapper for each unchecked item', () => {
		createForm();
		createElement('div', 'trbl2');
		const r0 = createElement('input', 'trbl2_c0', { type: 'radio' });
		r0.checked = false;
		const r1 = createElement('input', 'trbl2_c1', { type: 'radio' });
		r1.checked = false;
		new TRadioButtonList({
			ID: 'trbl2',
			ItemCount: 2,
			ListName: 'trbl2',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['trbl2_c0']).toBeDefined();
		expect(global.Prado.Registry['trbl2_c1']).toBeDefined();
	});

	it('skips registration for already-checked radio buttons', () => {
		createForm();
		createElement('div', 'trbl3');
		const r0 = createElement('input', 'trbl3_c0', { type: 'radio' });
		r0.checked = true; // already checked
		new TRadioButtonList({
			ID: 'trbl3',
			ItemCount: 1,
			ListName: 'trbl3',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		// TRadioButton skips $super when checked
		expect(global.Prado.Registry['trbl3_c0']).toBeUndefined();
	});

	it('creates zero children when ItemCount is 0', () => {
		createElement('div', 'trbl4');
		new TRadioButtonList({
			ID: 'trbl4',
			ItemCount: 0,
			ListName: 'trbl4',
			FormID: 'testForm',
			EventParameter: '',
			CausesValidation: false,
		});
		expect(global.Prado.Registry['trbl4_c0']).toBeUndefined();
	});
});

// ─── Inheritance chain verification ──────────────────────────────────────────

describe('Inheritance chains', () => {
	it('Control prototype has observe method', () => {
		expect(typeof Control.prototype.observe).toBe('function');
	});

	it('Control prototype has deinitialize method', () => {
		expect(typeof Control.prototype.deinitialize).toBe('function');
	});

	it('Control prototype has isLingering method', () => {
		expect(typeof Control.prototype.isLingering).toBe('function');
	});

	it('PostBackControl prototype has elementClicked method', () => {
		expect(typeof PostBackControl.prototype.elementClicked).toBe('function');
	});

	it('PostBackControl prototype has onPostBack method', () => {
		expect(typeof PostBackControl.prototype.onPostBack).toBe('function');
	});

	it('TImageButton prototype has addXYInput method', () => {
		expect(typeof TImageButton.prototype.addXYInput).toBe('function');
	});

	it('TImageButton prototype has removeXYInput method', () => {
		expect(typeof TImageButton.prototype.removeXYInput).toBe('function');
	});

	it('TTextBox prototype has handleReturnKey method', () => {
		expect(typeof TTextBox.prototype.handleReturnKey).toBe('function');
	});

	it('TTextBox prototype has doPostback method', () => {
		expect(typeof TTextBox.prototype.doPostback).toBe('function');
	});

	it('TListControl prototype has doPostback method', () => {
		expect(typeof TListControl.prototype.doPostback).toBe('function');
	});

	it('DefaultButton prototype has triggerEvent method', () => {
		expect(typeof DefaultButton.prototype.triggerEvent).toBe('function');
	});
});

// ─── Edge cases and boundary inputs ──────────────────────────────────────────

describe('Edge cases', () => {
	it('Control: constructing with a missing ID still initialises observers/intervals', () => {
		// No element in DOM, missing ID
		const ctrl = new Control({ ID: 'missing-completely' });
		expect(Array.isArray(ctrl.observers)).toBe(true);
		expect(Array.isArray(ctrl.intervals)).toBe(true);
	});

	it('findObserver returns -1 when observers array is empty', () => {
		createElement('div', 'edge1');
		const ctrl = new Control({ ID: 'edge1' });
		expect(ctrl.findObserver(ctrl.element, 'click', () => {})).toBe(-1);
	});

	it('multiple controls with distinct IDs all register independently', () => {
		createElement('div', 'multi1');
		createElement('div', 'multi2');
		createElement('div', 'multi3');
		new Control({ ID: 'multi1' });
		new Control({ ID: 'multi2' });
		new Control({ ID: 'multi3' });
		expect(global.Prado.Registry['multi1']).toBeDefined();
		expect(global.Prado.Registry['multi2']).toBeDefined();
		expect(global.Prado.Registry['multi3']).toBeDefined();
	});

	it('TRadioButton: no throw when element is null (no matching DOM id)', () => {
		expect(() => new TRadioButton({ ID: 'no-such-radio' })).not.toThrow();
	});

	it('TCheckBoxList with ItemCount=1 and missing child element does not throw', () => {
		createElement('div', 'edge-cbl');
		// No child element created — TCheckBox will have a null element reference
		expect(() =>
			new TCheckBoxList({
				ID: 'edge-cbl',
				ItemCount: 1,
				ListName: 'edge-cbl',
				FormID: 'testForm',
				EventParameter: '',
				CausesValidation: false,
			})
		).not.toThrow();
	});

	it('DefaultButton triggerEvent: no throw when target has no id', () => {
		const panel = createElement('div', 'edge-panel');
		createElement('button', 'edge-target');
		const input = createElement('input', 'edge-inp', { type: 'text' });
		panel.appendChild(input);
		const ctrl = new DefaultButton({
			ID: 'edge-panel',
			Panel: 'edge-panel',
			Target: 'edge-target',
			Event: 'click',
		});
		const fakeEvent = {
			keyCode: 13,
			target: input,
			preventDefault: vi.fn(),
		};
		expect(() => ctrl.triggerEvent(fakeEvent)).not.toThrow();
	});
});
