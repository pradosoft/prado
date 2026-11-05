/**
 * Tests for Prado.PostBack.doPostBack().
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *
 * Strategy: build a minimal jsdom form, pass options to Prado.PostBack, and
 * assert side-effects (fields added, form.action changed, submit triggered,
 * validation consulted).  We spy on jQuery(form).trigger to intercept submit.
 */

import { PostBack } from '../adapters/prado-core.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeForm(id = 'testForm') {
	const form = document.createElement('form');
	form.id     = id;
	form.action = 'http://example.com/original';
	document.body.appendChild(form);
	return form;
}

function removeForm(form) {
	if (form && form.parentNode) form.parentNode.removeChild(form);
}

/** Minimal options shared by most tests. */
function baseOpts(extra = {}) {
	return {
		FormID:         'testForm',
		CausesValidation: false,
		EventTarget:    '',
		EventParameter: '',
		...extra,
	};
}

// ─── Suite ───────────────────────────────────────────────────────────────────

describe('Prado.PostBack.doPostBack', () => {
	let form;
	const submits = [];

	beforeEach(() => {
		form = makeForm();
		submits.length = 0;
		// Capture submit events via jQuery (PostBack uses jQuery.trigger, not
		// a native DOM event, so native addEventListener won't fire here).
		// Each entry is the form data a listener sees at submit time.
		jQuery(form).on('submit', function (e) { e.preventDefault(); submits.push(new FormData(form)); });
	});

	afterEach(() => {
		jQuery(form).off('submit');
		removeForm(form);
		// Restore Prado.Validation if a test replaced it.
		delete global.Prado.Validation;
	});

	// ── form submit ───────────────────────────────────────────────────────────

	it('triggers a submit event on the form', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts(), fakeEvent);
		expect(submits).toHaveLength(1);
	});

	// ── EventTarget / EventParameter hidden fields ─────────────────────────

	it('submits a PRADO_POSTBACK_TARGET hidden field when EventTarget is set', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventTarget: 'btn1' }), fakeEvent);

		expect(submits[0].getAll('PRADO_POSTBACK_TARGET')).toEqual(['btn1']);
	});

	it('does NOT submit PRADO_POSTBACK_TARGET when EventTarget is empty', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventTarget: '' }), fakeEvent);

		expect(submits[0].has('PRADO_POSTBACK_TARGET')).toBe(false);
	});

	it('submits a PRADO_POSTBACK_PARAMETER hidden field when EventParameter is set', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventParameter: 'p42' }), fakeEvent);

		expect(submits[0].getAll('PRADO_POSTBACK_PARAMETER')).toEqual(['p42']);
	});

	it('does NOT submit PRADO_POSTBACK_PARAMETER when EventParameter is empty', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventParameter: '' }), fakeEvent);

		expect(submits[0].has('PRADO_POSTBACK_PARAMETER')).toBe(false);
	});

	it('removes the hidden fields when a submit listener cancels the submit', () => {
		// A TBulletedList item click whose submit a listener cancels.
		new PostBack(baseOpts({ EventTarget: 'list1', EventParameter: '3' }), { preventDefault: vi.fn() });

		expect(submits[0].get('PRADO_POSTBACK_PARAMETER')).toBe('3');
		expect(form.querySelector('[name="PRADO_POSTBACK_TARGET"]')).toBeNull();
		expect(form.querySelector('[name="PRADO_POSTBACK_PARAMETER"]')).toBeNull();

		// A later button postback without EventParameter submits only its own fields.
		jQuery(form).off('submit');
		let sent = null;
		form.submit = vi.fn(() => { sent = new FormData(form); });
		new PostBack({ FormID: 'testForm', EventTarget: 'btn1' }, { preventDefault: vi.fn() });

		expect(sent.getAll('PRADO_POSTBACK_TARGET')).toEqual(['btn1']);
		expect(sent.has('PRADO_POSTBACK_PARAMETER')).toBe(false);
	});

	// ── PostBackUrl ────────────────────────────────────────────────────────

	it('changes form.action when PostBackUrl is set', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ PostBackUrl: 'http://example.com/newaction' }), fakeEvent);

		expect(form.action).toBe('http://example.com/newaction');
	});

	it('does NOT change form.action when PostBackUrl is absent', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts(), fakeEvent);

		expect(form.action).toBe('http://example.com/original');
	});

	it('does NOT change form.action when PostBackUrl is an empty string', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ PostBackUrl: '' }), fakeEvent);

		expect(form.action).toBe('http://example.com/original');
	});

	// ── CausesValidation ─────────────────────────────────────────────────

	it('does NOT call Prado.Validation when CausesValidation is false', () => {
		const validate = vi.fn().mockReturnValue(true);
		global.Prado.Validation = { validate };

		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ CausesValidation: false }), fakeEvent);

		expect(validate).not.toHaveBeenCalled();
	});

	it('calls Prado.Validation.validate and submits when validation passes', () => {
		const validate = vi.fn().mockReturnValue(true);
		global.Prado.Validation = { validate };

		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(
			baseOpts({ CausesValidation: true, FormID: 'testForm', ID: 'btn1' }),
			fakeEvent,
		);

		// 3rd arg is the trigger element via document.getElementById(options.ID);
		// null is expected when no fixture element with that id exists.
		// (Pre-PR #1154, Prado.PostBack passed a jQuery wrapper here, which
		// matched expect.anything(); the de-jQuery rewrite returns a raw DOM
		// element or null.)
		expect(validate).toHaveBeenCalledWith('testForm', undefined, null);
		expect(submits).toHaveLength(1);
	});

	it('calls preventDefault and does NOT submit when CausesValidation is true and validation fails', () => {
		const validate = vi.fn().mockReturnValue(false);
		global.Prado.Validation = { validate };

		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(
			baseOpts({ CausesValidation: true, FormID: 'testForm', ID: 'btn1' }),
			fakeEvent,
		);

		expect(fakeEvent.preventDefault).toHaveBeenCalled();
		expect(submits).toHaveLength(0);
	});

	// ── option isolation between postbacks ────────────────────────────────

	describe('option isolation', () => {
		it('keeps options per instance and leaves the prototype untouched', () => {
			const first = new PostBack(baseOpts({ EventParameter: 'p1' }), { preventDefault: vi.fn() });
			const second = new PostBack(baseOpts(), { preventDefault: vi.fn() });

			expect(first.options).not.toBe(second.options);
			expect(first.options.EventParameter).toBe('p1');
			expect(second.options.EventParameter).toBe('');
			expect(Object.hasOwn(PostBack.prototype, 'options')).toBe(false);
		});

		it('does not reuse PostBackUrl from a postback cancelled by a submit listener', () => {
			new PostBack(baseOpts({ PostBackUrl: 'http://example.com/crosspage' }), { preventDefault: vi.fn() });
			expect(submits).toHaveLength(1);
			form.action = 'http://example.com/original';

			new PostBack(baseOpts(), { preventDefault: vi.fn() });

			expect(form.action).toBe('http://example.com/original');
		});

		it('does not reuse PostBackUrl from a postback cancelled by validation', () => {
			global.Prado.Validation = { validate: vi.fn().mockReturnValueOnce(false).mockReturnValue(true) };
			new PostBack(
				baseOpts({ CausesValidation: true, ID: 'btn1', PostBackUrl: 'http://example.com/crosspage' }),
				{ preventDefault: vi.fn() },
			);
			expect(form.action).toBe('http://example.com/original');

			new PostBack(baseOpts({ CausesValidation: true, ID: 'btn2' }), { preventDefault: vi.fn() });

			expect(form.action).toBe('http://example.com/original');
		});

		it('does not reuse ValidationGroup or CausesValidation from an earlier postback', () => {
			const validate = vi.fn().mockReturnValue(false);
			global.Prado.Validation = { validate };
			new PostBack(
				baseOpts({ CausesValidation: true, ValidationGroup: 'g1', ID: 'btn1' }),
				{ preventDefault: vi.fn() },
			);
			new PostBack(baseOpts({ CausesValidation: true, ID: 'btn2' }), { preventDefault: vi.fn() });

			expect(validate).toHaveBeenNthCalledWith(2, 'testForm', undefined, null);

			const { CausesValidation: _omit, ...plain } = baseOpts({ ID: 'btn3' });
			new PostBack(plain, { preventDefault: vi.fn() });

			expect(validate).toHaveBeenCalledTimes(2);
			expect(submits).toHaveLength(1);
		});
	});

	// ── TrackFocus (removed) ──────────────────────────────────────────────

	it('ignores the removed TrackFocus option and leaves PRADO_LASTFOCUS untouched', () => {
		const lastFocus = document.createElement('input');
		lastFocus.id   = 'PRADO_LASTFOCUS';
		lastFocus.type = 'hidden';
		form.appendChild(lastFocus);
		const btn = document.createElement('button');
		btn.id = 'btn1';
		form.appendChild(btn);
		btn.focus();

		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ TrackFocus: true, EventTarget: 'btn1' }), fakeEvent);

		expect(lastFocus.value).toBe('');
		expect(submits).toHaveLength(1);
	});

	// ── submitter formaction / formtarget / formenctype ───────────────────

	describe('submitter overrides', () => {
		let btn;
		let seen;

		beforeEach(() => {
			// Let the submit event through and record the form state at submit time.
			jQuery(form).off('submit');
			seen = null;
			form.submit = vi.fn(() => {
				seen = {
					action: form.getAttribute('action'),
					target: form.getAttribute('target'),
					enctype: form.getAttribute('enctype'),
				};
			});
			btn = document.createElement('button');
			btn.id = 'btn1';
			form.appendChild(btn);
		});

		it('submits with the button formaction, formtarget, and formenctype, then restores the form', () => {
			form.setAttribute('target', '_self');
			btn.setAttribute('formaction', '/export');
			btn.setAttribute('formtarget', '_blank');
			btn.setAttribute('formenctype', 'multipart/form-data');

			new PostBack(baseOpts({ ID: 'btn1' }), { preventDefault: vi.fn() });

			expect(seen).toEqual({ action: '/export', target: '_blank', enctype: 'multipart/form-data' });
			expect(form.getAttribute('action')).toBe('http://example.com/original');
			expect(form.getAttribute('target')).toBe('_self');
			expect(form.hasAttribute('enctype')).toBe(false);
		});

		it('removes the hidden fields after a submit to another window', () => {
			let sent = null;
			form.submit = vi.fn(() => { sent = new FormData(form); });
			btn.setAttribute('formtarget', '_blank');

			new PostBack(baseOpts({ ID: 'btn1', EventTarget: 'btn1', EventParameter: 'pdf' }), { preventDefault: vi.fn() });

			expect(sent.get('PRADO_POSTBACK_TARGET')).toBe('btn1');
			expect(sent.get('PRADO_POSTBACK_PARAMETER')).toBe('pdf');
			expect(form.querySelector('[name="PRADO_POSTBACK_TARGET"]')).toBeNull();
			expect(form.querySelector('[name="PRADO_POSTBACK_PARAMETER"]')).toBeNull();
		});

		it('leaves the form unchanged for a button without form attributes', () => {
			new PostBack(baseOpts({ ID: 'btn1' }), { preventDefault: vi.fn() });

			expect(seen).toEqual({ action: 'http://example.com/original', target: null, enctype: null });
		});

		it('ignores a button outside the form', () => {
			const outside = document.createElement('button');
			outside.id = 'btn2';
			outside.setAttribute('formaction', '/elsewhere');
			document.body.appendChild(outside);

			new PostBack(baseOpts({ ID: 'btn2' }), { preventDefault: vi.fn() });

			expect(seen.action).toBe('http://example.com/original');
			outside.remove();
		});
	});
});
