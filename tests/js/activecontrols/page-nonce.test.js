/**
 * Tests for the page CSP nonce on client-created scripts:
 *   Prado.Element.getPageNonce(), executeScripts(), appendScriptBlock(),
 *   and Prado.ScriptManagerClass.createAssetElement().
 * The stylesheet manager's use of the page nonce is in stylesheetmanager.test.js.
 *
 * A callback response carries a new nonce, while the page keeps the policy of
 * its first load. Scripts created on the client carry the page nonce.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 *         framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 */

let Element;

beforeAll(async () => {
	// A page-rendered nonce'd script precedes prado.js, which caches its nonce on load.
	const pageScript = document.createElement('script');
	pageScript.id = 'page-nonce-script';
	pageScript.setAttribute('nonce', 'page-nonce');
	document.head.appendChild(pageScript);

	await import('../adapters/ajax.js');
	Element = global.Prado.Element;
	pageScript.remove();
});

afterEach(() => {
	Element._pageNonce = 'page-nonce';
});

// ─── Prado.Element.getPageNonce ──────────────────────────────────────────────

describe('Prado.Element.getPageNonce', () => {
	it('caches the page nonce when prado.js loads', () => {
		expect(document.querySelector('[nonce]')).toBeNull();
		expect(Element.getPageNonce()).toBe('page-nonce');
	});

	it('keeps the cached nonce when a callback adds an element with its own nonce', () => {
		const callbackScript = document.createElement('script');
		callbackScript.setAttribute('nonce', 'callback-nonce');
		document.head.prepend(callbackScript);

		expect(Element.getPageNonce()).toBe('page-nonce');

		callbackScript.remove();
	});

	it('reads the nonce property of the first nonce\'d element when nothing is cached', () => {
		Element._pageNonce = '';
		const style = document.createElement('style');
		style.setAttribute('nonce', 'style-nonce');
		document.head.appendChild(style);

		expect(Element.getPageNonce()).toBe('style-nonce');

		style.remove();
	});

	it('prefers the nonce property, which the browser keeps after hiding the attribute', () => {
		Element._pageNonce = '';
		const link = document.createElement('link');
		link.setAttribute('nonce', '');
		link.nonce = 'hidden-value';
		document.head.appendChild(link);

		expect(Element.getPageNonce()).toBe('hidden-value');

		link.remove();
	});

	it('returns an empty string when the page has no nonce\'d element', () => {
		Element._pageNonce = '';
		expect(Element.getPageNonce()).toBe('');
	});
});

// ─── Prado.Element.executeScripts ────────────────────────────────────────────

describe('Prado.Element.executeScripts', () => {
	let root;

	beforeEach(() => {
		root = document.createElement('div');
		document.body.appendChild(root);
	});

	afterEach(() => {
		root.remove();
	});

	it('gives the re-created script the page nonce in place of the callback nonce', () => {
		root.innerHTML = '<script nonce="callback-nonce" data-x="1">var __n = 1;</script>';
		const oldScript = root.querySelector('script');

		Element.executeScripts(root);

		const fresh = root.querySelector('script');
		expect(fresh).not.toBe(oldScript);
		expect(fresh.nonce).toBe('page-nonce');
		expect(fresh.getAttribute('data-x')).toBe('1');
		expect(fresh.text).toBe('var __n = 1;');
	});

	it('gives a script without a nonce the page nonce', () => {
		root.innerHTML = '<script>var __n = 2;</script>';

		Element.executeScripts(root);

		expect(root.querySelector('script').nonce).toBe('page-nonce');
	});

	it('re-creates the root when the root is a script', () => {
		root.innerHTML = '<script data-x="4">var __n = 4;</script>';
		const oldScript = root.firstChild;

		Element.executeScripts(oldScript);

		const fresh = root.firstChild;
		expect(fresh).not.toBe(oldScript);
		expect(fresh.getAttribute('data-x')).toBe('4');
		expect(fresh.nonce).toBe('page-nonce');
	});

	it('ignores a text node', () => {
		root.textContent = 'text';
		expect(() => Element.executeScripts(root.firstChild)).not.toThrow();
		expect(root.textContent).toBe('text');
	});

	it('leaves the nonce unset when the page has no nonce', () => {
		Element._pageNonce = '';
		root.innerHTML = '<script>var __n = 3;</script>';

		Element.executeScripts(root);

		expect(root.querySelector('script').hasAttribute('nonce')).toBe(false);
	});
});

// ─── Prado.Element.appendScriptBlock ─────────────────────────────────────────

describe('Prado.Element.appendScriptBlock', () => {
	let appended;
	let spy;

	beforeEach(() => {
		appended = null;
		spy = vi.spyOn(document.head, 'appendChild').mockImplementation(function (el) {
			appended = el;
			return HTMLElement.prototype.appendChild.call(this, el);
		});
	});

	afterEach(() => {
		spy.mockRestore();
	});

	it('gives the inline script block the page nonce', () => {
		const request = { extractContent: () => 'var __block = 1;' };

		Element.appendScriptBlock.call(request, 'b1');

		expect(appended.id).toBe('inline_b1');
		expect(appended.nonce).toBe('page-nonce');
		expect(appended.text).toBe('var __block = 1;');
		expect(appended.isConnected).toBe(false);
	});

	it('leaves the nonce unset when the page has no nonce', () => {
		Element._pageNonce = '';
		const request = { extractContent: () => 'var __block = 2;' };

		Element.appendScriptBlock.call(request, 'b2');

		expect(appended.hasAttribute('nonce')).toBe(false);
	});

	it('appends nothing when the boundary has no content', () => {
		const request = { extractContent: () => null };

		Element.appendScriptBlock.call(request, 'b3');

		expect(appended).toBeNull();
	});
});

// ─── Prado.ScriptManagerClass.createAssetElement ─────────────────────────────

describe('Prado.ScriptManagerClass.createAssetElement', () => {
	it('gives the callback script file the page nonce', () => {
		const asset = global.Prado.ScriptManager.createAssetElement('http://example.com/cb.js');

		expect(asset.tagName).toBe('SCRIPT');
		expect(asset.src).toBe('http://example.com/cb.js');
		expect(asset.nonce).toBe('page-nonce');
	});

	it('leaves the nonce unset when the page has no nonce', () => {
		Element._pageNonce = '';

		const asset = global.Prado.ScriptManager.createAssetElement('http://example.com/cb.js');

		expect(asset.hasAttribute('nonce')).toBe(false);
	});
});
