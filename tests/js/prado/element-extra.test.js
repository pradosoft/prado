/**
 * Tests for the previously uncovered parts of Prado.Element:
 *   j(), select(), setOptions(), replace(), evaluateScript(), evaluateNonceScript(),
 *   setAttribute (on* and hidden/inert/popover with false),
 *   and Selection.selectValues / checkValues / checkIndices.
 *
 * Source: framework/Web/Javascripts/source/prado/prado.js
 */

import { Element } from '../adapters/prado-core.js';

const Selection = Element.Selection;

// ─── Prado.Element.j ─────────────────────────────────────────────────────────

describe('Prado.Element.j', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'j-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('calls the named jQuery method on the element', () => {
		Element.j('j-test', 'addClass', ['highlight']);
		expect(el.classList.contains('highlight')).toBe(true);
	});

	it('calls hide() — sets display:none', () => {
		Element.j('j-test', 'hide', []);
		expect(el.style.display).toBe('none');
	});
});

// ─── Prado.Element.select ────────────────────────────────────────────────────

describe('Prado.Element.select', () => {
	it('is a no-op when the element does not exist', () => {
		expect(() => Element.select('non-existent-id', 'selectAll', null, 0)).not.toThrow();
	});

	it('delegates to Selection.selectValue for a <select> element', () => {
		const sel = document.createElement('select');
		sel.id = 'sel-test';
		['a', 'b', 'c'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		document.body.appendChild(sel);

		Element.select('sel-test', 'selectValue', 'b', 1);
		expect(sel.options[1].selected).toBe(true);

		document.body.removeChild(sel);
	});

	it('delegates to Selection.selectAll for a <select> element', () => {
		const sel = document.createElement('select');
		sel.id   = 'sel-all';
		sel.setAttribute('multiple', '');
		['x', 'y'].forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		document.body.appendChild(sel);

		Element.select('sel-all', 'selectAll', null, 2);
		expect(sel.options[0].selected).toBe(true);
		expect(sel.options[1].selected).toBe(true);

		document.body.removeChild(sel);
	});
});

// ─── Prado.Element.setAttribute — hidden / inert / popover false branch ──────

describe('Prado.Element.setAttribute — boolean-remove attributes', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'attr-bool-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('removes the "hidden" attribute when value is false', () => {
		el.setAttribute('hidden', '');
		Element.setAttribute('attr-bool-test', 'hidden', false);
		expect(el.hasAttribute('hidden')).toBe(false);
	});

	it('sets the "hidden" attribute when value is truthy', () => {
		Element.setAttribute('attr-bool-test', 'hidden', true);
		expect(el.hasAttribute('hidden')).toBe(true);
	});
});

// ─── Prado.Element.setAttribute — on* event handler branch ───────────────────

describe('Prado.Element.setAttribute — on* event handlers', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('button');
		el.id = 'attr-on-test';
		document.body.appendChild(el);
	});

	afterEach(() => { document.body.removeChild(el); });

	it('attaches an evaluated function as an onclick handler', () => {
		global.__testClicked = false;
		Element.setAttribute('attr-on-test', 'onclick', 'global.__testClicked = true;');
		el.onclick({ target: el });
		expect(global.__testClicked).toBe(true);
		delete global.__testClicked;
	});
});

// ─── Prado.Element.setOptions ─────────────────────────────────────────────────

describe('Prado.Element.setOptions', () => {
	let sel;

	beforeEach(() => {
		sel = document.createElement('select');
		sel.id = 'so-test';
		document.body.appendChild(sel);
	});

	afterEach(() => { document.body.removeChild(sel); });

	it('replaces all options in a <select> with the provided flat list', () => {
		Element.setOptions('so-test', [
			['Apple', 'apple'],
			['Banana', 'banana'],
		]);
		expect(sel.options).toHaveLength(2);
		expect(sel.options[0].value).toBe('apple');
		expect(sel.options[0].text).toBe('Apple');
		expect(sel.options[1].value).toBe('banana');
	});

	it('handles grouped options (three-element sub-arrays)', () => {
		Element.setOptions('so-test', [
			['Red',   'red',   'Colors'],
			['Blue',  'blue',  'Colors'],
		]);
		// The resulting DOM has an <optgroup label="Colors"> with two children.
		const group = sel.querySelector('optgroup[label="Colors"]');
		expect(group).not.toBeNull();
		expect(group.querySelectorAll('option')).toHaveLength(2);
	});

	it('is a no-op when the element does not exist', () => {
		expect(() => Element.setOptions('no-such-element', [['A', 'a']])).not.toThrow();
	});
});

// ─── Prado.Element.replace ───────────────────────────────────────────────────

describe('Prado.Element.replace', () => {
	let el;

	beforeEach(() => {
		el = document.createElement('div');
		el.id = 'replace-test';
		document.body.appendChild(el);
	});

	afterEach(() => {
		const el2 = document.getElementById('replace-test');
		if (el2) document.body.removeChild(el2);
	});

	it('sets innerHTML when no boundary is given and self is falsy', () => {
		Element.replace('replace-test', '<span>hello</span>');
		expect(document.getElementById('replace-test').innerHTML).toBe('<span>hello</span>');
	});

	it('replaces the element itself when self is true', () => {
		// jQuery replaceWith removes the original node and inserts the new HTML
		// in its place. We verify the original element is gone and the wrapper
		// contains the new content.
		const wrapper = document.createElement('div');
		wrapper.id = 'rw-outer';
		const inner = document.createElement('div');
		inner.id = 'rw-inner';
		wrapper.appendChild(inner);
		document.body.appendChild(wrapper);

		Element.replace('rw-inner', '<span>replaced</span>', null, true);

		// Original #rw-inner was replaced; wrapper now contains the <span>.
		expect(document.getElementById('rw-inner')).toBeNull();
		expect(wrapper.querySelector('span').textContent).toBe('replaced');

		document.body.removeChild(wrapper);
	});

	describe('inline scripts', () => {
		// jsdom runs an inserted inline script in its own global and shares the
		// document. The scripts count their runs on <html>, which both globals reach.
		const count = (key) => `<script>document.documentElement.dataset.${key} = +(document.documentElement.dataset.${key} || 0) + 1;</script>`;
		const runs = (key) => +(document.documentElement.dataset[key] || 0);
		let wrapper;

		beforeEach(() => {
			wrapper = document.createElement('div');
			document.body.appendChild(wrapper);
		});

		afterEach(() => {
			wrapper.remove();
			for (const key of ['newRuns', 'siblingRuns', 'nestedRuns'])
				delete document.documentElement.dataset[key];
		});

		it('runs a top-level script of the self content exactly once', () => {
			wrapper.innerHTML = '<div id="rs-target"></div>';

			Element.replace('rs-target', '<div id="rs-target">new</div>' + count('newRuns'), null, true);

			expect(runs('newRuns')).toBe(1);
			expect(document.getElementById('rs-target').textContent).toBe('new');
			expect(wrapper.querySelectorAll('script')).toHaveLength(1);
		});

		it('runs a nested script of the self content exactly once', () => {
			wrapper.innerHTML = '<div id="rs-target"></div>';

			Element.replace('rs-target', '<div id="rs-target"><span>new</span>' + count('nestedRuns') + '</div>', null, true);

			expect(runs('nestedRuns')).toBe(1);
		});

		it('does not re-run the scripts of the siblings of the replaced element', () => {
			wrapper.innerHTML = '<p>before</p><div id="rs-target"></div><p>after</p>';
			const siblingScript = () => {
				const script = document.createElement('script');
				script.text = 'document.documentElement.dataset.siblingRuns = +(document.documentElement.dataset.siblingRuns || 0) + 1;';
				return script;
			};
			const beforeScript = wrapper.firstChild.appendChild(siblingScript());
			wrapper.lastChild.appendChild(siblingScript());
			expect(runs('siblingRuns')).toBe(2);

			Element.replace('rs-target', '<div id="rs-target">new</div>' + count('newRuns'), null, true);

			expect(runs('siblingRuns')).toBe(2);
			expect(runs('newRuns')).toBe(1);
			expect(wrapper.firstChild.querySelector('script')).toBe(beforeScript);
			expect(wrapper.firstChild.firstChild.data).toBe('before');
			expect(wrapper.lastChild.firstChild.data).toBe('after');
		});

		it('keeps the order of the replacement nodes when the element is the first child', () => {
			wrapper.innerHTML = '<div id="rs-target"></div><p>after</p>';

			Element.replace('rs-target', 'text<b>bold</b>' + count('newRuns'), null, true);

			expect(wrapper.innerHTML).toMatch(/^text<b>bold<\/b><script>.*<\/script><p>after<\/p>$/);
			expect(runs('newRuns')).toBe(1);
		});

		it('runs a script of the inner content exactly once', () => {
			wrapper.innerHTML = '<div id="rs-target"></div>';

			Element.replace('rs-target', '<span>inner</span>' + count('newRuns'));

			expect(runs('newRuns')).toBe(1);
		});

		it('runs an inline script after an external script loads', async () => {
			wrapper.innerHTML = '<div id="rs-target"></div>';

			Element.replace('rs-target', '<script src="lib.js"></script>' + count('newRuns'));

			expect(runs('newRuns')).toBe(0);
			wrapper.querySelector('script[src]').dispatchEvent(new Event('load'));
			await Promise.resolve();
			expect(runs('newRuns')).toBe(1);
		});

		it('keeps the order across the inserted nodes of the self content', async () => {
			wrapper.innerHTML = '<div id="rs-target"></div>';

			Element.replace('rs-target', '<div id="rs-target"><script src="lib.js"></script></div>' + count('newRuns'), null, true);

			expect(runs('newRuns')).toBe(0);
			wrapper.querySelector('script[src]').dispatchEvent(new Event('load'));
			await Promise.resolve();
			expect(runs('newRuns')).toBe(1);
		});
	});

	describe('external scripts', () => {
		const runs = () => +(document.documentElement.dataset.extRuns || 0);
		const inline = '<script>document.documentElement.dataset.extRuns = +(document.documentElement.dataset.extRuns || 0) + 1;</script>';
		let wrapper;

		beforeEach(() => {
			wrapper = document.createElement('div');
			document.body.appendChild(wrapper);
		});

		afterEach(() => {
			wrapper.remove();
			delete document.documentElement.dataset.extRuns;
		});

		it('continues after an external script fails to load', async () => {
			wrapper.innerHTML = '<script src="missing.js"></script>' + inline;

			const done = Element.executeScripts(wrapper);
			expect(runs()).toBe(0);
			wrapper.querySelector('script[src]').dispatchEvent(new Event('error'));
			await done;

			expect(runs()).toBe(1);
		});

		it('does not wait for an async external script', async () => {
			wrapper.innerHTML = '<script src="lib.js" async></script>' + inline;

			await Element.executeScripts(wrapper);

			expect(runs()).toBe(1);
		});

		it('re-creates the external script with its attributes', () => {
			wrapper.innerHTML = '<script src="lib.js" data-x="1"></script>';
			const old = wrapper.firstChild;

			Element.executeScripts(wrapper);

			const fresh = wrapper.firstChild;
			expect(fresh).not.toBe(old);
			expect(fresh.getAttribute('src')).toBe('lib.js');
			expect(fresh.getAttribute('data-x')).toBe('1');
		});

		it.each([
			['<script src="a.js"></script>', true],
			['<script src="a.js" type="text/javascript"></script>', true],
			['<script src="a.js" type="application/javascript"></script>', true],
			['<script src="a.js" async></script>', false],
			['<script src="a.js" type="module"></script>', false],
			['<script src="a.js" nomodule></script>', false],
			['<script src="a.js" type="text/template"></script>', false],
			['<script>1</script>', false],
		])('isBlockingScript(%s) is %s', (html, expected) => {
			wrapper.innerHTML = html;
			expect(Element.isBlockingScript(wrapper.firstChild)).toBe(expected);
		});
	});

	// NOTE: Element.replace(id, content, boundary) calls this.extractContent(boundary)
	// where `this` is Prado.Element.  Prado.Element does NOT have an extractContent
	// method — that method lives on Prado.CallbackRequest.  The boundary path is
	// therefore only meaningful when called from a CallbackRequest context.
	// No test is written for that path here; it is exercised via
	// tests/js/activecontrols/callbackrequest.test.js.
});

// ─── Prado.Element.evaluateScript ────────────────────────────────────────────

// jsdom runs an appended inline script in its own global, shares the document,
// and reports the script's error to the window `error` event. The scripts below
// count their runs on <html>, which both globals reach.
const COUNT = 'document.documentElement.dataset.evalCount = +(document.documentElement.dataset.evalCount || 0) + 1;';

function evalCount() {
	return +(document.documentElement.dataset.evalCount || 0);
}

describe('Prado.Element.evaluateScript', () => {
	let savedNonce;
	// Marks script errors handled, as a page's error handler would, so jsdom does
	// not report them as uncaught.
	const handled = (event) => event.preventDefault();

	beforeEach(() => {
		savedNonce = Element._pageNonce;
		Element._pageNonce = '';
		window.addEventListener('error', handled);
	});

	afterEach(() => {
		Element._pageNonce = savedNonce;
		window.removeEventListener('error', handled);
		delete document.documentElement.dataset.evalCount;
		delete globalThis.Logger;
		vi.restoreAllMocks();
	});

	it('evaluates the content as global code when the page has no nonce', () => {
		const append = vi.spyOn(document.head, 'appendChild');
		Element.evaluateScript('var __evalResult = 7;');
		expect(globalThis.__evalResult).toBe(7);
		expect(append).not.toHaveBeenCalled();
		delete globalThis.__evalResult;
	});

	it('logs and rethrows an error of the evaluated content', () => {
		globalThis.Logger = { error: vi.fn() };
		expect(() => Element.evaluateScript('throw new RangeError("bad");')).toThrow(RangeError);
		expect(globalThis.Logger.error).toHaveBeenCalledWith('Error during evaluation of script "throw new RangeError("bad");"');
	});

	it('reads the content from the boundary of the request it runs on', () => {
		const request = { extractContent: vi.fn(() => COUNT) };
		Element.evaluateScript.call(request, null, 'b1');
		expect(request.extractContent).toHaveBeenCalledWith('b1');
		expect(evalCount()).toBe(1);
	});

	it('runs the content once in a script element with the page nonce', () => {
		Element._pageNonce = 'page-nonce';
		const append = vi.spyOn(document.head, 'appendChild');

		Element.evaluateScript(COUNT);

		expect(evalCount()).toBe(1);
		const script = append.mock.calls[0][0];
		expect(script.nonce).toBe('page-nonce');
		expect(script.isConnected).toBe(false);
	});

	it('logs and rethrows the error the nonce\'d script reports to the window', () => {
		Element._pageNonce = 'page-nonce';
		globalThis.Logger = { error: vi.fn() };

		expect(() => Element.evaluateScript(COUNT + 'throw new TypeError("nonce error");')).toThrow('nonce error');

		expect(evalCount()).toBe(1);
		expect(globalThis.Logger.error).toHaveBeenCalledTimes(1);
	});

	it('rethrows a syntax error of the nonce\'d script', () => {
		Element._pageNonce = 'page-nonce';
		// jsdom's script global has its own SyntaxError class; compare the name.
		expect(() => Element.evaluateScript('var = ;')).toThrow(expect.objectContaining({ name: 'SyntaxError' }));
	});

	it('evaluates the content when the policy blocks the nonce\'d script', () => {
		Element._pageNonce = 'page-nonce';
		// A blocked script does not run; this one is not inserted.
		const append = vi.spyOn(document.head, 'appendChild').mockImplementation((node) => node);

		Element.evaluateScript(COUNT);

		expect(append).toHaveBeenCalledTimes(1);
		expect(evalCount()).toBe(1);
	});

	it('does not evaluate again when the nonce\'d script ran and a nested script reported an error', () => {
		Element._pageNonce = 'page-nonce';

		// The script reports an error to the window, as a nested script would, and completes.
		Element.evaluateScript(COUNT
			+ 'window.dispatchEvent(new ErrorEvent("error", { error: new Error("nested") }));');

		expect(evalCount()).toBe(1);
	});

	it('marks each script, so an inner run does not mark the outer script as run', () => {
		Element._pageNonce = 'page-nonce';
		// The document is shared, so it hands Prado to jsdom's script global.
		document.__prado = global.Prado;

		expect(() => Element.evaluateScript(
			'document.__prado.Element.evaluateScript(' + JSON.stringify(COUNT) + ');'
			+ 'throw new Error("outer");'
		)).toThrow('outer');
		expect(evalCount()).toBe(1);

		delete document.__prado;
	});
});

// ─── Prado.Element.evaluateNonceScript ───────────────────────────────────────

describe('Prado.Element.evaluateNonceScript', () => {
	afterEach(() => { vi.restoreAllMocks(); });

	it('returns true and removes the script after it runs', () => {
		const append = vi.spyOn(document.head, 'appendChild');
		expect(Element.evaluateNonceScript('1;', 'n1')).toBe(true);
		const script = append.mock.calls[0][0];
		expect(script.nonce).toBe('n1');
		expect(script.text).toBe('1;\n;document.currentScript.dataset.pradoRan = \'1\';');
		expect(script.isConnected).toBe(false);
	});

	it('returns false when the script does not run', () => {
		vi.spyOn(document.head, 'appendChild').mockImplementation((node) => node);
		expect(Element.evaluateNonceScript('1;', 'n1')).toBe(false);
	});

	it('removes its window error listener', () => {
		const remove = vi.spyOn(window, 'removeEventListener');
		Element.evaluateNonceScript('1;', 'n1');
		expect(remove).toHaveBeenCalledWith('error', expect.any(Function));
	});
});

// ─── Selection.selectValues / checkValues / checkIndices ─────────────────────

describe('Prado.Element.Selection.selectValues', () => {
	function makeSelect(values) {
		const sel = document.createElement('select');
		sel.setAttribute('multiple', '');
		values.forEach((v) => {
			const opt = document.createElement('option');
			opt.value = v;
			sel.appendChild(opt);
		});
		return sel;
	}

	it('selects each value in the array', () => {
		const sel = makeSelect(['a', 'b', 'c']);
		Selection.selectValues([sel], ['a', 'c']);
		expect(sel.options[0].selected).toBe(true);
		expect(sel.options[1].selected).toBe(false);
		expect(sel.options[2].selected).toBe(true);
	});
});

describe('Prado.Element.Selection.checkValues', () => {
	it('checks each element whose value is in the array', () => {
		const boxes = [
			{ value: 'x', checked: false },
			{ value: 'y', checked: false },
			{ value: 'z', checked: false },
		];
		Selection.checkValues(boxes, ['x', 'z']);
		expect(boxes[0].checked).toBe(true);
		expect(boxes[1].checked).toBe(false);
		expect(boxes[2].checked).toBe(true);
	});
});

describe('Prado.Element.Selection.checkIndices', () => {
	it('checks each element at the given indices', () => {
		const boxes = [
			{ checked: false },
			{ checked: false },
			{ checked: false },
		];
		Selection.checkIndices(boxes, [0, 2]);
		expect(boxes[0].checked).toBe(true);
		expect(boxes[1].checked).toBe(false);
		expect(boxes[2].checked).toBe(true);
	});
});
