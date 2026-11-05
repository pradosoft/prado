/**
 * Tests for Prado.Element.morph(), runScript(), getFocusSnapshot(), and the
 * morph argument of Prado.Element.replace().
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 * idiomorph: node_modules/idiomorph (the version pinned in composer.json)
 */

import { Element } from '../adapters/prado-core.js';
import { loadScript } from '../helpers/loadScript.js';

loadScript('node_modules/idiomorph/dist/idiomorph.js');

// jsdom runs an appended inline script in its own global; the scripts count
// their runs on <html>, which both globals reach.
const runs = (name) => +(document.documentElement.dataset[name] || 0);
const counter = (name) => `<script>document.documentElement.dataset.${name} = +(document.documentElement.dataset.${name} || 0) + 1;</script>`;

let root, savedNonce;

beforeEach(() => {
	savedNonce = Element._pageNonce;
	Element._pageNonce = '';
	root = document.createElement('div');
	root.id = 'morph-root';
	root.innerHTML = '<p id="text">old</p><span class="plain">plain</span><input id="field" value="one">';
	document.body.appendChild(root);
	for (const key of Object.keys(document.documentElement.dataset))
		delete document.documentElement.dataset[key];
});

afterEach(() => {
	Element._pageNonce = savedNonce;
	document.body.innerHTML = '';
});

describe('Prado.Element.morph', () => {
	it('returns false when the element is missing', () => {
		expect(Element.morph('no-such-id', '<p></p>', false)).toBe(false);
	});

	it('returns false when idiomorph is not loaded', () => {
		const saved = global.Idiomorph;
		global.Idiomorph = undefined;
		try {
			expect(Element.morph('morph-root', '<p>new</p>', false)).toBe(false);
		} finally {
			global.Idiomorph = saved;
		}
		expect(document.getElementById('text').textContent).toBe('old');
	});

	it('keeps the existing nodes and their listeners while updating them', () => {
		const text = document.getElementById('text');
		const plain = root.querySelector('.plain');
		let clicks = 0;
		plain.addEventListener('click', () => clicks++);
		expect(Element.morph('morph-root', '<p id="text" class="new">new</p><span class="plain">plain 2</span><input id="field" value="one">', false)).toBe(true);
		expect(document.getElementById('text')).toBe(text);
		expect(text.textContent).toBe('new');
		expect(text.className).toBe('new');
		expect(root.querySelector('.plain')).toBe(plain);
		plain.click();
		expect(clicks).toBe(1);
	});

	it('morphs the element itself when self is true', () => {
		Element.morph('morph-root', '<div id="morph-root" class="outer"><p id="text">new</p></div>', true);
		expect(document.getElementById('morph-root')).toBe(root);
		expect(root.className).toBe('outer');
		expect(root.children.length).toBe(1);
	});

	it('leaves a data-prado-morph="skip" subtree untouched', () => {
		root.innerHTML = '<div id="widget" data-prado-morph="skip"><b>client</b></div>';
		Element.morph('morph-root', '<div id="widget" data-prado-morph="skip">server</div>', false);
		expect(document.getElementById('widget').innerHTML).toBe('<b>client</b>');
	});

	it('creates SVG content in the SVG namespace', () => {
		Element.morph('morph-root', '<svg id="art"><circle id="dot" r="2"></circle></svg>', false);
		expect(document.getElementById('dot').namespaceURI).toBe('http://www.w3.org/2000/svg');
	});
});

describe('Prado.Element.morph scripts', () => {
	it('runs an added script once', () => {
		Element.morph('morph-root', '<p id="text">old</p>' + counter('added'), false);
		expect(runs('added')).toBe(1);
	});

	it('runs a script nested in added content once', () => {
		Element.morph('morph-root', '<p id="text">old</p><div><div id="deep">' + counter('nested') + '</div></div>', false);
		expect(runs('nested')).toBe(1);
	});

	it('does not run an unchanged script again', () => {
		const markup = '<p id="text">old</p>' + counter('same');
		Element.morph('morph-root', markup, false);
		Element.morph('morph-root', markup.replace('old', 'new'), false);
		expect(runs('same')).toBe(1);
	});

	it('runs a changed script once', () => {
		Element.morph('morph-root', '<script>document.documentElement.dataset.version = "1";</script>', false);
		Element.morph('morph-root', '<script>document.documentElement.dataset.version = "2";</script>', false);
		expect(document.documentElement.dataset.version).toBe('2');
	});

	it('runs scripts in document order', () => {
		const step = (n) => `<script>document.documentElement.dataset.order = (document.documentElement.dataset.order || '') + '${n}';</script>`;
		Element.morph('morph-root', '<div id="a">' + step(1) + '</div><div>' + step(2) + '<div id="b">' + step(3) + '</div></div>', false);
		expect(document.documentElement.dataset.order).toBe('123');
	});

	it('restores the type of an added script', () => {
		Element.morph('morph-root', '<script id="tpl" type="text/x-template"><b>x</b></script><script id="js">void 0;</script>', false);
		const tpl = document.getElementById('tpl');
		expect(tpl.getAttribute('type')).toBe('text/x-template');
		expect(tpl.hasAttribute(Element.MORPH_SCRIPT_TYPE)).toBe(false);
		expect(document.getElementById('js').hasAttribute('type')).toBe(false);
	});

	it('gives the scripts the page nonce', () => {
		Element._pageNonce = 'n0nce';
		Element.morph('morph-root', '<script id="s">void 0;</script>', false);
		expect(document.getElementById('s').nonce).toBe('n0nce');
	});
});

describe('Prado.Element.morph focused field', () => {
	it('takes the server value when the field is unchanged since the snapshot', () => {
		const field = document.getElementById('field');
		field.focus();
		const snapshot = Element.getFocusSnapshot();
		Element.morph('morph-root', '<p id="text">old</p><span class="plain">plain</span><input id="field" value="SERVER">', false, snapshot);
		expect(field.value).toBe('SERVER');
		expect(document.activeElement).toBe(field);
	});

	it('keeps what the user typed after the snapshot', () => {
		const field = document.getElementById('field');
		field.focus();
		const snapshot = Element.getFocusSnapshot();
		field.value = 'one two';
		Element.morph('morph-root', '<p id="text">old</p><span class="plain">plain</span><input id="field" value="SERVER">', false, snapshot);
		expect(field.value).toBe('one two');
		expect(document.activeElement).toBe(field);
	});

	it('keeps the focused value without a snapshot', () => {
		const field = document.getElementById('field');
		field.focus();
		Element.morph('morph-root', '<p id="text">old</p><span class="plain">plain</span><input id="field" value="SERVER">', false);
		expect(field.value).toBe('one');
	});

	it('updates a field without focus', () => {
		Element.morph('morph-root', '<p id="text">old</p><span class="plain">plain</span><input id="field" value="SERVER">', false);
		expect(document.getElementById('field').value).toBe('SERVER');
	});
});

describe('Prado.Element.getFocusSnapshot', () => {
	it('returns null without a focused field', () => {
		document.activeElement.blur();
		expect(Element.getFocusSnapshot()).toBeNull();
	});

	it('returns the focused field and its value', () => {
		const field = document.getElementById('field');
		field.focus();
		expect(Element.getFocusSnapshot()).toEqual({ element: field, value: 'one' });
	});
});

describe('Prado.Element.replace with morph', () => {
	it('morphs when morph is true', () => {
		const text = document.getElementById('text');
		Element.replace('morph-root', '<p id="text">new</p>', null, false, true);
		expect(document.getElementById('text')).toBe(text);
		expect(text.textContent).toBe('new');
	});

	it('replaces when morph is false', () => {
		const text = document.getElementById('text');
		Element.replace('morph-root', '<p id="text">new</p>', null, false, false);
		expect(document.getElementById('text')).not.toBe(text);
	});

	it('replaces when idiomorph is not loaded', () => {
		const saved = global.Idiomorph;
		global.Idiomorph = undefined;
		const text = document.getElementById('text');
		try {
			Element.replace('morph-root', '<p id="text">new</p>', null, false, true);
		} finally {
			global.Idiomorph = saved;
		}
		expect(document.getElementById('text')).not.toBe(text);
		expect(document.getElementById('text').textContent).toBe('new');
	});

	it('passes the callback request focus snapshot', () => {
		const field = document.getElementById('field');
		field.focus();
		const request = { focusSnapshot: Element.getFocusSnapshot() };
		Element.replace.call(request, 'morph-root', '<input id="field" value="SERVER">', null, false, true);
		expect(field.value).toBe('SERVER');
	});
});
