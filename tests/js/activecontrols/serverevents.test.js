/**
 * Tests for Prado.ServerEvents, its sse transport Prado.EventSource, and Prado.WebUI.TServerEventTriggeredCallback.
 * Source: framework/Web/Javascripts/source/prado/activecontrols/serverevents.js
 *
 * A fake EventSource records the URL it opened and lets a test emit messages
 * and errors; Prado.CallbackRequest is mocked so no request leaves the test.
 */

import { ServerEvents, EventSourceTransport, TServerEventTriggeredCallback } from '../adapters/serverevents.js';
import { clearRegistry, mockCallbackRequest, restoreMocks } from '../helpers/callbackMock.js';

class FakeEventSource {
	static instances = [];

	constructor(url) {
		this.url = url;
		this.readyState = 0;
		this.listeners = {};
		this.closed = false;
		FakeEventSource.instances.push(this);
	}

	addEventListener(type, handler) {
		(this.listeners[type] ||= []).push(handler);
	}

	close() {
		this.closed = true;
		this.readyState = 2;
	}

	emit(data, lastEventId = '') {
		for (const handler of this.listeners.message || [])
			handler({ data: typeof data === 'string' ? data : JSON.stringify(data), lastEventId });
	}

	fail(readyState) {
		this.readyState = readyState;
		for (const handler of this.listeners.error || [])
			handler({});
	}
}

const URL = '/index.php?events=orders&token=abc';

let savedEventSource;

function anchor(id) {
	const span = document.createElement('span');
	span.id = id;
	span.hidden = true;
	document.body.appendChild(span);
	return span;
}

function control(id, extra = {}) {
	anchor(id);
	return new TServerEventTriggeredCallback({ ID: id, EventTarget: 'ctl0$' + id, StreamUrl: URL, LastEventID: 'g-3', EventName: '', ...extra });
}

function subscriber(active = true) {
	return { active, events: [], isActive() { return this.active; }, onServerEvent(event) { this.events.push(event); } };
}

beforeEach(() => {
	savedEventSource = global.EventSource;
	global.EventSource = FakeEventSource;
	FakeEventSource.instances = [];
});

afterEach(() => {
	for (const key of Object.keys(ServerEvents.streams))
		ServerEvents.close(key);
	clearRegistry();
	restoreMocks();
	document.body.innerHTML = '';
	global.EventSource = savedEventSource;
});

function sse(subscriber, lastEventId = '', url = URL) {
	return ServerEvents.subscribe({ StreamUrl: url, LastEventID: lastEventId }, subscriber);
}

/**
 * A transport whose streams a test drives: `streams` holds each opened stream's options and its
 * `deliver` and `refused` functions.
 */
function fakeTransport() {
	const transport = {
		streams: [],
		key: (options) => options.Channel,
		open(options, deliver, refused) {
			const stream = { options, deliver, refused, closed: false };
			transport.streams.push(stream);
			return { close: () => { stream.closed = true; } };
		},
	};
	return transport;
}

describe('Prado.ServerEvents with the sse transport', () => {
	it('registers sse, the default transport, as Prado.EventSource', () => {
		expect(ServerEvents.transports.sse).toBe(EventSourceTransport);
		expect(ServerEvents.DEFAULT_TRANSPORT).toBe('sse');
		expect(ServerEvents.keyOf({ StreamUrl: URL })).toBe('sse ' + URL);
		expect(ServerEvents.keyOf({ Transport: 'sse', StreamUrl: URL })).toBe('sse ' + URL);
	});

	it('opens one EventSource per URL with the resume point', () => {
		const a = subscriber(), b = subscriber();
		const first = sse(a, 'g-3');
		const second = sse(b, 'g-9');
		expect(first).toBe(second);
		expect(FakeEventSource.instances).toHaveLength(1);
		expect(FakeEventSource.instances[0].url).toBe(URL + '&lastEventId=g-3');
		expect(ServerEvents.streams[first].handle.source).toBe(FakeEventSource.instances[0]);
		expect(ServerEvents.streams[first].handle.url).toBe(URL);
	});

	it('sends an empty resume point and uses ? for a URL without a query', () => {
		sse(subscriber(), '', '/events/orders');
		expect(FakeEventSource.instances[0].url).toBe('/events/orders?lastEventId=');
	});

	it('delivers each message to every active subscriber', () => {
		const a = subscriber(), b = subscriber();
		sse(a);
		sse(b);
		FakeEventSource.instances[0].emit({ channel: 'orders', event: 'changed', data: { id: 7 } }, 'g-4');
		const expected = { Channel: 'orders', ID: 'g-4', Event: 'changed', Data: { id: 7 } };
		expect(a.events).toEqual([expected]);
		expect(b.events).toEqual([expected]);
	});

	it('gives an empty Channel to a message without one', () => {
		const a = subscriber();
		sse(a);
		FakeEventSource.instances[0].emit({ event: 'changed', data: 1 }, 'g-4');
		expect(a.events).toEqual([{ Channel: '', ID: 'g-4', Event: 'changed', Data: 1 }]);
	});

	it('ignores a message that is not a Prado server event', () => {
		const a = subscriber();
		const seen = [];
		const listener = (e) => seen.push(e.detail);
		document.addEventListener('prado:serverevent', listener);
		sse(a);
		const source = FakeEventSource.instances[0];
		source.emit('not json');
		source.emit({ data: 1 });
		source.emit('null');
		document.removeEventListener('prado:serverevent', listener);
		expect(a.events).toEqual([]);
		expect(seen).toEqual([]);
	});

	it('dispatches each event on document before the subscribers receive it', () => {
		const order = [];
		const a = { isActive: () => true, onServerEvent: (event) => order.push(['subscriber', event]) };
		const listener = (e) => order.push(['document', e.detail]);
		document.addEventListener('prado:serverevent', listener);
		sse(a);
		FakeEventSource.instances[0].emit({ channel: 'orders', event: 'changed', data: 3 }, 'g-5');
		document.removeEventListener('prado:serverevent', listener);
		const event = { Channel: 'orders', ID: 'g-5', Event: 'changed', Data: 3 };
		expect(order).toEqual([['document', event], ['subscriber', event]]);
		expect(ServerEvents.DOCUMENT_EVENT).toBe('prado:serverevent');
	});

	it('drops an inactive subscriber on the next message and closes the last one', async () => {
		const a = subscriber(), b = subscriber();
		const key = sse(a);
		sse(b);
		const source = FakeEventSource.instances[0];
		a.active = false;
		source.emit({ event: 'e', data: null });
		expect(a.events).toEqual([]);
		expect(b.events).toHaveLength(1);
		expect(ServerEvents.streams[key].subscribers).toEqual([b]);
		b.active = false;
		source.emit({ event: 'e', data: null });
		await Promise.resolve();
		expect(source.closed).toBe(true);
		expect(ServerEvents.streams[key]).toBeUndefined();
	});

	it('sweeps inactive subscribers after a callback response', async () => {
		const a = subscriber();
		sse(a);
		a.active = false;
		document.dispatchEvent(new CustomEvent('prado:ajaxComplete'));
		await Promise.resolve();
		expect(FakeEventSource.instances[0].closed).toBe(true);
	});

	it('forgets a stream the server refused and keeps one that reconnects', () => {
		const key = sse(subscriber());
		const source = FakeEventSource.instances[0];
		source.fail(0);
		expect(ServerEvents.streams[key]).toBeDefined();
		source.fail(2);
		expect(ServerEvents.streams[key]).toBeUndefined();
		sse(subscriber());
		expect(FakeEventSource.instances).toHaveLength(2);
	});

	it('unsubscribe of an unknown key or subscriber does nothing', () => {
		const a = subscriber();
		ServerEvents.unsubscribe('sse /none', a);
		const key = sse(a);
		ServerEvents.unsubscribe(key, subscriber());
		expect(ServerEvents.streams[key].subscribers).toEqual([a]);
	});
});

describe('Prado.ServerEvents transports', () => {
	let fake;

	beforeEach(() => {
		fake = fakeTransport();
		ServerEvents.registerTransport('fake', fake);
	});

	afterEach(() => {
		delete ServerEvents.transports.fake;
	});

	it('selects a registered transport by Transport and shares a stream per transport key', () => {
		const a = subscriber(), b = subscriber(), c = subscriber();
		const key = ServerEvents.subscribe({ Transport: 'fake', Channel: 'orders' }, a);
		expect(ServerEvents.subscribe({ Transport: 'fake', Channel: 'orders' }, b)).toBe(key);
		ServerEvents.subscribe({ Transport: 'fake', Channel: 'inbox' }, c);
		expect(key).toBe('fake orders');
		expect(fake.streams.map((stream) => stream.options.Channel)).toEqual(['orders', 'inbox']);
		expect(FakeEventSource.instances).toHaveLength(0);
	});

	it('delivers what a transport decodes, with the document event, to its stream only', () => {
		const a = subscriber(), other = subscriber();
		const seen = [];
		const listener = (e) => seen.push(e.detail);
		document.addEventListener('prado:serverevent', listener);
		ServerEvents.subscribe({ Transport: 'fake', Channel: 'orders' }, a);
		ServerEvents.subscribe({ Transport: 'fake', Channel: 'inbox' }, other);
		const event = { Channel: 'orders', ID: '7', Event: 'changed', Data: { n: 1 } };
		fake.streams[0].deliver(event);
		fake.streams[0].deliver({ Channel: 'orders', ID: '8' });
		document.removeEventListener('prado:serverevent', listener);
		expect(a.events).toEqual([event]);
		expect(other.events).toEqual([]);
		expect(seen).toEqual([event]);
	});

	it('closes a transport stream when its last subscriber leaves or the server refuses it', async () => {
		const a = subscriber();
		const key = ServerEvents.subscribe({ Transport: 'fake', Channel: 'orders' }, a);
		ServerEvents.unsubscribe(key, a);
		await Promise.resolve();
		expect(fake.streams[0].closed).toBe(true);
		expect(ServerEvents.streams[key]).toBeUndefined();

		const refusedKey = ServerEvents.subscribe({ Transport: 'fake', Channel: 'orders' }, subscriber());
		fake.streams[1].refused();
		expect(fake.streams[1].closed).toBe(true);
		expect(ServerEvents.streams[refusedKey]).toBeUndefined();
	});

	it('throws for an unknown transport and keeps no stream', () => {
		expect(() => ServerEvents.subscribe({ Transport: 'none', Channel: 'orders' }, subscriber())).toThrow("no transport 'none'");
		expect(Object.keys(ServerEvents.streams)).toEqual([]);
	});

	it('forgets a stream whose transport fails to open', () => {
		ServerEvents.registerTransport('broken', { key: () => 'x', open: () => { throw new Error('down'); } });
		expect(() => ServerEvents.subscribe({ Transport: 'broken' }, subscriber())).toThrow('down');
		expect(ServerEvents.streams['broken x']).toBeUndefined();
		delete ServerEvents.transports.broken;
	});

	it('a control streams through the transport its options name', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		control('Orders', { Transport: 'fake', Channel: 'orders', StreamUrl: undefined });
		expect(FakeEventSource.instances).toHaveLength(0);
		fake.streams[0].deliver({ Channel: 'orders', ID: '9', Event: 'changed', Data: 2 });
		expect(setCallbackParameterMock).toHaveBeenCalledWith({ Channel: 'orders', ID: '9', Event: 'changed', Data: 2 });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});
});

describe('Prado.WebUI.TServerEventTriggeredCallback', () => {
	it('subscribes on init and sends a callback with the server event', () => {
		const { dispatchMock, setCallbackParameterMock } = mockCallbackRequest();
		control('Orders');
		const source = FakeEventSource.instances[0];
		expect(source.url).toBe(URL + '&lastEventId=g-3');
		source.emit({ channel: 'orders', event: 'changed', data: 5 }, 'g-4');
		expect(global.Prado.CallbackRequest).toHaveBeenCalledWith('ctl0$Orders', expect.objectContaining({ StreamUrl: URL }));
		expect(setCallbackParameterMock).toHaveBeenCalledWith({ Channel: 'orders', ID: 'g-4', Event: 'changed', Data: 5 });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('runs OnServerEvent before the callback, and false skips the callback', () => {
		const { dispatchMock } = mockCallbackRequest();
		const calls = [];
		const ctl = control('Orders', {
			OnServerEvent: (sender, event) => {
				calls.push([sender, event]);
				return event.Data !== 'skip';
			},
		});
		const source = FakeEventSource.instances[0];
		source.emit({ channel: 'orders', event: 'changed', data: 'skip' }, 'g-4');
		expect(dispatchMock).not.toHaveBeenCalled();
		source.emit({ channel: 'orders', event: 'changed', data: 'send' }, 'g-5');
		expect(dispatchMock).toHaveBeenCalledTimes(1);
		expect(calls).toEqual([
			[ctl, { Channel: 'orders', ID: 'g-4', Event: 'changed', Data: 'skip' }],
			[ctl, { Channel: 'orders', ID: 'g-5', Event: 'changed', Data: 'send' }],
		]);
	});

	it('OnServerEvent returning undefined still sends the callback', () => {
		const { dispatchMock } = mockCallbackRequest();
		control('Orders', { OnServerEvent: () => {} });
		FakeEventSource.instances[0].emit({ event: 'changed', data: 1 });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('CallbackOnEvent false runs only OnServerEvent', () => {
		const { dispatchMock } = mockCallbackRequest();
		const seen = [];
		control('Orders', { CallbackOnEvent: false, OnServerEvent: (sender, event) => { seen.push(event.Data); } });
		FakeEventSource.instances[0].emit({ event: 'changed', data: 1 });
		FakeEventSource.instances[0].emit({ event: 'changed', data: 2 });
		expect(seen).toEqual([1, 2]);
		expect(dispatchMock).not.toHaveBeenCalled();
		expect(global.Prado.CallbackRequest).not.toHaveBeenCalled();
	});

	it('OnServerEvent runs only for its EventName', () => {
		mockCallbackRequest();
		const seen = [];
		control('Orders', { EventName: 'changed', OnServerEvent: (sender, event) => { seen.push(event.Event); } });
		FakeEventSource.instances[0].emit({ event: 'removed', data: 1 });
		FakeEventSource.instances[0].emit({ event: 'changed', data: 1 });
		expect(seen).toEqual(['changed']);
	});

	it('sends callbacks only for its EventName', () => {
		const { dispatchMock } = mockCallbackRequest();
		control('Orders', { EventName: 'changed' });
		const source = FakeEventSource.instances[0];
		source.emit({ event: 'removed', data: 1 });
		expect(dispatchMock).not.toHaveBeenCalled();
		source.emit({ event: 'changed', data: 1 });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('controls on one channel share the EventSource', () => {
		const { dispatchMock } = mockCallbackRequest();
		control('A');
		control('B');
		expect(FakeEventSource.instances).toHaveLength(1);
		FakeEventSource.instances[0].emit({ event: 'e', data: null });
		expect(dispatchMock).toHaveBeenCalledTimes(2);
	});

	it('stops when a callback removes its anchor', async () => {
		const { dispatchMock } = mockCallbackRequest();
		control('Orders');
		const source = FakeEventSource.instances[0];
		document.getElementById('Orders').remove();
		source.emit({ event: 'e', data: null });
		expect(dispatchMock).not.toHaveBeenCalled();
		await Promise.resolve();
		expect(source.closed).toBe(true);
	});

	it('a control registered again keeps the open stream', async () => {
		const { dispatchMock } = mockCallbackRequest();
		const first = control('Orders');
		const source = FakeEventSource.instances[0];
		document.body.innerHTML = '';
		const second = control('Orders');
		await Promise.resolve();
		expect(first.isLingering()).toBe(true);
		expect(FakeEventSource.instances).toHaveLength(1);
		expect(source.closed).toBe(false);
		expect(ServerEvents.streams['sse ' + URL].subscribers).toEqual([second]);
		source.emit({ event: 'e', data: null });
		expect(dispatchMock).toHaveBeenCalledTimes(1);
	});

	it('closes the stream when the control is deinitialized', async () => {
		mockCallbackRequest();
		const wrapper = control('Orders');
		const source = FakeEventSource.instances[0];
		wrapper.deinitialize();
		await Promise.resolve();
		expect(source.closed).toBe(true);
	});
});
