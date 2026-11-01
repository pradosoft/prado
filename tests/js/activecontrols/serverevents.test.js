/**
 * Tests for Prado.EventSource and Prado.WebUI.TServerEventTriggeredCallback.
 * Source: framework/Web/Javascripts/source/prado/activecontrols/serverevents.js
 *
 * A fake EventSource records the URL it opened and lets a test emit messages
 * and errors; Prado.CallbackRequest is mocked so no request leaves the test.
 */

import { EventSourceManager, TServerEventTriggeredCallback } from '../adapters/serverevents.js';
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
	for (const url of Object.keys(EventSourceManager.sources))
		EventSourceManager.close(url);
	clearRegistry();
	restoreMocks();
	document.body.innerHTML = '';
	global.EventSource = savedEventSource;
});

describe('Prado.EventSource', () => {
	it('opens one EventSource per URL with the resume point', () => {
		const a = subscriber(), b = subscriber();
		const first = EventSourceManager.subscribe(URL, a, 'g-3');
		const second = EventSourceManager.subscribe(URL, b, 'g-9');
		expect(first).toBe(second);
		expect(FakeEventSource.instances).toHaveLength(1);
		expect(first.url).toBe(URL + '&lastEventId=g-3');
	});

	it('sends an empty resume point and uses ? for a URL without a query', () => {
		const source = EventSourceManager.subscribe('/events/orders', subscriber(), '');
		expect(source.url).toBe('/events/orders?lastEventId=');
	});

	it('delivers each message to every active subscriber', () => {
		const a = subscriber(), b = subscriber();
		const source = EventSourceManager.subscribe(URL, a, '');
		EventSourceManager.subscribe(URL, b, '');
		source.emit({ channel: 'orders', event: 'changed', data: { id: 7 } }, 'g-4');
		const expected = { Channel: 'orders', ID: 'g-4', Event: 'changed', Data: { id: 7 } };
		expect(a.events).toEqual([expected]);
		expect(b.events).toEqual([expected]);
	});

	it('gives an empty Channel to a message without one', () => {
		const a = subscriber();
		const source = EventSourceManager.subscribe(URL, a, '');
		source.emit({ event: 'changed', data: 1 }, 'g-4');
		expect(a.events).toEqual([{ Channel: '', ID: 'g-4', Event: 'changed', Data: 1 }]);
	});

	it('ignores a message that is not a Prado server event', () => {
		const a = subscriber();
		const seen = [];
		const listener = (e) => seen.push(e.detail);
		document.addEventListener('prado:serverevent', listener);
		const source = EventSourceManager.subscribe(URL, a, '');
		source.emit('not json');
		source.emit({ data: 1 });
		source.emit('null');
		document.removeEventListener('prado:serverevent', listener);
		expect(a.events).toEqual([]);
		expect(seen).toEqual([]);
	});

	it('dispatches each message on document before the subscribers receive it', () => {
		const order = [];
		const a = { isActive: () => true, onServerEvent: (event) => order.push(['subscriber', event]) };
		const listener = (e) => order.push(['document', e.detail]);
		document.addEventListener('prado:serverevent', listener);
		const source = EventSourceManager.subscribe(URL, a, '');
		source.emit({ channel: 'orders', event: 'changed', data: 3 }, 'g-5');
		document.removeEventListener('prado:serverevent', listener);
		const event = { Channel: 'orders', ID: 'g-5', Event: 'changed', Data: 3 };
		expect(order).toEqual([['document', event], ['subscriber', event]]);
		expect(EventSourceManager.DOCUMENT_EVENT).toBe('prado:serverevent');
	});

	it('drops an inactive subscriber on the next message and closes the last one', async () => {
		const a = subscriber(), b = subscriber();
		const source = EventSourceManager.subscribe(URL, a, '');
		EventSourceManager.subscribe(URL, b, '');
		a.active = false;
		source.emit({ event: 'e', data: null });
		expect(a.events).toEqual([]);
		expect(b.events).toHaveLength(1);
		expect(EventSourceManager.sources[URL].subscribers).toEqual([b]);
		b.active = false;
		source.emit({ event: 'e', data: null });
		await Promise.resolve();
		expect(source.closed).toBe(true);
		expect(EventSourceManager.sources[URL]).toBeUndefined();
	});

	it('sweeps inactive subscribers after a callback response', async () => {
		const a = subscriber();
		const source = EventSourceManager.subscribe(URL, a, '');
		a.active = false;
		document.dispatchEvent(new CustomEvent('prado:ajaxComplete'));
		await Promise.resolve();
		expect(source.closed).toBe(true);
	});

	it('forgets a stream the server refused and keeps one that reconnects', () => {
		const source = EventSourceManager.subscribe(URL, subscriber(), '');
		source.fail(0);
		expect(EventSourceManager.sources[URL]).toBeDefined();
		source.fail(2);
		expect(EventSourceManager.sources[URL]).toBeUndefined();
		EventSourceManager.subscribe(URL, subscriber(), '');
		expect(FakeEventSource.instances).toHaveLength(2);
	});

	it('unsubscribe of an unknown URL or subscriber does nothing', () => {
		const a = subscriber();
		EventSourceManager.unsubscribe('/none', a);
		EventSourceManager.subscribe(URL, a, '');
		EventSourceManager.unsubscribe(URL, subscriber());
		expect(EventSourceManager.sources[URL].subscribers).toEqual([a]);
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
		expect(EventSourceManager.sources[URL].subscribers).toEqual([second]);
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
