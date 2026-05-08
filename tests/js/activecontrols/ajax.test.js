/**
 * Tests for Prado.CallbackRequestManager (ajax3.js).
 * Source: framework/Web/Javascripts/source/prado/activecontrols/ajax3.js
 *
 * ESM note: only tests/js/adapters/ajax.js changes on ESM conversion.
 */

import { CallbackRequestManager, Callback } from '../adapters/ajax.js';

// ─── Protocol constants ───────────────────────────────────────────────────────

describe('Prado.CallbackRequestManager POST field names', () => {
	it('FIELD_CALLBACK_TARGET matches the PHP-side field name', () => {
		expect(CallbackRequestManager.FIELD_CALLBACK_TARGET).toBe('PRADO_CALLBACK_TARGET');
	});

	it('FIELD_CALLBACK_PARAMETER matches the PHP-side field name', () => {
		expect(CallbackRequestManager.FIELD_CALLBACK_PARAMETER).toBe('PRADO_CALLBACK_PARAMETER');
	});

	it('FIELD_CALLBACK_PAGESTATE matches the PHP-side field name', () => {
		expect(CallbackRequestManager.FIELD_CALLBACK_PAGESTATE).toBe('PRADO_PAGESTATE');
	});
});

describe('Prado.CallbackRequestManager response header names', () => {
	it('REDIRECT_HEADER', () => {
		expect(CallbackRequestManager.REDIRECT_HEADER).toBe('X-PRADO-REDIRECT');
	});

	it('DATA_HEADER', () => {
		expect(CallbackRequestManager.DATA_HEADER).toBe('X-PRADO-DATA');
	});

	it('ACTION_HEADER', () => {
		expect(CallbackRequestManager.ACTION_HEADER).toBe('X-PRADO-ACTIONS');
	});

	it('ERROR_HEADER', () => {
		expect(CallbackRequestManager.ERROR_HEADER).toBe('X-PRADO-ERROR');
	});

	it('DEBUG_HEADER', () => {
		expect(CallbackRequestManager.DEBUG_HEADER).toBe('X-PRADO-DEBUG');
	});

	it('PAGESTATE_HEADER', () => {
		expect(CallbackRequestManager.PAGESTATE_HEADER).toBe('X-PRADO-PAGESTATE');
	});

	it('SCRIPTLIST_HEADER', () => {
		expect(CallbackRequestManager.SCRIPTLIST_HEADER).toBe('X-PRADO-SCRIPTLIST');
	});

	it('STYLESHEET_HEADER', () => {
		expect(CallbackRequestManager.STYLESHEET_HEADER).toBe('X-PRADO-STYLESHEET');
	});

	it('STYLESHEETLIST_HEADER', () => {
		expect(CallbackRequestManager.STYLESHEETLIST_HEADER).toBe('X-PRADO-STYLESHEETLIST');
	});

	it('HIDDENFIELDLIST_HEADER', () => {
		expect(CallbackRequestManager.HIDDENFIELDLIST_HEADER).toBe('X-PRADO-HIDDENFIELDLIST');
	});
});

describe('Prado.CallbackRequestManager logging flags', () => {
	it('LOG_ERROR defaults to true', () => {
		expect(CallbackRequestManager.LOG_ERROR).toBe(true);
	});

	it('LOG_SUCCESS defaults to false', () => {
		expect(CallbackRequestManager.LOG_SUCCESS).toBe(false);
	});
});

// ─── logFormatException ───────────────────────────────────────────────────────

describe('Prado.CallbackRequestManager.logFormatException', () => {
	function makeLog() {
		return {
			info:           vi.fn(),
			warn:           vi.fn(),
			error:          vi.fn(),
			group:          vi.fn(),
			groupCollapsed: vi.fn(),
			groupEnd:       vi.fn(),
		};
	}

	it('calls log.info with the exception type and message', () => {
		const log = makeLog();
		CallbackRequestManager.logFormatException(log, {
			type: 'BadMethodCallException',
			message: 'test error',
			file: 'test.php',
			line: 42,
			trace: [],
			version: '4.3.3',
			time: '2024-01-01',
		});
		const allCalls = log.info.mock.calls.map((c) => c[0]).join('\n');
		expect(allCalls).toContain('BadMethodCallException');
		expect(allCalls).toContain('test error');
		expect(allCalls).toContain('test.php');
	});

	it('opens a group for the first stack-trace entry', () => {
		const log = makeLog();
		CallbackRequestManager.logFormatException(log, {
			type: 'Exception',
			message: 'error',
			file: 'a.php',
			line: 1,
			trace: [
				{ file: 'a.php', line: 10, class: 'Foo', function: 'bar', args: ['x'] },
				{ file: 'b.php', line: 20, class: 'Baz', function: 'qux', args: [] },
			],
			version: '1.0',
			time: '2024',
		});
		expect(log.group).toHaveBeenCalledTimes(1);
		expect(log.groupEnd).toHaveBeenCalled();
	});

	it('closes each group opened for a trace entry', () => {
		const log = makeLog();
		CallbackRequestManager.logFormatException(log, {
			type: 'Ex',
			message: 'msg',
			file: 'f.php',
			line: 0,
			trace: [
				{ file: 'a.php', line: 1, class: 'A', function: 'a', args: [] },
				{ file: 'b.php', line: 2, class: 'B', function: 'b', args: [] },
			],
			version: '1',
			time: 'now',
		});
		// groupEnd should be called once per trace entry (first uses group, rest groupCollapsed)
		expect(log.groupEnd.mock.calls.length).toBe(2);
	});

	it('logs version and time at the end', () => {
		const log = makeLog();
		CallbackRequestManager.logFormatException(log, {
			type: 'Ex', message: 'msg', file: 'f.php', line: 0,
			trace: [], version: '4.3.3', time: '2024-01-01 12:00',
		});
		const lastCall = log.info.mock.calls.at(-1)[0];
		expect(lastCall).toContain('4.3.3');
	});
});

// ─── logDebug ─────────────────────────────────────────────────────────────────

describe('Prado.CallbackRequestManager.logDebug', () => {
	function makeLog() {
		return {
			info:           vi.fn(),
			warn:           vi.fn(),
			error:          vi.fn(),
			group:          vi.fn(),
			groupCollapsed: vi.fn(),
			groupEnd:       vi.fn(),
		};
	}

	it('uses group (not groupCollapsed) for fewer than 10 entries', () => {
		const log   = makeLog();
		const blocks = Array.from({ length: 5 }, (_, i) => ['info', '12:00', 'ctrl', `msg ${i}`]);
		CallbackRequestManager.logDebug(log, blocks);
		expect(log.group).toHaveBeenCalledTimes(1);
		expect(log.groupCollapsed).not.toHaveBeenCalled();
	});

	it('uses groupCollapsed for 10 or more entries', () => {
		const log   = makeLog();
		const blocks = Array.from({ length: 10 }, (_, i) => ['info', '12:00', 'ctrl', `msg ${i}`]);
		CallbackRequestManager.logDebug(log, blocks);
		expect(log.groupCollapsed).toHaveBeenCalledTimes(1);
		expect(log.group).not.toHaveBeenCalled();
	});

	it('dispatches each entry to the named log method', () => {
		const log   = makeLog();
		const blocks = [
			['info', '12:00', 'ctrl', 'one'],
			['warn', '12:01', 'ctrl', 'two'],
		];
		CallbackRequestManager.logDebug(log, blocks);
		expect(log.info).toHaveBeenCalledTimes(1);
		expect(log.warn).toHaveBeenCalledTimes(1);
	});

	it('includes entry count in the group label', () => {
		const log   = makeLog();
		const blocks = [['info', '12:00', 'ctrl', 'msg']];
		CallbackRequestManager.logDebug(log, blocks);
		expect(log.group).toHaveBeenCalledWith(expect.stringContaining('1 entries'));
	});

	it('calls groupEnd after logging all entries', () => {
		const log   = makeLog();
		const blocks = [['info', '12:00', 'ctrl', 'msg']];
		CallbackRequestManager.logDebug(log, blocks);
		expect(log.groupEnd).toHaveBeenCalledTimes(1);
	});
});

// ─── Prado.Callback helper ────────────────────────────────────────────────────

describe('Prado.Callback', () => {
	it('is a function', () => {
		expect(typeof Callback).toBe('function');
	});
});
