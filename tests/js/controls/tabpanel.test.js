/**
 * Tests for Prado.WebUI.TTabPanel (tabpanel.js).
 * Source: framework/Web/Javascripts/source/prado/controls/tabpanel.js
 *
 * DOM structure expected by TTabPanel:
 *   <input id="{ID}_1" …>        — hidden field, value = active tab index
 *   For each view ID in options.Views:
 *     <div   id="{viewID}">       — view content panel
 *     <div   id="{viewID}_0">     — tab header / clickable tab
 *
 * ESM note: only tests/js/adapters/tabpanel.js changes on ESM conversion.
 */

import { TTabPanel } from '../adapters/tabpanel.js';

// ─── helpers ─────────────────────────────────────────────────────────────────

/**
 * Build the minimal DOM and return an options object for TTabPanel.
 *
 * @param {string[]} viewIDs   - IDs for the tab panels, e.g. ['tab0','tab1']
 * @param {number}   active    - index of the initially-active tab
 * @param {boolean[]} vis      - visibility flags per view (default all true)
 * @param {object}   extra     - extra options merged into the returned object
 */
function buildDOM(
	viewIDs = ['tab0', 'tab1', 'tab2'],
	active = 0,
	vis = null,
	extra = {},
) {
	const id = 'tabpanel';
	if (!vis) vis = viewIDs.map(() => true);

	// Hidden field
	const hidden = document.createElement('input');
	hidden.type = 'hidden';
	hidden.id = id + '_1';
	hidden.value = String(active);
	document.body.appendChild(hidden);

	// View panels + tab headers
	for (const vid of viewIDs) {
		const panel = document.createElement('div');
		panel.id = vid;
		document.body.appendChild(panel);

		const header = document.createElement('div');
		header.id = vid + '_0';
		document.body.appendChild(header);
	}

	return Object.assign(
		{
			ID: id,
			Views: [...viewIDs],
			ViewsVis: [...vis],
			ActiveCssClass: 'active',
			NormalCssClass: 'normal',
			AutoSwitch: false,
		},
		extra,
	);
}

function makeTabPanel(viewIDs, active, vis, extra) {
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
	const options = buildDOM(viewIDs, active, vis, extra);
	return { tabpanel: new TTabPanel(options), options };
}

afterEach(() => {
	document.body.innerHTML = '';
	for (const k of Object.keys(global.Prado.Registry)) {
		delete global.Prado.Registry[k];
	}
});

// ─── Class shape ─────────────────────────────────────────────────────────────

describe('TTabPanel class shape', () => {
	it('is a constructor function', () => {
		expect(typeof TTabPanel).toBe('function');
	});

	it('prototype has onInit method', () => {
		expect(typeof TTabPanel.prototype.onInit).toBe('function');
	});

	it('prototype has elementClicked method', () => {
		expect(typeof TTabPanel.prototype.elementClicked).toBe('function');
	});
});

// ─── onInit — basic wiring ────────────────────────────────────────────────────

describe('TTabPanel.onInit', () => {
	it('constructs without throwing', () => {
		expect(() => makeTabPanel()).not.toThrow();
	});

	it('stores views array on the instance', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1']);
		expect(Array.isArray(tabpanel.views)).toBe(true);
		expect(tabpanel.views).toEqual(['tab0', 'tab1']);
	});

	it('stores viewsvis array on the instance', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0, [true, false]);
		expect(tabpanel.viewsvis).toEqual([true, false]);
	});

	it('stores activeCssClass on the instance', () => {
		const { tabpanel } = makeTabPanel();
		expect(tabpanel.activeCssClass).toBe('active');
	});

	it('stores normalCssClass on the instance', () => {
		const { tabpanel } = makeTabPanel();
		expect(tabpanel.normalCssClass).toBe('normal');
	});

	it('resolves hiddenField element', () => {
		const { tabpanel } = makeTabPanel();
		expect(tabpanel.hiddenField).not.toBeNull();
		expect(tabpanel.hiddenField.id).toBe('tabpanel_1');
	});

	it('registers instance in Prado.Registry', () => {
		const { tabpanel } = makeTabPanel();
		expect(global.Prado.Registry['tabpanel']).toBe(tabpanel);
	});
});

// ─── onInit — initial CSS classes ─────────────────────────────────────────────

describe('TTabPanel.onInit — initial CSS state', () => {
	it('adds activeCssClass to the active tab header', () => {
		makeTabPanel(['tab0', 'tab1'], 0);
		expect(global.jQuery('#tab0_0').hasClass('active')).toBe(true);
	});

	it('does not add normalCssClass to the active tab header', () => {
		makeTabPanel(['tab0', 'tab1'], 0);
		expect(global.jQuery('#tab0_0').hasClass('normal')).toBe(false);
	});

	it('adds normalCssClass to inactive tab headers', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		expect(global.jQuery('#tab1_0').hasClass('normal')).toBe(true);
		expect(global.jQuery('#tab2_0').hasClass('normal')).toBe(true);
	});

	it('shows the active tab panel', () => {
		makeTabPanel(['tab0', 'tab1'], 0);
		expect(global.jQuery('#tab0').css('display')).not.toBe('none');
	});

	it('hides inactive tab panels', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		expect(global.jQuery('#tab1').css('display')).toBe('none');
		expect(global.jQuery('#tab2').css('display')).toBe('none');
	});

	it('applies active class to second tab when active=1', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 1);
		expect(global.jQuery('#tab1_0').hasClass('active')).toBe(true);
		expect(global.jQuery('#tab0_0').hasClass('normal')).toBe(true);
	});

	it('shows the last tab when it is the active one', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 2);
		expect(global.jQuery('#tab2').css('display')).not.toBe('none');
		expect(global.jQuery('#tab0').css('display')).toBe('none');
		expect(global.jQuery('#tab1').css('display')).toBe('none');
	});
});

// ─── onInit — visibility flags ────────────────────────────────────────────────

describe('TTabPanel.onInit — ViewsVis flag', () => {
	it('does not bind click on a tab whose ViewsVis flag is false', () => {
		// Build DOM manually and spy on observe before construction.
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const options = buildDOM(['tab0', 'tab1'], 0, [true, false]);

		const observeSpy = vi.spyOn(TTabPanel.prototype, 'observe');
		new TTabPanel(options);
		// Only one observe call should have been made for the visible tab.
		const clickCalls = observeSpy.mock.calls.filter(
			([, evt]) => evt === 'click',
		);
		expect(clickCalls.length).toBe(1);
		observeSpy.mockRestore();
	});

	it('does bind click on a tab whose ViewsVis flag is true', () => {
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const options = buildDOM(['tab0', 'tab1'], 0, [true, true]);
		const observeSpy = vi.spyOn(TTabPanel.prototype, 'observe');
		new TTabPanel(options);
		const clickCalls = observeSpy.mock.calls.filter(
			([, evt]) => evt === 'click',
		);
		expect(clickCalls.length).toBe(2);
		observeSpy.mockRestore();
	});
});

// ─── onInit — AutoSwitch ──────────────────────────────────────────────────────

describe('TTabPanel.onInit — AutoSwitch', () => {
	it('binds mouseenter when AutoSwitch is true', () => {
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const options = buildDOM(['tab0', 'tab1'], 0, null, { AutoSwitch: true });
		const observeSpy = vi.spyOn(TTabPanel.prototype, 'observe');
		new TTabPanel(options);
		const mouseenterCalls = observeSpy.mock.calls.filter(
			([, evt]) => evt === 'mouseenter',
		);
		expect(mouseenterCalls.length).toBe(2); // one per visible tab
		observeSpy.mockRestore();
	});

	it('does NOT bind mouseenter when AutoSwitch is false', () => {
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const options = buildDOM(['tab0', 'tab1'], 0, null, { AutoSwitch: false });
		const observeSpy = vi.spyOn(TTabPanel.prototype, 'observe');
		new TTabPanel(options);
		const mouseenterCalls = observeSpy.mock.calls.filter(
			([, evt]) => evt === 'mouseenter',
		);
		expect(mouseenterCalls.length).toBe(0);
		observeSpy.mockRestore();
	});
});

// ─── elementClicked ───────────────────────────────────────────────────────────

describe('TTabPanel.elementClicked', () => {
	it('shows the clicked tab panel', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab1').css('display')).not.toBe('none');
	});

	it('hides previously-active panel', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab0').css('display')).toBe('none');
	});

	it('adds activeCssClass to the clicked tab header', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab1_0').hasClass('active')).toBe(true);
	});

	it('removes activeCssClass from previously-active header', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab0_0').hasClass('active')).toBe(false);
	});

	it('adds normalCssClass to previously-active header', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab0_0').hasClass('normal')).toBe(true);
	});

	it('updates hiddenField.value to the new tab index', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		tabpanel.elementClicked('tab2', {});
		expect(tabpanel.hiddenField.value).toBe('2');
	});

	it('switching back updates hiddenField to 0', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 1);
		tabpanel.elementClicked('tab0', {});
		expect(tabpanel.hiddenField.value).toBe('0');
	});

	it('clicking the already-active tab keeps it shown', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab0', {});
		expect(global.jQuery('#tab0').css('display')).not.toBe('none');
	});

	it('clicking the already-active tab keeps its active class', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		tabpanel.elementClicked('tab0', {});
		expect(global.jQuery('#tab0_0').hasClass('active')).toBe(true);
	});

	it('hides all non-clicked panels', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		tabpanel.elementClicked('tab1', {});
		expect(global.jQuery('#tab0').css('display')).toBe('none');
		expect(global.jQuery('#tab2').css('display')).toBe('none');
	});
});

// ─── Click event via DOM (event delegation) ───────────────────────────────────

describe('TTabPanel tab click via DOM event', () => {
	it('click on header switches panel (proves click handler was bound)', () => {
		// jQuery.proxy captures the method reference at construction time, so a
		// post-construction vi.spyOn cannot intercept it.  We verify the observable
		// side-effect instead: the active panel changes after the click.
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		global.jQuery('#tab1_0').trigger('click');
		expect(global.jQuery('#tab1').css('display')).not.toBe('none');
		expect(global.jQuery('#tab0').css('display')).toBe('none');
	});

	it('switches tab via click event — hidden field updated', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1'], 0);
		global.jQuery('#tab1_0').trigger('click');
		expect(tabpanel.hiddenField.value).toBe('1');
	});

	it('mouseenter switches panel when AutoSwitch is enabled', () => {
		// Same proxy-spy limitation: verify observable side-effect (panel switches).
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const options = buildDOM(['tab0', 'tab1'], 0, null, { AutoSwitch: true });
		new TTabPanel(options);
		document.getElementById('tab1_0').dispatchEvent(new Event('mouseenter', { bubbles: true }));
		expect(global.jQuery('#tab1').css('display')).not.toBe('none');
		expect(global.jQuery('#tab0').css('display')).toBe('none');
	});
});

// ─── Single tab ───────────────────────────────────────────────────────────────

describe('TTabPanel with a single tab', () => {
	it('constructs without error', () => {
		expect(() => makeTabPanel(['tab0'], 0)).not.toThrow();
	});

	it('shows the only panel', () => {
		makeTabPanel(['tab0'], 0);
		expect(global.jQuery('#tab0').css('display')).not.toBe('none');
	});

	it('clicking the single tab keeps it active', () => {
		const { tabpanel } = makeTabPanel(['tab0'], 0);
		tabpanel.elementClicked('tab0', {});
		expect(global.jQuery('#tab0_0').hasClass('active')).toBe(true);
	});
});

// ─── First and last tab boundary values ──────────────────────────────────────

describe('TTabPanel boundary: first and last tabs', () => {
	it('first tab (index 0) is initially active and shown', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		expect(global.jQuery('#tab0').css('display')).not.toBe('none');
		expect(global.jQuery('#tab0_0').hasClass('active')).toBe(true);
	});

	it('last tab is initially active when hidden field = last index', () => {
		makeTabPanel(['tab0', 'tab1', 'tab2'], 2);
		expect(global.jQuery('#tab2').css('display')).not.toBe('none');
		expect(global.jQuery('#tab2_0').hasClass('active')).toBe(true);
	});

	it('switching from last tab to first works correctly', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 2);
		tabpanel.elementClicked('tab0', {});
		expect(global.jQuery('#tab0').css('display')).not.toBe('none');
		expect(global.jQuery('#tab2').css('display')).toBe('none');
		expect(tabpanel.hiddenField.value).toBe('0');
	});

	it('switching from first tab to last works correctly', () => {
		const { tabpanel } = makeTabPanel(['tab0', 'tab1', 'tab2'], 0);
		tabpanel.elementClicked('tab2', {});
		expect(global.jQuery('#tab2').css('display')).not.toBe('none');
		expect(global.jQuery('#tab0').css('display')).toBe('none');
		expect(tabpanel.hiddenField.value).toBe('2');
	});
});

// ─── Missing DOM elements (graceful degradation) ──────────────────────────────

describe('TTabPanel with missing header element', () => {
	it('constructs without throwing when a view header is absent', () => {
		for (const k of Object.keys(global.Prado.Registry)) {
			delete global.Prado.Registry[k];
		}
		const hidden = document.createElement('input');
		hidden.id = 'tabpanel_1';
		hidden.value = '0';
		document.body.appendChild(hidden);

		// Panel exists but NO header (_0).
		const panel = document.createElement('div');
		panel.id = 'ghost';
		document.body.appendChild(panel);

		expect(() => {
			new TTabPanel({
				ID: 'tabpanel',
				Views: ['ghost'],
				ViewsVis: [true],
				ActiveCssClass: 'active',
				NormalCssClass: 'normal',
				AutoSwitch: false,
			});
		}).not.toThrow();
	});
});

// ─── Registry replacement ─────────────────────────────────────────────────────

describe('TTabPanel registry replacement', () => {
	it('replaces existing registry entry without throwing', () => {
		makeTabPanel(['tab0'], 0);
		expect(() => {
			new TTabPanel({
				ID: 'tabpanel',
				Views: ['tab0'],
				ViewsVis: [true],
				ActiveCssClass: 'active',
				NormalCssClass: 'normal',
				AutoSwitch: false,
			});
		}).not.toThrow();
	});

	it('after replacement the registry holds the new instance', () => {
		makeTabPanel(['tab0'], 0);
		const second = new TTabPanel({
			ID: 'tabpanel',
			Views: ['tab0'],
			ViewsVis: [true],
			ActiveCssClass: 'active',
			NormalCssClass: 'normal',
			AutoSwitch: false,
		});
		expect(global.Prado.Registry['tabpanel']).toBe(second);
	});
});
