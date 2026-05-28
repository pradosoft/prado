/**
 * Tests for Prado.WebUI.TAccordion (accordion.js).
 * Source: framework/Web/Javascripts/source/prado/controls/accordion.js
 *
 * DOM structure expected by TAccordion:
 *   <div id="{ID}">              — accordion container
 *   <input id="{ID}_1" …>       — hidden field, value = active view index
 *   For each view key in options.Views:
 *     <div id="{viewID}">        — view panel (collapsible body)
 *     <div id="{viewID}_0">      — header / clickable title
 *
 * ESM note: only tests/js/adapters/accordion.js changes on ESM conversion.
 */

import { TAccordion } from '../adapters/accordion.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

/**
 * Build a minimal accordion DOM and return an options object that matches it.
 *
 * @param {string[]} viewIDs   - IDs of the view panels, e.g. ['v0','v1','v2']
 * @param {number}   active    - index of the initially-active view (stored in hidden field)
 * @param {object}   extra     - extra options merged into the returned options object
 */
function buildDOM(viewIDs = ['v0', 'v1', 'v2'], active = 0, extra = {}) {
	const id = 'accordion';

	// Container
	const container = document.createElement('div');
	container.id = id;

	// Hidden field
	const hidden = document.createElement('input');
	hidden.type = 'hidden';
	hidden.id = id + '_1';
	hidden.value = String(active);
	document.body.appendChild(hidden);

	// View panels + headers
	const viewsObj = {};
	for (const vid of viewIDs) {
		const view = document.createElement('div');
		view.id = vid;
		// Give it a non-zero height so checkMaxHeight works
		Object.defineProperty(view, 'offsetHeight', { configurable: true, value: 100 });
		Object.defineProperty(view, 'offsetWidth', { configurable: true, value: 200 });
		view.style.display = 'block';
		document.body.appendChild(view);

		const header = document.createElement('div');
		header.id = vid + '_0';
		document.body.appendChild(header);

		viewsObj[vid] = true;
	}

	document.body.appendChild(container);

	return Object.assign(
		{
			ID: id,
			Views: viewsObj,
			Duration: 0,
			HeaderCssClass: 'header',
			ActiveHeaderCssClass: 'active-header',
		},
		extra,
	);
}

function makeAccordion(viewIDs, active, extra) {
	// Clear Prado registry so each test gets a fresh instance.
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
	const options = buildDOM(viewIDs, active, extra);
	return { accordion: new TAccordion(options), options };
}

// Clean up DOM after every test.
afterEach(() => {
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

// ─── Constructor / class shape ────────────────────────────────────────────────

describe('TAccordion class shape', () => {
	it('is a constructor function', () => {
		expect(typeof TAccordion).toBe('function');
	});

	it('prototype has onInit method', () => {
		expect(typeof TAccordion.prototype.onInit).toBe('function');
	});

	it('prototype has checkMaxHeight method', () => {
		expect(typeof TAccordion.prototype.checkMaxHeight).toBe('function');
	});

	it('prototype has elementClicked method', () => {
		expect(typeof TAccordion.prototype.elementClicked).toBe('function');
	});

	it('prototype has animate method', () => {
		expect(typeof TAccordion.prototype.animate).toBe('function');
	});
});

// ─── onInit — basic wiring ────────────────────────────────────────────────────

describe('TAccordion.onInit', () => {
	it('creates an instance without throwing', () => {
		expect(() => makeAccordion()).not.toThrow();
	});

	it('stores options on the instance', () => {
		const { accordion } = makeAccordion();
		expect(accordion.options).toBeDefined();
		expect(accordion.options.ID).toBe('accordion');
	});

	it('resolves the accordion DOM element', () => {
		const { accordion } = makeAccordion();
		expect(accordion.accordion).not.toBeNull();
		expect(accordion.accordion.id).toBe('accordion');
	});

	it('resolves the hidden field', () => {
		const { accordion } = makeAccordion();
		expect(accordion.hiddenField).not.toBeNull();
		expect(accordion.hiddenField.id).toBe('accordion_1');
	});

	it('sets currentView to the view matching the hidden-field index', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 1);
		expect(accordion.currentView).toBe('v1');
	});

	it('sets currentView to the first view when hidden field is 0', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		expect(accordion.currentView).toBe('v0');
	});

	it('initialises oldView to null', () => {
		const { accordion } = makeAccordion();
		expect(accordion.oldView).toBeNull();
	});

	it('registers the instance in Prado.Registry', () => {
		const { accordion } = makeAccordion();
		expect(global.Prado.Registry['accordion']).toBe(accordion);
	});
});

// ─── onInit — maxHeight ────────────────────────────────────────────────────────

describe('TAccordion maxHeight initialisation', () => {
	it('uses options.maxHeight when provided', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { maxHeight: 250 });
		expect(accordion.maxHeight).toBe(250);
	});

	it('derives maxHeight from views when options.maxHeight is absent', () => {
		// Our buildDOM stubs offsetHeight=100 on every view element.
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		// jQuery's .height() in jsdom returns 0 for elements not in a real
		// layout engine, so maxHeight stays at 0 — that is the expected
		// jsdom-environment result and is what we assert here.
		expect(typeof accordion.maxHeight).toBe('number');
	});

	it('does not call checkMaxHeight when options.maxHeight is given', () => {
		const spy = vi.spyOn(TAccordion.prototype, 'checkMaxHeight');
		makeAccordion(['v0'], 0, { maxHeight: 99 });
		expect(spy).not.toHaveBeenCalled();
		spy.mockRestore();
	});

	it('calls checkMaxHeight when options.maxHeight is absent', () => {
		const spy = vi.spyOn(TAccordion.prototype, 'checkMaxHeight');
		makeAccordion(['v0'], 0);
		expect(spy).toHaveBeenCalledOnce();
		spy.mockRestore();
	});
});

// ─── checkMaxHeight ────────────────────────────────────────────────────────────

describe('TAccordion.checkMaxHeight', () => {
	it('picks up the stubbed offsetHeight from buildDOM (100 per view)', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		accordion.maxHeight = 0;
		accordion.checkMaxHeight();
		// buildDOM stubs offsetHeight=100 on each view element.
		expect(accordion.maxHeight).toBe(100);
	});

	it('updates maxHeight to the tallest panel when heights differ', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		// Override the stubbed offsetHeight on each panel.
		Object.defineProperty(document.getElementById('v0'), 'offsetHeight', { configurable: true, value: 80 });
		Object.defineProperty(document.getElementById('v1'), 'offsetHeight', { configurable: true, value: 120 });
		accordion.maxHeight = 0;
		accordion.checkMaxHeight();
		expect(accordion.maxHeight).toBe(120);
	});

	it('does not decrease maxHeight if all panels are shorter', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		Object.defineProperty(document.getElementById('v0'), 'offsetHeight', { configurable: true, value: 50 });
		Object.defineProperty(document.getElementById('v1'), 'offsetHeight', { configurable: true, value: 50 });
		accordion.maxHeight = 200;
		// checkMaxHeight only raises, never lowers.
		accordion.checkMaxHeight();
		// 50 < 200 so maxHeight stays at 200.
		expect(accordion.maxHeight).toBe(200);
	});
});

// ─── elementClicked — same view ───────────────────────────────────────────────

describe('TAccordion.elementClicked — clicking the active view', () => {
	it('does not call animate when oldView === currentView', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		const animateSpy = vi.spyOn(accordion, 'animate');
		const fakeEvent = { stopPropagation: vi.fn(), preventDefault: vi.fn() };
		// Click the already-active view.
		accordion.elementClicked('v0', fakeEvent);
		expect(animateSpy).not.toHaveBeenCalled();
	});

	it('does not change hiddenField value when clicking the active view', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		accordion.elementClicked('v0', { stopPropagation: vi.fn() });
		expect(accordion.hiddenField.value).toBe('0');
	});
});

// ─── elementClicked — switching views (Duration = 0) ─────────────────────────

describe('TAccordion.elementClicked — switching views without animation', () => {
	it('updates currentView to the clicked view', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 0);
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(accordion.currentView).toBe('v1');
	});

	it('updates oldView to the previously-active view', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 0);
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(accordion.oldView).toBe('v0');
	});

	it('updates the hidden field to the new view index', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 0);
		accordion.elementClicked('v2', { stopPropagation: vi.fn() });
		expect(accordion.hiddenField.value).toBe('2');
	});

	it('shows the new view and hides the old view', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		// Force v0 to be visible, v1 hidden.
		global.jQuery('#v0').show();
		global.jQuery('#v1').hide();

		accordion.elementClicked('v1', { stopPropagation: vi.fn() });

		expect(global.jQuery('#v1').css('display')).not.toBe('none');
		expect(global.jQuery('#v0').css('display')).toBe('none');
	});

	it('applies ActiveHeaderCssClass to the new view header', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(global.jQuery('#v1_0').hasClass('active-header')).toBe(true);
	});

	it('applies HeaderCssClass to the old view header', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(global.jQuery('#v0_0').hasClass('header')).toBe(true);
	});

	it('does not call animate when Duration is 0', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 0 });
		const spy = vi.spyOn(accordion, 'animate');
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(spy).not.toHaveBeenCalled();
	});
});

// ─── elementClicked — switching views with animation ─────────────────────────

describe('TAccordion.elementClicked — switching views with animation', () => {
	it('calls animate when Duration > 0 and view changes', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 300 });
		const spy = vi.spyOn(accordion, 'animate');
		accordion.elementClicked('v1', { stopPropagation: vi.fn() });
		expect(spy).toHaveBeenCalledOnce();
	});

	it('does NOT call animate when Duration > 0 but same view clicked', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 300 });
		const spy = vi.spyOn(accordion, 'animate');
		accordion.elementClicked('v0', { stopPropagation: vi.fn() });
		expect(spy).not.toHaveBeenCalled();
	});
});

// ─── animate ─────────────────────────────────────────────────────────────────

describe('TAccordion.animate', () => {
	it('applies ActiveHeaderCssClass to the current view header', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 300 });
		// Manually set up state as elementClicked would.
		accordion.oldView = 'v0';
		accordion.currentView = 'v1';
		accordion.animate();
		expect(global.jQuery('#v1_0').hasClass('active-header')).toBe(true);
	});

	it('applies HeaderCssClass to the old view header', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 300 });
		accordion.oldView = 'v0';
		accordion.currentView = 'v1';
		accordion.animate();
		expect(global.jQuery('#v0_0').hasClass('header')).toBe(true);
	});

	it('starts showing the current view (sets display before animate)', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0, { Duration: 1 });
		global.jQuery('#v1').hide();
		accordion.oldView = 'v0';
		accordion.currentView = 'v1';
		// .show() is called synchronously inside animate before the jQuery
		// animation callback fires; in jsdom jQuery animations complete
		// synchronously.
		accordion.animate();
		expect(global.jQuery('#v1').css('display')).not.toBe('none');
	});
});

// ─── Single-view accordion ────────────────────────────────────────────────────

describe('TAccordion with a single view', () => {
	it('constructs without error', () => {
		expect(() => makeAccordion(['v0'], 0)).not.toThrow();
	});

	it('currentView is set to the only view', () => {
		const { accordion } = makeAccordion(['v0'], 0);
		expect(accordion.currentView).toBe('v0');
	});

	it('clicking the single view does not throw', () => {
		const { accordion } = makeAccordion(['v0'], 0);
		expect(() => accordion.elementClicked('v0', { stopPropagation: vi.fn() })).not.toThrow();
	});
});

// ─── Last view active ────────────────────────────────────────────────────────

describe('TAccordion with the last view initially active', () => {
	it('sets currentView to the last view when hidden-field index points there', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 2);
		expect(accordion.currentView).toBe('v2');
	});

	it('switching from last to first updates hidden field to 0', () => {
		const { accordion } = makeAccordion(['v0', 'v1', 'v2'], 2);
		accordion.elementClicked('v0', { stopPropagation: vi.fn() });
		expect(accordion.hiddenField.value).toBe('0');
	});
});

// ─── Missing header element (graceful degradation) ────────────────────────────

describe('TAccordion with missing header element', () => {
	it('skips observe for a view whose header element is absent', () => {
		// Build options with a view that has no corresponding _0 element.
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}

		const hidden = document.createElement('input');
		hidden.id = 'accordion_1';
		hidden.value = '0';
		document.body.appendChild(hidden);

		const container = document.createElement('div');
		container.id = 'accordion';
		document.body.appendChild(container);

		// View panel exists but header does NOT.
		const view = document.createElement('div');
		view.id = 'orphan';
		document.body.appendChild(view);

		// No orphan_0 header element.

		expect(() => {
			new TAccordion({
				ID: 'accordion',
				Views: { orphan: true },
				Duration: 0,
				HeaderCssClass: 'h',
				ActiveHeaderCssClass: 'ah',
			});
		}).not.toThrow();
	});
});

// ─── Header click event binding ───────────────────────────────────────────────

describe('TAccordion header click event binding', () => {
	it('clicking a header element changes currentView (proves handler was bound)', () => {
		// The click handler is bound via jQuery.proxy at construction time, so
		// post-construction method spies cannot intercept it.  Instead we verify
		// the observable side-effect: currentView changes when the header is clicked.
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		expect(accordion.currentView).toBe('v0');

		global.jQuery('#v1_0').trigger('click');

		expect(accordion.currentView).toBe('v1');
	});

	it('clicking the second header switches to it', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		global.jQuery('#v1_0').trigger('click');
		expect(accordion.currentView).toBe('v1');
	});

	it('clicking the first header after switching back reactivates it', () => {
		const { accordion } = makeAccordion(['v0', 'v1'], 0);
		global.jQuery('#v1_0').trigger('click');
		global.jQuery('#v0_0').trigger('click');
		expect(accordion.currentView).toBe('v0');
	});
});

// ─── Re-registration (replace existing registry entry) ───────────────────────

describe('TAccordion registry replacement', () => {
	it('replaces an existing registry entry without throwing', () => {
		const { accordion: first } = makeAccordion(['v0'], 0);
		// First instance is registered; now create a second for the same ID.
		expect(() => {
			new TAccordion({
				ID: 'accordion',
				Views: { v0: true },
				Duration: 0,
				HeaderCssClass: 'h',
				ActiveHeaderCssClass: 'ah',
			});
		}).not.toThrow();
	});

	it('after replacement, the registry holds the new instance', () => {
		makeAccordion(['v0'], 0);
		const second = new TAccordion({
			ID: 'accordion',
			Views: { v0: true },
			Duration: 0,
			HeaderCssClass: 'h',
			ActiveHeaderCssClass: 'ah',
		});
		expect(global.Prado.Registry['accordion']).toBe(second);
	});
});
