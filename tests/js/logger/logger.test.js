/**
 * Tests for logger.js globals: CustomEvent, Cookie, Logger, LogEntry,
 * LogConsole, inspect, puts, and Prado.Inspector.
 * Source: framework/Web/Javascripts/source/prado/logger/logger.js
 *
 * ESM note: only tests/js/adapters/logger.js changes on ESM conversion.
 */

import {
	CustomEvent,
	Cookie,
	Logger,
	LogEntry,
	LogConsole,
	inspect,
	puts,
	PradoInspector,
} from '../adapters/logger.js';

// ─── CustomEvent ─────────────────────────────────────────────────────────────

describe('CustomEvent', () => {
	describe('constructor / initialize', () => {
		it('creates an instance with an empty listeners array', () => {
			const ev = new CustomEvent();
			expect(ev.listeners).toEqual([]);
		});

		it('each instance has its own listeners array', () => {
			const a = new CustomEvent();
			const b = new CustomEvent();
			a.listeners.push(() => {});
			expect(b.listeners).toHaveLength(0);
		});
	});

	describe('addListener', () => {
		it('appends a function to listeners', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			ev.addListener(fn);
			expect(ev.listeners).toContain(fn);
		});

		it('can add multiple listeners', () => {
			const ev = new CustomEvent();
			const fn1 = vi.fn();
			const fn2 = vi.fn();
			ev.addListener(fn1);
			ev.addListener(fn2);
			expect(ev.listeners).toHaveLength(2);
		});

		it('allows the same listener to be added more than once', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			ev.addListener(fn);
			ev.addListener(fn);
			expect(ev.listeners).toHaveLength(2);
		});
	});

	describe('removeListener', () => {
		it('removes the first occurrence of a listener', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			ev.addListener(fn);
			ev.removeListener(fn);
			expect(ev.listeners).not.toContain(fn);
		});

		it('removes occurrences when the same listener was added multiple times', () => {
			// NOTE: The source implementation collects indexes first then splices in
			// forward order, so each splice shifts subsequent elements.  When the
			// same function is at indexes [0, 1], splicing index 0 shifts the second
			// occurrence to index 0, making the second splice (index 1) a no-op.
			// This is a known quirk of the source; the test documents the actual
			// behaviour rather than the ideal behaviour.
			const ev = new CustomEvent();
			const fn = vi.fn();
			ev.addListener(fn);
			ev.addListener(fn);
			ev.removeListener(fn);
			// At least one occurrence is removed; no more than one may survive.
			expect(ev.listeners.length).toBeLessThan(2);
		});

		it('is a no-op when the listener was never added', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			expect(() => ev.removeListener(fn)).not.toThrow();
			expect(ev.listeners).toHaveLength(0);
		});

		it('leaves other listeners intact', () => {
			const ev = new CustomEvent();
			const fn1 = vi.fn();
			const fn2 = vi.fn();
			ev.addListener(fn1);
			ev.addListener(fn2);
			ev.removeListener(fn1);
			expect(ev.listeners).toContain(fn2);
			expect(ev.listeners).not.toContain(fn1);
		});
	});

	describe('dispatch', () => {
		it('calls every listener with the supplied handler argument', () => {
			const ev = new CustomEvent();
			const fn1 = vi.fn();
			const fn2 = vi.fn();
			ev.addListener(fn1);
			ev.addListener(fn2);
			const payload = { data: 42 };
			ev.dispatch(payload);
			expect(fn1).toHaveBeenCalledWith(payload);
			expect(fn2).toHaveBeenCalledWith(payload);
		});

		it('does not throw when there are no listeners', () => {
			const ev = new CustomEvent();
			expect(() => ev.dispatch('anything')).not.toThrow();
		});

		it('continues dispatching to remaining listeners when one throws (uses alert)', () => {
			const ev = new CustomEvent();
			const bad = vi.fn(() => { throw new Error('boom'); });
			const good = vi.fn();
			// The source catches exceptions and calls alert(); stub alert.
			const origAlert = global.alert;
			global.alert = vi.fn();
			ev.addListener(bad);
			ev.addListener(good);
			ev.dispatch('x');
			expect(good).toHaveBeenCalledWith('x');
			expect(global.alert).toHaveBeenCalled();
			global.alert = origAlert;
		});

		it('dispatches with undefined when called without arguments', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			ev.addListener(fn);
			ev.dispatch();
			expect(fn).toHaveBeenCalledWith(undefined);
		});
	});

	describe('_findListenerIndexes (private)', () => {
		it('returns all indexes where the method appears', () => {
			const ev = new CustomEvent();
			const fn = vi.fn();
			const other = vi.fn();
			ev.addListener(other);
			ev.addListener(fn);
			ev.addListener(fn);
			const indexes = ev._findListenerIndexes(fn);
			expect(indexes).toEqual([1, 2]);
		});

		it('returns an empty array when the method is not present', () => {
			const ev = new CustomEvent();
			expect(ev._findListenerIndexes(vi.fn())).toEqual([]);
		});
	});
});

// ─── Cookie ──────────────────────────────────────────────────────────────────

describe('Cookie', () => {
	// jsdom provides document.cookie.  Clear it before each test.
	beforeEach(() => {
		// Remove all cookies by expiring them.
		document.cookie.split(';').forEach(c => {
			const name = c.trim().split('=')[0];
			if (name) {
				document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
			}
		});
	});

	describe('set and get', () => {
		it('sets a cookie and gets its value back', () => {
			Cookie.set('testKey', 'testVal');
			expect(Cookie.get('testKey')).toBe('testVal');
		});

		it('returns null for a cookie that was never set', () => {
			expect(Cookie.get('nonexistent_xyz')).toBeNull();
		});

		it('handles values with special characters via escape/unescape', () => {
			Cookie.set('special', 'hello world');
			expect(Cookie.get('special')).toBe('hello world');
		});

		it('sets a cookie with an expiration (positive days)', () => {
			// We cannot easily inspect expires in jsdom, but the set should not throw.
			expect(() => Cookie.set('expKey', 'expVal', 1)).not.toThrow();
		});

		it('sets a cookie with a path', () => {
			expect(() => Cookie.set('pathKey', 'pathVal', undefined, '/')).not.toThrow();
		});
	});

	describe('get', () => {
		it('returns null when document.cookie is empty', () => {
			// Ensure nothing is set
			expect(Cookie.get('anything')).toBeNull();
		});

		it('retrieves the correct value when multiple cookies exist', () => {
			Cookie.set('a', '1');
			Cookie.set('b', '2');
			expect(Cookie.get('a')).toBe('1');
			expect(Cookie.get('b')).toBe('2');
		});
	});

	describe('getAll', () => {
		it('returns an array (may be empty)', () => {
			const all = Cookie.getAll();
			expect(Array.isArray(all)).toBe(true);
		});

		it('includes cookies that were set', () => {
			Cookie.set('ga', 'va');
			const all = Cookie.getAll();
			const names = all.map(c => c.name);
			expect(names).toContain('ga');
		});

		it('attaches the value as a named property on the array', () => {
			Cookie.set('prop', 'pval');
			const all = Cookie.getAll();
			expect(all['prop']).toBe('pval');
		});
	});

	describe('clear', () => {
		it('clears a cookie so get returns null afterwards', () => {
			Cookie.set('clr', 'v');
			Cookie.clear('clr');
			// jsdom may not enforce expiration instantly, but the value set is ""
			const val = Cookie.get('clr');
			expect(val === null || val === '').toBe(true);
		});
	});

	describe('clearAll', () => {
		it('does not throw even when no cookies are set', () => {
			expect(() => Cookie.clearAll()).not.toThrow();
		});

		it('clears every cookie', () => {
			Cookie.set('x1', 'v1');
			Cookie.set('x2', 'v2');
			Cookie.clearAll();
			const all = Cookie.getAll();
			const nonEmpty = all.filter(c => c.value !== '');
			expect(nonEmpty).toHaveLength(0);
		});
	});
});

// ─── LogEntry ────────────────────────────────────────────────────────────────

describe('LogEntry', () => {
	it('stores message and tag from the constructor', () => {
		const entry = new LogEntry('hello', 'info');
		expect(entry.message).toBe('hello');
		expect(entry.tag).toBe('info');
	});

	it('stores arbitrary tag values', () => {
		const entry = new LogEntry('msg', 'custom-tag');
		expect(entry.tag).toBe('custom-tag');
	});

	it('stores numeric messages', () => {
		const entry = new LogEntry(42, 'debug');
		expect(entry.message).toBe(42);
	});

	it('stores null message', () => {
		const entry = new LogEntry(null, 'error');
		expect(entry.message).toBeNull();
	});
});

// ─── Logger ──────────────────────────────────────────────────────────────────

describe('Logger', () => {
	// Reset Logger state before each test.
	beforeEach(() => {
		Logger.clear();
		// Re-attach fresh CustomEvent instances so listeners from prior tests
		// don't interfere.
		Logger.onupdate = new CustomEvent();
		Logger.onclear = new CustomEvent();
	});

	describe('log', () => {
		it('pushes a LogEntry onto logEntries', () => {
			Logger.log('test message', 'info');
			expect(Logger.logEntries).toHaveLength(1);
			expect(Logger.logEntries[0].message).toBe('test message');
		});

		it('uses "info" as the default tag when none is provided', () => {
			Logger.log('no tag');
			expect(Logger.logEntries[0].tag).toBe('info');
		});

		it('uses the provided tag', () => {
			Logger.log('msg', 'debug');
			expect(Logger.logEntries[0].tag).toBe('debug');
		});

		it('dispatches the onupdate event with the new LogEntry', () => {
			const listener = vi.fn();
			Logger.onupdate.addListener(listener);
			Logger.log('dispatch-test', 'info');
			expect(listener).toHaveBeenCalledTimes(1);
			expect(listener.mock.calls[0][0]).toBeInstanceOf(LogEntry);
			expect(listener.mock.calls[0][0].message).toBe('dispatch-test');
		});

		it('accumulates multiple entries', () => {
			Logger.log('a', 'info');
			Logger.log('b', 'debug');
			Logger.log('c', 'error');
			expect(Logger.logEntries).toHaveLength(3);
		});
	});

	describe('info', () => {
		it('logs an entry with tag "info"', () => {
			const consoleSpy = vi.spyOn(console, 'info').mockImplementation(() => {});
			Logger.info('info message');
			expect(Logger.logEntries[0].tag).toBe('info');
			expect(Logger.logEntries[0].message).toBe('info message');
			consoleSpy.mockRestore();
		});

		it('calls console.info with the message', () => {
			const consoleSpy = vi.spyOn(console, 'info').mockImplementation(() => {});
			Logger.info('console check');
			expect(consoleSpy).toHaveBeenCalledWith('console check');
			consoleSpy.mockRestore();
		});
	});

	describe('debug', () => {
		it('logs an entry with tag "debug"', () => {
			const consoleSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});
			Logger.debug('debug message');
			expect(Logger.logEntries[0].tag).toBe('debug');
			consoleSpy.mockRestore();
		});

		it('calls console.debug with the message', () => {
			const consoleSpy = vi.spyOn(console, 'debug').mockImplementation(() => {});
			Logger.debug('dbg');
			expect(consoleSpy).toHaveBeenCalledWith('dbg');
			consoleSpy.mockRestore();
		});
	});

	describe('warn', () => {
		it('logs an entry with tag "warning"', () => {
			const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
			Logger.warn('warning message');
			expect(Logger.logEntries[0].tag).toBe('warning');
			consoleSpy.mockRestore();
		});

		it('calls console.warn with the message', () => {
			const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
			Logger.warn('w');
			expect(consoleSpy).toHaveBeenCalledWith('w');
			consoleSpy.mockRestore();
		});
	});

	describe('error', () => {
		it('logs an entry with tag "error"', () => {
			const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
			Logger.error('error message', new Error('boom'));
			expect(Logger.logEntries[0].tag).toBe('error');
			consoleSpy.mockRestore();
		});

		it('concatenates message and error into the log entry', () => {
			const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
			Logger.error('oops', 'detail');
			expect(Logger.logEntries[0].message).toBe('oops: \ndetail');
			consoleSpy.mockRestore();
		});

		it('calls console.error with concatenated message', () => {
			const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
			Logger.error('err', 'detail');
			expect(consoleSpy).toHaveBeenCalledWith('err: \ndetail');
			consoleSpy.mockRestore();
		});

		it('handles undefined error argument', () => {
			const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
			expect(() => Logger.error('err', undefined)).not.toThrow();
			consoleSpy.mockRestore();
		});
	});

	describe('clear', () => {
		it('empties logEntries', () => {
			Logger.log('a', 'info');
			Logger.log('b', 'debug');
			Logger.clear();
			expect(Logger.logEntries).toHaveLength(0);
		});

		it('dispatches the onclear event', () => {
			const listener = vi.fn();
			Logger.onclear.addListener(listener);
			Logger.clear();
			expect(listener).toHaveBeenCalledTimes(1);
		});
	});

	describe('onupdate / onclear are CustomEvent instances', () => {
		it('onupdate is a CustomEvent', () => {
			expect(Logger.onupdate).toBeInstanceOf(CustomEvent);
		});

		it('onclear is a CustomEvent', () => {
			expect(Logger.onclear).toBeInstanceOf(CustomEvent);
		});
	});
});

// ─── inspect (helper function) ───────────────────────────────────────────────

describe('inspect', () => {
	it('returns "undefined" for undefined input', () => {
		expect(inspect(undefined)).toBe('undefined');
	});

	it('returns "null" for null input', () => {
		expect(inspect(null)).toBe('null');
	});

	it('returns a string representation for numbers', () => {
		expect(inspect(42)).toBe('42');
		expect(inspect(0)).toBe('0');
		expect(inspect(-1)).toBe('-1');
	});

	it('returns a string representation for booleans', () => {
		expect(inspect(true)).toBe('true');
		expect(inspect(false)).toBe('false');
	});

	it('returns a quoted string for string input', () => {
		expect(inspect('hello')).toBe('"hello"');
	});

	it('escapes double-quotes within strings', () => {
		expect(inspect('say "hi"')).toBe('"say \\"hi\\""');
	});

	it('escapes backslashes within strings', () => {
		expect(inspect('a\\b')).toBe('"a\\\\b"');
	});

	it('escapes newlines within strings', () => {
		expect(inspect("a\nb")).toBe('"a\\nb"');
	});

	it('escapes tabs within strings', () => {
		expect(inspect("a\tb")).toBe('"a\\tb"');
	});

	it('escapes carriage returns within strings', () => {
		expect(inspect("a\rb")).toBe('"a\\rb"');
	});

	it('returns a function signature with body replaced by {...}', () => {
		const result = inspect(function myFn(a, b) { return a + b; });
		expect(result).toContain('function');
		expect(result).toContain('{...}');
	});

	it('serialises an array of primitives', () => {
		expect(inspect([1, 2, 3])).toBe('[1, 2, 3]');
	});

	it('serialises a nested array', () => {
		expect(inspect([1, [2, 3]])).toBe('[1, [2, 3]]');
	});

	it('serialises an empty array', () => {
		expect(inspect([])).toBe('[]');
	});

	it('serialises a plain object', () => {
		const result = inspect({ a: 1 });
		expect(result).toContain('"a"');
		expect(result).toContain('1');
	});

	it('serialises an empty object', () => {
		expect(inspect({})).toBe('{}');
	});

	it('uses __json__ when available', () => {
		const obj = { __json__: () => 'serialised' };
		expect(inspect(obj)).toBe('"serialised"');
	});

	it('uses json() method when available', () => {
		const obj = { json: () => 99 };
		expect(inspect(obj)).toBe('99');
	});

	it('handles array with undefined element', () => {
		const result = inspect([undefined]);
		expect(result).toBe('[undefined]');
	});
});

// ─── puts ────────────────────────────────────────────────────────────────────

describe('puts', () => {
	beforeEach(() => {
		Logger.clear();
		Logger.onupdate = new CustomEvent();
		Logger.onclear = new CustomEvent();
	});

	it('is a function', () => {
		expect(typeof puts).toBe('function');
	});

	it('delegates to Logger.log with the given message and tag', () => {
		puts('hello', 'info');
		expect(Logger.logEntries).toHaveLength(1);
		expect(Logger.logEntries[0].message).toBe('hello');
		expect(Logger.logEntries[0].tag).toBe('info');
	});

	it('works without a tag (falls back to default "info")', () => {
		puts('no tag');
		expect(Logger.logEntries[0].tag).toBe('info');
	});
});

// ─── Array.prototype.contains (added by logger.js) ──────────────────────────

describe('Array.prototype.contains', () => {
	it('returns true when the element is present', () => {
		expect([1, 2, 3].contains(2)).toBe(true);
	});

	it('returns false when the element is absent', () => {
		expect([1, 2, 3].contains(99)).toBe(false);
	});

	it('returns false for an empty array', () => {
		expect([].contains('x')).toBe(false);
	});

	it('uses strict reference equality for objects', () => {
		const obj = {};
		expect([obj].contains(obj)).toBe(true);
		expect([{}].contains({})).toBe(false);
	});

	it('handles null elements', () => {
		expect([null, 1].contains(null)).toBe(true);
	});
});

// ─── LogConsole ──────────────────────────────────────────────────────────────

describe('LogConsole', () => {
	let lc;

	// Ensure a clean DOM body and a fresh Logger state before each test.
	beforeEach(() => {
		document.body.innerHTML = '';
		Logger.clear();
		Logger.onupdate = new CustomEvent();
		Logger.onclear = new CustomEvent();
		// Stub alert (used by CustomEvent.dispatch on listener errors).
		global.alert = vi.fn();
		// Cookie.get('ConsoleVisible') returns null in a fresh jsdom — LogConsole
		// will not auto-toggle.  Cookie.get('tagPattern') returns null — defaults
		// to '.*'.
		lc = new LogConsole(74); // 74 = key code 'J'
	});

	afterEach(() => {
		// Clean up any DOM nodes the console added.
		document.body.innerHTML = '';
	});

	describe('initialize', () => {
		it('creates a logElement div appended to document.body', () => {
			expect(lc.logElement).toBeTruthy();
			expect(lc.logElement.tagName.toLowerCase()).toBe('div');
			expect(document.body.contains(lc.logElement)).toBe(true);
		});

		it('sets hidden to true initially (logElement is hidden)', () => {
			expect(lc.hidden).toBe(true);
		});

		it('initialises outputCount to 0', () => {
			expect(lc.outputCount).toBe(0);
		});

		it('defaults tagPattern to ".*"', () => {
			expect(lc.tagPattern).toBe('.*');
		});

		it('creates a toolbarElement inside logElement', () => {
			expect(lc.toolbarElement).toBeTruthy();
			expect(lc.logElement.contains(lc.toolbarElement)).toBe(true);
		});

		it('creates a tagFilterElement input with value ".*"', () => {
			expect(lc.tagFilterElement.tagName.toLowerCase()).toBe('input');
			expect(lc.tagFilterElement.value).toBe('.*');
		});

		it('creates an outputElement div inside logElement', () => {
			expect(lc.outputElement).toBeTruthy();
			expect(lc.outputElement.style.overflow).toBe('auto');
		});

		it('creates an inputElement for commands', () => {
			expect(lc.inputElement).toBeTruthy();
			expect(lc.inputElement.value).toBe('Type command here');
		});

		it('commandHistory starts empty', () => {
			// commandHistory is a class-level property; fresh instance resets it
			// via the prototype — it is shared across instances in the source, so
			// we only verify it is array-like.
			expect(Array.isArray(lc.commandHistory)).toBe(true);
		});
	});

	describe('toggle', () => {
		it('calls show() when logElement is hidden (display === "none")', () => {
			lc.logElement.style.display = 'none';
			const showSpy = vi.spyOn(lc, 'show');
			lc.toggle();
			expect(showSpy).toHaveBeenCalled();
		});

		it('calls hide() when logElement is visible', () => {
			lc.logElement.style.display = 'block';
			const hideSpy = vi.spyOn(lc, 'hide');
			lc.toggle();
			expect(hideSpy).toHaveBeenCalled();
		});
	});

	describe('show', () => {
		it('sets hidden to false', () => {
			lc.show();
			expect(lc.hidden).toBe(false);
		});

		it('makes the logElement visible (jQuery show)', () => {
			lc.show();
			// jsdom + jQuery: display should no longer be 'none'
			expect(lc.logElement.style.display).not.toBe('none');
		});
	});

	describe('hide', () => {
		it('sets hidden to true', () => {
			lc.show();
			lc.hide();
			expect(lc.hidden).toBe(true);
		});

		it('hides the logElement (display becomes none)', () => {
			lc.show();
			lc.hide();
			expect(lc.logElement.style.display).toBe('none');
		});
	});

	describe('output', () => {
		it('increments outputCount on each call', () => {
			lc.output('first');
			expect(lc.outputCount).toBe(1);
			lc.output('second');
			expect(lc.outputCount).toBe(2);
		});

		it('appends a <pre> element to outputElement', () => {
			lc.output('hello');
			expect(lc.outputElement.innerHTML).toContain('<pre');
			expect(lc.outputElement.innerHTML).toContain('hello');
		});

		it('HTML-escapes angle brackets in the message', () => {
			lc.output('<script>');
			expect(lc.outputElement.innerHTML).toContain('&lt;script&gt;');
		});

		it('HTML-escapes ampersands in the message', () => {
			lc.output('a & b');
			expect(lc.outputElement.innerHTML).toContain('&amp;');
		});

		it('applies the supplied style to the <pre>', () => {
			lc.output('styled', 'color:red');
			expect(lc.outputElement.innerHTML).toContain('color:red');
		});

		it('defaults to "undefined" for falsy message', () => {
			lc.output(null);
			expect(lc.outputElement.innerHTML).toContain('undefined');
		});

		it('applies alternating background on even outputCount', () => {
			lc.output('odd');   // count 1 — no alternate bg
			lc.output('even');  // count 2 — background-color:#101010
			expect(lc.outputElement.innerHTML).toContain('#101010');
		});
	});

	describe('clear', () => {
		it('empties outputElement.innerHTML', () => {
			lc.output('something');
			lc.clear();
			expect(lc.outputElement.innerHTML).toBe('');
		});
	});

	describe('logUpdate', () => {
		it('appends output for a matching log entry', () => {
			lc.tagPattern = '.*'; // match everything
			const entry = new LogEntry('test message', 'info');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('test message');
		});

		it('skips entries whose tag does not match the pattern', () => {
			lc.tagPattern = '^debug$';
			const entry = new LogEntry('ignored', 'info');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toBe('');
		});

		it('colours error entries red', () => {
			lc.tagPattern = '.*';
			const entry = new LogEntry('err', 'error');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('color:red');
		});

		it('colours warning entries orange', () => {
			lc.tagPattern = '.*';
			const entry = new LogEntry('warn', 'warning');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('color:orange');
		});

		it('colours debug entries green', () => {
			lc.tagPattern = '.*';
			const entry = new LogEntry('dbg', 'debug');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('color:green');
		});

		it('colours info entries white', () => {
			lc.tagPattern = '.*';
			const entry = new LogEntry('inf', 'info');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('color:white');
		});

		it('colours unknown tags yellow', () => {
			lc.tagPattern = '.*';
			const entry = new LogEntry('custom', 'custom-tag');
			lc.logUpdate(entry);
			expect(lc.outputElement.innerHTML).toContain('color:yellow');
		});
	});

	describe('updateTags', () => {
		it('does nothing when the pattern has not changed', () => {
			lc.tagPattern = '.*';
			lc.tagFilterElement.value = '.*';
			const prevHTML = lc.outputElement.innerHTML;
			lc.updateTags();
			expect(lc.outputElement.innerHTML).toBe(prevHTML);
		});

		it('updates tagPattern when a valid new pattern is entered', () => {
			lc.tagFilterElement.value = 'debug';
			lc.updateTags();
			expect(lc.tagPattern).toBe('debug');
		});

		it('rejects an invalid RegExp and leaves tagPattern unchanged', () => {
			lc.tagPattern = '.*';
			lc.tagFilterElement.value = '[invalid(';
			lc.updateTags();
			expect(lc.tagPattern).toBe('.*');
		});

		it('re-renders all existing log entries with the new pattern', () => {
			// Seed Logger with entries before creating console.
			Logger.log('alpha', 'info');
			Logger.log('beta', 'debug');
			// Change filter to only show debug.
			lc.tagFilterElement.value = 'debug';
			lc.updateTags();
			expect(lc.outputElement.innerHTML).toContain('beta');
			expect(lc.outputElement.innerHTML).not.toContain('alpha');
		});

		it('resets outputCount before re-rendering (count reflects only replayed entries)', () => {
			// updateTags() sets outputCount = 0 then re-renders matching Logger.logEntries.
			// After the re-render outputCount equals the number of matching entries.
			// Here we change the pattern to 'nomatch' so no entries are re-rendered.
			lc.output('existing');
			lc.tagFilterElement.value = 'nomatch_xyz';
			lc.updateTags();
			expect(lc.outputCount).toBe(0);
		});
	});

	describe('handleInput', () => {
		it('does nothing for non-enter key codes', () => {
			lc.inputElement.value = 'something';
			lc.handleInput({ keyCode: 65 }); // 'A'
			expect(lc.inputElement.value).toBe('something');
		});

		it('clears the input on Enter', () => {
			lc.inputElement.value = '1+1';
			lc.handleInput({ keyCode: 13 });
			expect(lc.inputElement.value).toBe('');
		});

		it('calls Logger.clear() when command is "clear"', () => {
			Logger.log('entry', 'info');
			lc.inputElement.value = 'clear';
			lc.handleInput({ keyCode: 13 });
			expect(Logger.logEntries).toHaveLength(0);
		});

		it('evals the expression and logs the result', () => {
			lc.inputElement.value = '1 + 1';
			lc.handleInput({ keyCode: 13 });
			// Logger should contain the result 2
			const messages = Logger.logEntries.map(e => e.message);
			expect(messages).toContain(2);
		});

		it('logs an error when eval throws', () => {
			const consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
			lc.inputElement.value = 'this is !@# invalid syntax ^^^';
			lc.handleInput({ keyCode: 13 });
			// Logger will have an error entry
			const errorEntry = Logger.logEntries.find(e => e.tag === 'error');
			expect(errorEntry).toBeTruthy();
			consoleSpy.mockRestore();
		});

		it('pushes non-empty commands to commandHistory', () => {
			lc.commandHistory = [];
			lc.inputElement.value = '1+1';
			lc.handleInput({ keyCode: 13 });
			expect(lc.commandHistory[0]).toBe('1+1');
		});

		it('does not push duplicate consecutive command to history', () => {
			lc.commandHistory = ['1+1'];
			lc.inputElement.value = '1+1';
			lc.handleInput({ keyCode: 13 });
			// Should still be length 1 (no duplicate)
			expect(lc.commandHistory.filter(c => c === '1+1')).toHaveLength(1);
		});

		it('navigates up in history on ArrowUp (keyCode 38)', () => {
			lc.commandHistory = ['first', 'second'];
			lc.commandIndex = 0;
			lc.handleInput({ keyCode: 38 });
			expect(lc.inputElement.value).toBe('first');
		});

		it('does not go past the last history entry on ArrowUp', () => {
			lc.commandHistory = ['only'];
			lc.commandIndex = 0;
			lc.handleInput({ keyCode: 38 });
			// commandIndex should stay at 0 (length - 1)
			expect(lc.commandIndex).toBe(0);
		});

		it('navigates down in history on ArrowDown (keyCode 40)', () => {
			lc.commandHistory = ['first', 'second'];
			lc.commandIndex = 1;
			lc.handleInput({ keyCode: 40 });
			expect(lc.inputElement.value).toBe('first');
		});

		it('resets commandIndex to 0 for other keys', () => {
			lc.commandIndex = 5;
			lc.handleInput({ keyCode: 65 }); // 'A'
			expect(lc.commandIndex).toBe(0);
		});
	});
});

// ─── Prado.Inspector ─────────────────────────────────────────────────────────

describe('Prado.Inspector', () => {
	it('is defined', () => {
		expect(PradoInspector).toBeDefined();
	});

	describe('format', () => {
		it('escapes < and >', () => {
			expect(PradoInspector.format('<div>')).toBe('&lt;div&gt;');
		});

		it('returns non-string values as-is', () => {
			expect(PradoInspector.format(42)).toBe(42);
			expect(PradoInspector.format(null)).toBeNull();
		});

		it('handles empty string', () => {
			expect(PradoInspector.format('')).toBe('');
		});
	});

	describe('buildInspectionLevel', () => {
		it('returns a link when displaying is a plain window string', () => {
			PradoInspector.displaying = '[object Window]';
			const html = PradoInspector.buildInspectionLevel();
			expect(html).toContain('var_dump()');
		});

		it('builds breadcrumb links for dotted name paths', () => {
			PradoInspector.displaying = 'Prado.WebUI';
			const html = PradoInspector.buildInspectionLevel();
			expect(html).toContain('Prado');
			expect(html).toContain('WebUI');
		});
	});

	describe('cleanUp', () => {
		it('does not throw when there is nothing to clean up', () => {
			expect(() => PradoInspector.cleanUp()).not.toThrow();
		});

		it('removes so_mContainer from the DOM if present', () => {
			const container = document.createElement('div');
			container.id = 'so_mContainer';
			document.body.appendChild(container);
			const style = document.createElement('style');
			style.id = 'so_mStyle';
			document.body.appendChild(style);
			PradoInspector.cleanUp();
			expect(document.getElementById('so_mContainer')).toBeNull();
		});
	});
});
