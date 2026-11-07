/**
 * Tests for Prado.ServiceWorker (serviceworker/serviceworker.js), the page client
 * that registers the service worker and exchanges messages with it.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { ServiceWorker } from '../adapters/serviceworker.js';

function eventTarget(target) {
	const listeners = {};
	target.addEventListener = (type, listener) => {
		(listeners[type] = listeners[type] || []).push(listener);
	};
	target.emit = (type, event = {}) => {
		(listeners[type] || []).forEach((listener) => listener(event));
	};
	return target;
}

function createWorker(state = 'installed', reply = null) {
	return eventTarget({
		state,
		postMessage: vi.fn((message, ports) => {
			if (reply && ports && ports[0]) {
				ports[0].postMessage(reply(message));
			}
		}),
	});
}

function createRegistration(fields = {}) {
	return eventTarget(Object.assign({ waiting: null, installing: null, active: null }, fields));
}

let container;
let events;

function installContainer(registration, controller = null) {
	let resolveReady;
	container = eventTarget({
		controller,
		register: vi.fn(async () => registration),
		ready: new Promise((resolve) => {
			resolveReady = resolve;
		}),
	});
	resolveReady(registration);
	Object.defineProperty(navigator, 'serviceWorker', { value: container, configurable: true });
	return container;
}

function listen(name) {
	const handler = vi.fn();
	document.addEventListener(name, handler);
	events.push([name, handler]);
	return handler;
}

beforeEach(() => {
	events = [];
	ServiceWorker.registrations = {};
	ServiceWorker.listening = false;
	ServiceWorker.reloading = false;
	ServiceWorker.timeout = 1000;
	vi.spyOn(ServiceWorker, 'reload').mockImplementation(() => undefined);
});

afterEach(() => {
	events.forEach(([name, handler]) => document.removeEventListener(name, handler));
	delete navigator.serviceWorker;
	vi.restoreAllMocks();
});

describe('Prado.ServiceWorker', () => {
	it('resolves to null without service worker support', async () => {
		delete navigator.serviceWorker;
		expect(ServiceWorker.isSupported()).toBe(false);
		expect(await ServiceWorker.register('/sw.js')).toBeNull();
		await expect(ServiceWorker.postMessage('version')).rejects.toThrow('not supported');
	});

	it('registers once per script URL with the scope and no HTTP cache', async () => {
		const registration = createRegistration();
		installContainer(registration);
		const first = ServiceWorker.register('/index.php?sw=worker', { scope: '/', worker: 'default' });
		const second = ServiceWorker.register('/index.php?sw=worker', { scope: '/' });
		expect(first).toBe(second);
		expect(await first).toBe(registration);
		expect(container.register).toHaveBeenCalledTimes(1);
		expect(container.register).toHaveBeenCalledWith('/index.php?sw=worker', { updateViaCache: 'none', scope: '/' });
	});

	it('raises ready when a worker controls the page', async () => {
		const registration = createRegistration();
		installContainer(registration, createWorker('activated'));
		const ready = listen('prado:serviceworkerready');
		await ServiceWorker.register('/sw.js');
		expect(ready).toHaveBeenCalledTimes(1);
		expect(ready.mock.calls[0][0].detail.registration).toBe(registration);
	});

	it('raises ready when the first worker claims the page', async () => {
		const registration = createRegistration();
		installContainer(registration);
		const ready = listen('prado:serviceworkerready');
		await ServiceWorker.register('/sw.js');
		expect(ready).not.toHaveBeenCalled();
		container.emit('controllerchange');
		await container.ready;
		await Promise.resolve();
		expect(ready).toHaveBeenCalledTimes(1);
	});

	it('logs and resolves to null when the registration fails', async () => {
		installContainer(createRegistration());
		container.register.mockRejectedValueOnce(new Error('SecurityError'));
		const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
		expect(await ServiceWorker.register('/sw.js')).toBeNull();
		expect(warn).toHaveBeenCalled();
	});

	it('reports a waiting worker as an update', async () => {
		const waiting = createWorker('installed');
		const registration = createRegistration({ waiting });
		installContainer(registration, createWorker('activated'));
		const update = listen('prado:serviceworkerupdate');
		await ServiceWorker.register('/sw.js');
		expect(update).toHaveBeenCalledTimes(1);
		const detail = update.mock.calls[0][0].detail;
		expect(detail.worker).toBe(waiting);
		expect(detail.registration).toBe(registration);
		expect(typeof detail.activate).toBe('function');
	});

	it('reports a new worker once it installs while another controls the page', async () => {
		const registration = createRegistration();
		installContainer(registration, createWorker('activated'));
		const update = listen('prado:serviceworkerupdate');
		await ServiceWorker.register('/sw.js');
		const installing = createWorker('installing');
		registration.installing = installing;
		registration.emit('updatefound');
		installing.emit('statechange');
		expect(update).not.toHaveBeenCalled();
		installing.state = 'installed';
		installing.emit('statechange');
		expect(update).toHaveBeenCalledTimes(1);
	});

	it('does not report the first worker as an update', async () => {
		const registration = createRegistration();
		installContainer(registration);
		const update = listen('prado:serviceworkerupdate');
		await ServiceWorker.register('/sw.js');
		const installing = createWorker('installed');
		registration.installing = installing;
		registration.emit('updatefound');
		installing.emit('statechange');
		expect(update).not.toHaveBeenCalled();
	});

	it('activates the waiting worker and reloads when it takes control', async () => {
		const waiting = createWorker('installed');
		const registration = createRegistration({ waiting });
		installContainer(registration, createWorker('activated'));
		const update = listen('prado:serviceworkerupdate');
		const ready = listen('prado:serviceworkerready');
		await ServiceWorker.register('/sw.js');
		ready.mockClear();
		expect(update.mock.calls[0][0].detail.activate()).toBe(true);
		expect(waiting.postMessage).toHaveBeenCalledWith({ type: 'skipWaiting' });
		container.emit('controllerchange');
		expect(ServiceWorker.reload).toHaveBeenCalledTimes(1);
		expect(ready).not.toHaveBeenCalled();
		expect(ServiceWorker.activate(createRegistration())).toBe(false);
	});

	it('raises the worker messages on the document', () => {
		installContainer(createRegistration());
		ServiceWorker.listen();
		const received = listen('prado:serviceworkermessage');
		container.emit('message', { data: { prado: 'serviceworker', type: 'refresh', data: { id: 3 } } });
		container.emit('message', { data: { other: true } });
		expect(received).toHaveBeenCalledTimes(1);
		expect(received.mock.calls[0][0].detail).toEqual({ type: 'refresh', data: { id: 3 } });
	});

	it('sends a message to the controller and resolves with the reply', async () => {
		const controller = createWorker('activated', (msg) => ({ ok: true, result: msg.type + ':' + msg.data }));
		installContainer(createRegistration(), controller);
		expect(await ServiceWorker.postMessage('echo', 'x')).toBe('echo:x');
		expect(controller.postMessage.mock.calls[0][0]).toEqual({ type: 'echo', data: 'x' });
	});

	it('sends a message to the active worker without a controller', async () => {
		const active = createWorker('activated', () => ({ ok: true, result: 'v1' }));
		installContainer(createRegistration({ active }));
		expect(await ServiceWorker.postMessage('version')).toBe('v1');
	});

	it('rejects a failed reply, a missing worker, and a timeout', async () => {
		installContainer(createRegistration(), createWorker('activated', () => ({ ok: false, error: 'Unknown message type: x' })));
		await expect(ServiceWorker.postMessage('x')).rejects.toThrow('Unknown message type: x');

		installContainer(createRegistration());
		await expect(ServiceWorker.postMessage('x')).rejects.toThrow('No active service worker');

		installContainer(createRegistration(), createWorker('activated'));
		ServiceWorker.timeout = 10;
		await expect(ServiceWorker.postMessage('slow')).rejects.toThrow('did not reply to slow');
	});
});
