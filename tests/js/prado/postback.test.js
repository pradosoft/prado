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
		// Reset the shared prototype options object between tests to prevent
		// option bleed: PostBack.prototype.options is mutated by jQuery.extend.
		PostBack.prototype.options = {};
		form = makeForm();
		submits.length = 0;
		// Capture submit events via jQuery (PostBack uses jQuery.trigger, not
		// a native DOM event, so native addEventListener won't fire here).
		jQuery(form).on('submit', function (e) { e.preventDefault(); submits.push(true); });
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

	it('appends a PRADO_POSTBACK_TARGET hidden field when EventTarget is set', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventTarget: 'btn1' }), fakeEvent);

		const field = form.querySelector('[name="PRADO_POSTBACK_TARGET"]');
		expect(field).not.toBeNull();
		expect(field.value).toBe('btn1');
	});

	it('does NOT append PRADO_POSTBACK_TARGET when EventTarget is empty', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventTarget: '' }), fakeEvent);

		expect(form.querySelector('[name="PRADO_POSTBACK_TARGET"]')).toBeNull();
	});

	it('appends a PRADO_POSTBACK_PARAMETER hidden field when EventParameter is set', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventParameter: 'p42' }), fakeEvent);

		const field = form.querySelector('[name="PRADO_POSTBACK_PARAMETER"]');
		expect(field).not.toBeNull();
		expect(field.value).toBe('p42');
	});

	it('does NOT append PRADO_POSTBACK_PARAMETER when EventParameter is empty', () => {
		const fakeEvent = { preventDefault: vi.fn() };
		new PostBack(baseOpts({ EventParameter: '' }), fakeEvent);

		expect(form.querySelector('[name="PRADO_POSTBACK_PARAMETER"]')).toBeNull();
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
