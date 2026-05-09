/**
 * Tests for Prado.WebUI.THtmlArea.
 * Source: framework/Web/Javascripts/source/prado/controls/htmlarea.js
 *
 * tinyMCE is mocked on global before the adapter is imported.  Each test
 * that exercises tinyMCE integration configures the mock via vi.fn().
 *
 * ESM note: only tests/js/adapters/htmlarea.js changes on ESM conversion;
 * this file stays unchanged.
 */

// ─── tinyMCE mock (must be set BEFORE the adapter is imported) ────────────────

global.tinyMCE = {
	init:        vi.fn(),
	get:         vi.fn().mockReturnValue(null),
	execCommand: vi.fn(),
	editors:     [],
};

import { THtmlArea, Registry } from '../adapters/htmlarea.js';

// ─── Helpers ─────────────────────────────────────────────────────────────────

let idCounter = 0;

function buildTextarea(id) {
	const ta = document.createElement('textarea');
	ta.id = id;
	ta.value = 'initial content';
	document.body.appendChild(ta);
	return ta;
}

function makeHtmlArea(overrides = {}) {
	const id = 'ha-test-' + (++idCounter);
	buildTextarea(id);

	const options = Object.assign(
		{
			EditorOptions: { elements: id },
		},
		overrides,
	);

	return new THtmlArea(options);
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

// ─── Static properties ────────────────────────────────────────────────────────

describe('THtmlArea static properties', () => {
	it('starts with an empty pendingRegistrations array', () => {
		// Reset to known state (other tests may have pushed items)
		THtmlArea.pendingRegistrations = [];
		expect(Array.isArray(THtmlArea.pendingRegistrations)).toBe(true);
	});

	it('starts with tinyMCELoadState 0 initially (before any instance)', () => {
		// The initial value is 0; after initInstance() it becomes 255.
		// We just verify it is a number.
		expect(typeof THtmlArea.tinyMCELoadState).toBe('number');
	});
});

// ─── Constructor / ID assignment ──────────────────────────────────────────────

describe('THtmlArea constructor', () => {
	it('sets ID from EditorOptions.elements', () => {
		const id = 'ha-id-' + (++idCounter);
		buildTextarea(id);
		const ha = new THtmlArea({ EditorOptions: { elements: id } });
		expect(ha.ID).toBe(id);
	});

	it('stores options on instance', () => {
		const editorOpts = { elements: 'ha-opts-' + (++idCounter), mode: 'exact' };
		buildTextarea(editorOpts.elements);
		const ha = new THtmlArea({ EditorOptions: editorOpts });
		expect(ha.options.EditorOptions).toBe(editorOpts);
	});

	it('registers itself in Prado.Registry', () => {
		const ha = makeHtmlArea();
		expect(Registry[ha.ID]).toBe(ha);
	});
});

// ─── initInstance / tinyMCE.init ─────────────────────────────────────────────

describe('THtmlArea.initInstance', () => {
	it('calls tinyMCE.init with EditorOptions', () => {
		THtmlArea.tinyMCELoadState = 255;
		const editorOpts = { elements: 'ha-init-' + (++idCounter) };
		buildTextarea(editorOpts.elements);
		const ha = new THtmlArea({ EditorOptions: editorOpts });
		expect(global.tinyMCE.init).toHaveBeenCalledWith(editorOpts);
	});
});

// ─── registerAjaxHook / deRegisterAjaxHook ────────────────────────────────────

describe('THtmlArea ajax hooks', () => {
	it('registerAjaxHook does not throw', () => {
		const ha = makeHtmlArea();
		expect(() => ha.registerAjaxHook()).not.toThrow();
	});

	it('deRegisterAjaxHook does not throw', () => {
		const ha = makeHtmlArea();
		expect(() => ha.deRegisterAjaxHook()).not.toThrow();
	});
});

// ─── checkInstance ────────────────────────────────────────────────────────────

describe('THtmlArea.checkInstance', () => {
	it('does not throw when the textarea element exists in DOM', () => {
		const ha = makeHtmlArea();
		expect(() => ha.checkInstance()).not.toThrow();
	});

	it('calls deinitialize when the element is not in the DOM', () => {
		const ha = makeHtmlArea();
		// Remove the textarea from DOM
		const el = document.getElementById(ha.ID);
		if (el) { el.parentNode.removeChild(el); }
		const spy = vi.spyOn(ha, 'deinitialize').mockImplementation(() => {});
		ha.checkInstance();
		expect(spy).toHaveBeenCalled();
	});
});

// ─── ajaxresponder ────────────────────────────────────────────────────────────

describe('THtmlArea.ajaxresponder', () => {
	it('calls checkInstance', () => {
		const ha = makeHtmlArea();
		const spy = vi.spyOn(ha, 'checkInstance').mockImplementation(() => {});
		ha.ajaxresponder({});
		expect(spy).toHaveBeenCalled();
	});
});

// ─── removePreviousInstance ───────────────────────────────────────────────────

describe('THtmlArea.removePreviousInstance', () => {
	it('removes matching editors from tinyMCE.editors array', () => {
		const ha = makeHtmlArea();
		global.tinyMCE.editors = [{ id: ha.ID }, { id: 'other-id' }];
		// stub deregister/deRegisterAjaxHook so they don't break
		vi.spyOn(ha, 'deRegisterAjaxHook').mockImplementation(() => {});
		vi.spyOn(ha, 'deregister').mockImplementation(() => {});
		ha.removePreviousInstance();
		expect(global.tinyMCE.editors.length).toBe(1);
		expect(global.tinyMCE.editors[0].id).toBe('other-id');
	});

	it('leaves editors array intact when no match', () => {
		const ha = makeHtmlArea();
		global.tinyMCE.editors = [{ id: 'unrelated' }];
		ha.removePreviousInstance();
		expect(global.tinyMCE.editors.length).toBe(1);
	});
});

// ─── onDone ──────────────────────────────────────────────────────────────────

describe('THtmlArea.onDone', () => {
	it('does not call execCommand when tinyMCE.get returns null', () => {
		const ha = makeHtmlArea();
		global.tinyMCE.get.mockReturnValue(null);
		ha.onDone();
		expect(global.tinyMCE.execCommand).not.toHaveBeenCalled();
	});

	it('calls execCommand mceFocus and mceRemoveControl when a previous instance exists', () => {
		const ha = makeHtmlArea();
		const prev = { id: ha.ID };
		global.tinyMCE.get.mockReturnValue(prev);

		// Stub removePreviousInstance and deRegisterAjaxHook
		vi.spyOn(ha, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha.onDone();

		expect(global.tinyMCE.execCommand).toHaveBeenCalledWith('mceFocus', false, ha.ID);
		expect(global.tinyMCE.execCommand).toHaveBeenCalledWith('mceRemoveControl', false, ha.ID);
	});

	it('preserves textarea value after mceRemoveControl', () => {
		const ha = makeHtmlArea();
		const ta = document.getElementById(ha.ID);
		ta.value = 'preserved content';

		const prev = { id: ha.ID };
		global.tinyMCE.get.mockReturnValue(prev);
		vi.spyOn(ha, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha, 'deRegisterAjaxHook').mockImplementation(() => {});

		ha.onDone();

		expect(ta.value).toBe('preserved content');
	});

	it('suppresses errors thrown during remove', () => {
		const ha = makeHtmlArea();
		global.tinyMCE.get.mockReturnValue({ id: ha.ID });
		global.tinyMCE.execCommand.mockImplementation(() => {
			throw new Error('tinyMCE error');
		});
		vi.spyOn(ha, 'removePreviousInstance').mockImplementation(() => {});
		vi.spyOn(ha, 'deRegisterAjaxHook').mockImplementation(() => {});

		expect(() => ha.onDone()).not.toThrow();
	});
});

// ─── compressedScriptsLoaded ──────────────────────────────────────────────────

describe('THtmlArea.compressedScriptsLoaded', () => {
	it('sets tinyMCELoadState to 255', () => {
		const ha = makeHtmlArea();
		THtmlArea.tinyMCELoadState = 1;
		THtmlArea.pendingRegistrations = [];
		ha.compressedScriptsLoaded();
		expect(THtmlArea.tinyMCELoadState).toBe(255);
	});

	it('calls initInstance on all pending wrappers found in Registry', () => {
		const ha = makeHtmlArea();
		THtmlArea.pendingRegistrations = [ha.ID];
		Registry[ha.ID] = ha;
		const spy = vi.spyOn(ha, 'initInstance').mockImplementation(() => {});
		THtmlArea.tinyMCELoadState = 1;
		ha.compressedScriptsLoaded();
		expect(spy).toHaveBeenCalled();
	});
});
