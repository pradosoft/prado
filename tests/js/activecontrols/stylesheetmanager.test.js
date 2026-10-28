/**
 * Tests for Prado.StyleSheetManager (ajax3.js): the CSP nonce of the elements a
 * callback response creates.
 * Source: framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 *
 * A callback response is rendered with its own request nonce, which the page's
 * Content-Security-Policy does not allow, so the stylesheet manager gives the
 * `<style>` and `<link>` elements it creates the nonce of the page.
 *
 * ESM note: only tests/js/adapters/ajax.js changes on ESM conversion.
 */

import '../adapters/ajax.js';

const manager = () => global.Prado.StyleSheetManager;

function addNonced(tag, nonce) {
	const el = document.createElement(tag);
	el.setAttribute('nonce', nonce);
	document.head.appendChild(el);
	return el;
}

afterEach(() => {
	document.head.innerHTML = '';
	document.body.innerHTML = '';
});

describe('Prado.StyleSheetManager.getPageNonce', () => {
	it('returns an empty string when the page has no nonce', () => {
		expect(manager().getPageNonce()).toBe('');
	});

	it('reads the nonce of a script, style, or link element', () => {
		for (const tag of ['script', 'style', 'link']) {
			const el = addNonced(tag, `n-${tag}`);
			expect(manager().getPageNonce()).toBe(`n-${tag}`);
			el.remove();
		}
	});

	it('prefers the nonce property, which the browser keeps after hiding the attribute', () => {
		const el = addNonced('script', '');
		el.nonce = 'hidden-value';
		expect(manager().getPageNonce()).toBe('hidden-value');
	});
});

describe('Prado.StyleSheetManager created elements', () => {
	it('gives a callback <style> the page nonce', () => {
		addNonced('script', 'page-nonce');
		manager().createStyleSheetCode('.a { color: red; }');
		const style = document.head.querySelector('style');
		expect(style.nonce).toBe('page-nonce');
		expect(style.textContent).toBe('.a { color: red; }');
	});

	it('gives a callback <link> the page nonce', () => {
		addNonced('script', 'page-nonce');
		const link = manager().createAssetElement('/a.css');
		expect(link.rel).toBe('stylesheet');
		expect(link.nonce).toBe('page-nonce');
	});

	it('leaves the nonce unset when the page has none', () => {
		manager().createStyleSheetCode('.a {}');
		expect(document.head.querySelector('style').nonce || '').toBe('');
		expect(manager().createAssetElement('/a.css').nonce || '').toBe('');
	});
});
