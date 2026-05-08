/**
 * Tests for Prado.WebUI.THtmlArea5.
 * Source: framework/Web/Javascripts/source/prado/controls/htmlarea5.js
 *
 * tinyMCE is mocked on global before the adapter is imported.  Each test
 * that exercises tinyMCE integration configures the mock via vi.fn().
 *
 * ESM note: only tests/js/adapters/htmlarea5.js changes on ESM conversion;
 * this file stays unchanged.
 */

// ─── tinyMCE mock (must be set BEFORE the adapter is imported) ────────────────

global.tinyMCE = {
	init:        vi.fn(),
	get:         vi.fn().mockReturnValue(null),
	execCommand: vi.fn(),
	editors:     [],
};

import { THtmlArea5, Registry } from '../adapters/htmlarea5.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

let idCounter = 0;

function buildTextarea(id) {
	const ta = document.createElement('textarea');
	ta.id = id;
	ta.value = 'initial content';
	document.body.appendChild(ta);
	return ta;
}

function makeHtmlArea5(overrides = {}) {
	const id = 'ha5-test-' + (++idCounter);
	buildTextarea(id);

	const options = Object.assign(
		{
			ID: id,
			EditorOptions: { selector: '#' + id },
		},
		overrides,
	);

	return new THtmlArea5(options);
}

beforeEach(() => {
	vi.clearAllMocks();
	global.tinyMCE.editors = [];
	global.tinyMCE.get.mockReturnValue(null);
});

afterEach(() => {
	document.body.innerHTML = '';
	for (const k of Object.keys(Registry)) {
		delete Registry[k];
	}
});

// ─── Constructor ──────────────────────────────────────────────────────────────

describe('THtmlArea5 constructor', () => {
	it('registers itself in Prado.Registry', () => {
		const ha5 = makeHtmlArea5();
		expect(Registry[ha5.ID]).toBe(ha5);
	});

	it('stores options on the instance', () => {
		const editorOpts = { selector: '#custom-id-' + (++idCounter) };
		const id = editorOpts.selector.slice(1);
		buildTextarea(id);
		const ha5 = new THtmlArea5({ ID: id, EditorOptions: editorOpts });
		expect(ha5.options.EditorOptions).toBe(editorOpts);
	});

	it('calls tinyMCE.init during onInit', () => {
		const ha5 = makeHtmlArea5();
		expect(global.tinyMCE.init).toHaveBeenCalledWith(ha5.options.EditorOptions);
	});
});

// ─── registerAjaxHook / deRegisterAjaxHook ────────────────────────────────────

describe('THtmlArea5 ajax hooks', () => {
	it('registerAjaxHook does not throw', () => {
		const ha5 = makeHtmlArea5();
		expect(() => ha5.registerAjaxHook()).not.toThrow();
	});

	it('deRegisterAjaxHook does not throw', () => {
		const ha5 = makeHtmlArea5();
		expect(() => ha5.deRegisterAjaxHook()).not.toThrow();
	});
});

// ─── checkInstance ────────────────────────────────────────────────────────────

describe('THtmlArea5.checkInstance', () => {
	it('does not throw when the textarea exists in the DOM', () => {
		const ha5 = makeHtmlArea5();
		expect(() => ha5.checkInstance()).not.toThrow();
	});

	it('calls deinitialize when the element is absent from the DOM', () => {
		const ha5 = makeHtmlArea5();
		const el = document.getElementById(ha5.ID);
		if (el) { el.parentNode.removeChild(el); }
		const spy = vi.spyOn(ha5, 'deinitialize').mockImplementation(() => {});
		ha5.checkInstance();
		expect(spy).toHaveBeenCalled();
	});
});

// ─── ajaxresponder ────────────────────────────────────────────────────────────

describe('THtmlArea5.ajaxresponder', () => {
	it('delegates to checkInstance', () => {
		const ha5 = makeHtmlArea5();
		const spy = vi.spyOn(ha5, 'checkInstance').mockImplementation(() => {});
		ha5.ajaxresponder({});
		expect(spy).toHaveBeenCalled();
	});
});

// ─── removePreviousInstance ───────────────────────────────────────────────────

describe('THtmlArea5.removePreviousInstance', () => {
	it('removes matching editors from tinyMCE.editors', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.editors = [{ id: ha5.ID }, { id: 'other' }];
		ha5.removePreviousInstance();
		expect(global.tinyMCE.editors.length).toBe(1);
		expect(global.tinyMCE.editors[0].id).toBe('other');
	});

	it('removes multiple editors with the same id', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.editors = [{ id: ha5.ID }, { id: ha5.ID }];
		ha5.removePreviousInstance();
		expect(global.tinyMCE.editors.length).toBe(0);
	});

	it('leaves the array unchanged when no match', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.editors = [{ id: 'unrelated' }];
		ha5.removePreviousInstance();
		expect(global.tinyMCE.editors.length).toBe(1);
	});
});

// ─── onDone — no previous instance ───────────────────────────────────────────

describe('THtmlArea5.onDone — no previous tinyMCE instance', () => {
	it('does not throw when tinyMCE.get returns null', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.get.mockReturnValue(null);
		expect(() => ha5.onDone()).not.toThrow();
	});

	it('does not call execCommand when there is no previous instance', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.get.mockReturnValue(null);
		ha5.onDone();
		expect(global.tinyMCE.execCommand).not.toHaveBeenCalled();
	});

	it('calls deRegisterAjaxHook even when no previous instance', () => {
		const ha5 = makeHtmlArea5();
		global.tinyMCE.get.mockReturnValue(null);
		const spy = vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});
		ha5.onDone();
		expect(spy).toHaveBeenCalled();
	});
});

// ─── onDone — with previous instance ─────────────────────────────────────────

describe('THtmlArea5.onDone — with previous tinyMCE instance', () => {
	it('calls execCommand mceFocus', () => {
		const ha5 = makeHtmlArea5();
		const prev = { id: ha5.ID, remove: vi.fn() };
		global.tinyMCE.get.mockReturnValue(prev);
		vi.spyOn(ha5, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha5.onDone();

		expect(global.tinyMCE.execCommand).toHaveBeenCalledWith('mceFocus', false, ha5.ID);
	});

	it('calls prev.remove()', () => {
		const ha5 = makeHtmlArea5();
		const removeMock = vi.fn();
		const prev = { id: ha5.ID, remove: removeMock };
		global.tinyMCE.get.mockReturnValue(prev);
		vi.spyOn(ha5, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha5.onDone();

		expect(removeMock).toHaveBeenCalled();
	});

	it('preserves textarea value after remove', () => {
		const ha5 = makeHtmlArea5();
		const ta = document.getElementById(ha5.ID);
		ta.value = 'preserved value';

		const prev = { id: ha5.ID, remove: vi.fn() };
		global.tinyMCE.get.mockReturnValue(prev);
		vi.spyOn(ha5, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha5.onDone();

		expect(ta.value).toBe('preserved value');
	});

	it('calls removePreviousInstance to clean up registry', () => {
		const ha5 = makeHtmlArea5();
		const prev = { id: ha5.ID, remove: vi.fn() };
		global.tinyMCE.get.mockReturnValue(prev);
		const spy = vi.spyOn(ha5, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha5.onDone();

		expect(spy).toHaveBeenCalled();
	});

	it('calls deRegisterAjaxHook', () => {
		const ha5 = makeHtmlArea5();
		const prev = { id: ha5.ID, remove: vi.fn() };
		global.tinyMCE.get.mockReturnValue(prev);
		vi.spyOn(ha5, 'removePreviousInstance').mockImplementation(() => {});
		const spy = vi.spyOn(ha5, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha5.onDone();

		expect(spy).toHaveBeenCalled();
	});
});

// ─── tinyMCE.init arguments ──────────────────────────────────────────────────

describe('THtmlArea5 tinyMCE.init arguments', () => {
	it('passes EditorOptions verbatim to tinyMCE.init', () => {
		const editorOpts = {
			selector: '#specific-' + (++idCounter),
			toolbar: 'bold italic',
			plugins: 'lists',
		};
		const id = editorOpts.selector.slice(1);
		buildTextarea(id);
		new THtmlArea5({ ID: id, EditorOptions: editorOpts });
		expect(global.tinyMCE.init).toHaveBeenCalledWith(
			expect.objectContaining({ toolbar: 'bold italic', plugins: 'lists' }),
		);
	});
});
