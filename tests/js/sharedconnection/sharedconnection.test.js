/**
 * Tests for Prado.SharedConnection (sharedconnection/sharedconnection.js).
 *
 * Each "tab" is a Connection with its own window target; the tabs share a fake
 * Web Locks manager and fake BroadcastChannels, which deliver asynchronously as
 * the browser does.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { SharedConnection } from '../adapters/sharedconnection.js';

const KEY = 'sse:/events?channel=orders&token=t';

function abortError() {
	return new DOMException('Aborted', 'AbortError');
}

function createLocks() {
	const queues = new Map();
	const grant = (name) => {
		const queue = queues.get(name);
		if (queue.holder || queue.waiting.length === 0) {
			return;
		}
		const entry = queue.waiting.shift();
		queue.holder = entry;
		Promise.resolve(entry.callback({ name })).then((value) => {
			if (queue.holder === entry) {
				queue.holder = null;
				entry.resolve(value);
				grant(name);
			}
		});
	};
	return {
		queues,
		holder: (name) => (queues.get(name) || {}).holder || null,
		request(name, options, callback) {
			return new Promise((resolve, reject) => {
				if (!queues.has(name)) {
					queues.set(name, { holder: null, waiting: [] });
				}
				const queue = queues.get(name);
				const entry = { callback, resolve, reject, tab: options.tab };
				if (options.steal) {
					if (queue.holder) {
						const old = queue.holder;
						queue.holder = null;
						old.reject(abortError());
					}
					queue.waiting.unshift(entry);
					grant(name);
					return;
				}
				if (options.signal) {
					options.signal.addEventListener('abort', () => {
						const index = queue.waiting.indexOf(entry);
						if (index !== -1) {
							queue.waiting.splice(index, 1);
							reject(abortError());
						}
					});
				}
				queue.waiting.push(entry);
				grant(name);
			});
		},
	};
}

function createChannelClass() {
	const byName = new Map();
	return class FakeChannel {
		constructor(name) {
			this.name = name;
			this.onmessage = null;
			this.closed = false;
			if (!byName.has(name)) {
				byName.set(name, new Set());
			}
			byName.get(name).add(this);
		}

		postMessage(data) {
			const copy = structuredClone(data);
			byName.get(this.name).forEach((channel) => {
				if (channel !== this) {
					queueMicrotask(() => {
						if (!channel.closed && channel.onmessage) {
							channel.onmessage({ data: copy });
						}
					});
				}
			});
		}

		close() {
			this.closed = true;
			byName.get(this.name).delete(this);
		}
	};
}

let locks;
let Channel;
let transports;

function createTransport() {
	return vi.fn((resumeId, emit) => {
		const transport = { resumeId, emit, closed: false, close: vi.fn(() => { transport.closed = true; }) };
		transports.push(transport);
		return transport;
	});
}

function createTab(options = {}, environment = {}) {
	const target = new EventTarget();
	const messages = [];
	const open = options.open || createTransport();
	const connection = new SharedConnection.Connection(KEY, Object.assign({ open, heartbeat: 1000 }, options), Object.assign({ locks, BroadcastChannel: Channel, target }, environment));
	const subscription = connection.subscribe((message, info) => messages.push([message, info.leader]));
	return { connection, subscription, messages, target, open };
}

async function flush() {
	for (let i = 0; i < 20; i++) {
		await Promise.resolve();
	}
}

beforeEach(() => {
	vi.useFakeTimers();
	locks = createLocks();
	Channel = createChannelClass();
	transports = [];
	SharedConnection.connections = {};
});

afterEach(() => {
	vi.useRealTimers();
	vi.restoreAllMocks();
});

describe('Prado.SharedConnection', () => {
	it('lets the first tab lead and the others follow', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		expect(one.connection.leader).toBe(true);
		expect(two.connection.leader).toBe(false);
		expect(transports).toHaveLength(1);
		expect(transports[0].resumeId).toBeNull();
		expect(one.subscription.isLeader()).toBe(true);
		expect(two.subscription.isLeader()).toBe(false);
	});

	it('dispatches the leader messages in every tab', async () => {
		const one = createTab();
		const two = createTab();
		const three = createTab();
		await flush();
		transports[0].emit({ id: '1-1', event: 'message', data: '{"n":1}' });
		transports[0].emit({ id: '1-2', event: 'message', data: '{"n":2}' });
		await flush();
		expect(one.messages).toEqual([[{ id: '1-1', event: 'message', data: '{"n":1}' }, true], [{ id: '1-2', event: 'message', data: '{"n":2}' }, true]]);
		expect(two.messages.map(([message, leader]) => [message.id, leader])).toEqual([['1-1', false], ['1-2', false]]);
		expect(three.messages).toHaveLength(2);
		expect(two.connection.lastId).toBe('1-2');
	});

	it('hands the connection to the next tab with the last message ID', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		transports[0].emit({ id: '1-5', event: 'message', data: 'x' });
		await flush();
		one.subscription.close();
		await flush();
		expect(transports[0].close).toHaveBeenCalled();
		expect(two.connection.leader).toBe(true);
		expect(transports).toHaveLength(2);
		expect(transports[1].resumeId).toBe('1-5');
		expect(one.connection.channel).toBeNull();
	});

	it('hands over when the leader tab freezes and rejoins on resume', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		one.target.dispatchEvent(new Event('freeze'));
		await flush();
		expect(one.connection.leader).toBe(false);
		expect(two.connection.leader).toBe(true);
		one.target.dispatchEvent(new Event('resume'));
		await flush();
		expect(one.connection.paused).toBe(false);
		expect(one.connection.leader).toBe(false);
		two.subscription.close();
		await flush();
		expect(one.connection.leader).toBe(true);
	});

	it('hands over when the leader tab enters the back-forward cache', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		one.target.dispatchEvent(new Event('pagehide'));
		await flush();
		expect(two.connection.leader).toBe(true);
		one.target.dispatchEvent(new Event('pageshow'));
		await flush();
		expect(one.connection.leader).toBe(false);
	});

	it('takes the lock from a leader that stopped sending heartbeats', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		// The leader stops: its heartbeat no longer reaches the channel.
		clearInterval(one.connection.heartbeatTimer);
		await vi.advanceTimersByTimeAsync(3100);
		await flush();
		expect(two.connection.leader).toBe(true);
		expect(transports[0].close).toHaveBeenCalled();
		expect(one.connection.leader).toBe(false);
		expect(one.connection.request).not.toBeNull();
	});

	it('keeps a follower while heartbeats arrive', async () => {
		const one = createTab();
		const two = createTab();
		await flush();
		await vi.advanceTimersByTimeAsync(10000);
		await flush();
		expect(one.connection.leader).toBe(true);
		expect(two.connection.leader).toBe(false);
		expect(transports).toHaveLength(1);
	});

	it('learns the last ID from heartbeats before any message', async () => {
		const one = createTab();
		await flush();
		transports[0].emit({ id: '7-1', event: 'message', data: 'x' });
		const two = createTab();
		await vi.advanceTimersByTimeAsync(1000);
		await flush();
		expect(two.connection.lastId).toBe('7-1');
		expect(one.connection.lastId).toBe('7-1');
	});

	it('holds its own connection without locks or channels', async () => {
		const one = createTab({}, { locks: undefined });
		const two = createTab({}, { BroadcastChannel: undefined });
		const three = createTab({ shared: false });
		await flush();
		expect(one.connection.shared).toBe(false);
		expect(one.connection.leader).toBe(true);
		expect(two.connection.leader).toBe(true);
		expect(three.connection.leader).toBe(true);
		expect(transports).toHaveLength(3);
		transports[0].emit({ id: '1', event: 'message', data: 'a' });
		expect(one.messages).toHaveLength(1);
		expect(two.messages).toHaveLength(0);
	});

	it('keeps dispatching when a subscriber throws', async () => {
		const one = createTab();
		await flush();
		const error = vi.spyOn(console, 'error').mockImplementation(() => undefined);
		const later = [];
		one.connection.subscribe(() => {
			throw new Error('bad');
		});
		one.connection.subscribe((message) => later.push(message.id));
		transports[0].emit({ id: '2', event: 'message', data: '' });
		expect(later).toEqual(['2']);
		expect(error).toHaveBeenCalled();
	});

	it('ignores transport messages after it resigns', async () => {
		const one = createTab();
		await flush();
		const emit = transports[0].emit;
		one.subscription.close();
		emit({ id: '9', event: 'message', data: '' });
		expect(one.messages).toHaveLength(0);
	});

	it('shares one connection per key within a tab through subscribe()', async () => {
		vi.stubGlobal('navigator', { locks });
		vi.stubGlobal('BroadcastChannel', Channel);
		const open = createTransport();
		const first = [];
		const second = [];
		const a = SharedConnection.subscribe({ key: 'k', open, onMessage: (m) => first.push(m.id) });
		const b = SharedConnection.subscribe({ key: 'k', open, onMessage: (m) => second.push(m.id) });
		await flush();
		expect(open).toHaveBeenCalledTimes(1);
		transports[0].emit({ id: 'x', event: 'message', data: '' });
		expect(first).toEqual(['x']);
		expect(second).toEqual(['x']);
		a.close();
		expect(SharedConnection.connections.k).toBeDefined();
		b.close();
		expect(SharedConnection.connections.k).toBeUndefined();
		expect(transports[0].close).toHaveBeenCalled();
		vi.unstubAllGlobals();
	});
});

describe('Prado.SharedConnection.eventSource', () => {
	let sources;

	beforeEach(() => {
		sources = [];
		vi.stubGlobal('EventSource', class {
			constructor(url, init) {
				this.url = url;
				this.init = init;
				this.listeners = {};
				this.close = vi.fn();
				sources.push(this);
			}

			addEventListener(type, listener) {
				this.listeners[type] = listener;
			}
		});
	});

	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('opens the stream and resumes with the last ID', () => {
		const open = SharedConnection.eventSource('/index.php?events=orders', { events: ['changed'], withCredentials: true });
		const emitted = [];
		const transport = open(null, (message) => emitted.push(message));
		expect(sources[0].url).toBe('/index.php?events=orders');
		expect(sources[0].init).toEqual({ withCredentials: true });
		sources[0].listeners.message({ type: 'message', lastEventId: '3-1', data: '{"a":1}' });
		sources[0].listeners.changed({ type: 'changed', lastEventId: '', data: 'x' });
		expect(emitted).toEqual([{ id: '3-1', event: 'message', data: '{"a":1}' }, { id: null, event: 'changed', data: 'x' }]);
		transport.close();
		expect(sources[0].close).toHaveBeenCalled();

		open('3-1', () => undefined);
		expect(sources[1].url).toBe('/index.php?events=orders&lastEventId=3-1');
		SharedConnection.eventSource('/stream', { resumeParameter: 'from' })('a b', () => undefined);
		expect(sources[2].url).toBe('/stream?from=a%20b');
	});
});
