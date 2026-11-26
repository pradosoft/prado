/**
 * Tests for Prado.WebUI.TTextHighlighter and Prado.WebUI.TMarkdown (controls.js).
 * Source: framework/Web/Javascripts/source/prado/controls/controls.js
 *
 * Strategy
 * --------
 * highlight.js and clipboard.js are not loaded. Each test installs a recording
 * `hljs` and a `ClipboardJS` stub on the global object, renders the markup the
 * PHP control writes, and checks which elements were highlighted and which copy
 * buttons were added.
 *
 * ESM note: only tests/js/adapters/controls.js changes on ESM conversion.
 */

import { TTextHighlighter, TMarkdown } from '../adapters/controls.js';

let highlighted;
let numbered;
let configured;
let clipboards;

beforeEach(() => {
	highlighted = [];
	numbered = [];
	configured = [];
	clipboards = [];
	global.hljs = {
		configure(options) { configured.push(options); },
		highlightBlock(el) { highlighted.push(el); el.classList.add('hljs'); },
		lineNumbersBlock(el) { numbered.push(el); },
	};
	global.ClipboardJS = class {
		constructor(el) { clipboards.push(el); }
	};
	document.body.innerHTML = '';
});

afterEach(() => {
	delete global.hljs;
	delete global.ClipboardJS;
	document.body.innerHTML = '';
});

describe('TTextHighlighter', () => {
	function render() {
		document.body.innerHTML = '<pre id="code"><code id="code_code" class="php">$a = 1;</code></pre>';
		return document.getElementById('code_code');
	}

	it('highlights its code element with the tab replacement', () => {
		const code = render();
		new TTextHighlighter({ ID: 'code', tabsize: '  ', copycode: false, linenum: false });
		expect(highlighted).toEqual([code]);
		expect(configured).toEqual([{ tabReplace: '  ' }]);
		expect(numbered).toEqual([]);
		expect(clipboards).toEqual([]);
	});

	it('adds line numbers and a copy button', () => {
		const code = render();
		new TTextHighlighter({ ID: 'code', tabsize: '', copycode: true, linenum: true });
		expect(numbered).toEqual([code]);
		expect(configured).toEqual([{ tabReplace: '    ' }]);
		const btn = document.getElementById('code_copy');
		expect(btn.getAttribute('data-clipboard-text')).toBe('$a = 1;');
		expect(document.getElementById('code').firstChild).toBe(btn);
		expect(clipboards).toEqual([btn]);
	});
});

describe('TMarkdown', () => {
	function render() {
		document.body.innerHTML = '<div id="doc"><h1>Title</h1>'
			+ '<p>Inline <code>code</code></p>'
			+ '<pre><code class="language-js">var a = 1;</code></pre>'
			+ '<pre><code>plain</code></pre></div>';
		return [...document.querySelectorAll('#doc pre > code')];
	}

	it('highlights each fenced code block and leaves the prose and inline code', () => {
		const blocks = render();
		new TMarkdown({ ID: 'doc', tabsize: '    ', copycode: false, linenum: false });
		expect(highlighted).toEqual(blocks);
		expect(document.querySelector('#doc p code').classList.contains('hljs')).toBe(false);
		expect(document.querySelector('#doc h1').classList.contains('hljs')).toBe(false);
		expect(numbered).toEqual([]);
	});

	it('adds line numbers and a copy button to every block', () => {
		const blocks = render();
		new TMarkdown({ ID: 'doc', tabsize: '\t', copycode: true, linenum: true });
		expect(numbered).toEqual(blocks);
		expect(configured).toEqual([{ tabReplace: '\t' }]);
		const first = document.getElementById('doc_copy0');
		const second = document.getElementById('doc_copy1');
		expect(first.getAttribute('data-clipboard-text')).toBe('var a = 1;');
		expect(second.getAttribute('data-clipboard-text')).toBe('plain');
		expect(blocks[0].parentNode.firstChild).toBe(first);
		expect(blocks[0].parentNode.style.position).toBe('relative');
		expect(clipboards).toEqual([first, second]);
	});

	it('does nothing without its element', () => {
		new TMarkdown({ ID: 'missing', tabsize: '    ', copycode: true, linenum: true });
		expect(highlighted).toEqual([]);
		expect(configured).toEqual([]);
	});

	it('highlights nothing for Markdown without code blocks', () => {
		document.body.innerHTML = '<div id="doc"><p>Just text.</p></div>';
		new TMarkdown({ ID: 'doc', tabsize: '    ', copycode: false, linenum: false });
		expect(highlighted).toEqual([]);
	});
});
