// @vitest-environment node
/**
 * Tests for the service worker runtime (serviceworker/worker.js) and the
 * removal script (serviceworker/worker-unregister.js).
 */

import { describe, it, expect, vi } from 'vitest';
import { basic, createScope, dispatch, fetchEvent, message, runUnregister, runWorker } from './workerHarness.js';

describe('runtime', () => {
	it('exposes the worker options', () => {
		const scope = createScope();
		const prado = runWorker(scope, { version: 'abc', worker: 'admin', scope: '/admin/' });
		expect(prado.version).toBe('abc');
		expect(prado.worker).toBe('admin');
		expect(prado.scope).toBe('/admin/');
		expect(prado.cachePrefix).toBe('prado-admin-');
		expect(prado.cacheName('runtime')).toBe('prado-admin-abc-runtime');
	});

	it('adds no fetch listener without a fetch handler', () => {
		const scope = createScope();
		runWorker(scope, {});
		expect(scope.listeners.fetch).toBeUndefined();
		expect(scope.listeners.install).toHaveLength(1);
		expect(scope.listeners.activate).toHaveLength(1);
		expect(scope.listeners.message).toHaveLength(1);
	});

	it('runs fragments with their config', () => {
		const scope = createScope();
		const prado = runWorker(scope, {}, [{ name: 'one', code: 'prado.seen = config.value;', config: { value: 7 } }]);
		expect(prado.seen).toBe(7);
	});

	it('refuses a fetch handler after start', () => {
		const scope = createScope();
		const prado = runWorker(scope, {});
		expect(() => prado.onFetch(() => undefined)).toThrow(/before prado.start/);
	});

	it('passes requests to handlers in order', async () => {
		const scope = createScope();
		scope.self.handlers = [vi.fn(() => undefined), vi.fn(() => Promise.resolve(basic('mine'))), vi.fn()];
		runWorker(scope, {}, [{ name: 'h', code: 'self.handlers.forEach((handler) => prado.onFetch(handler));' }]);
		const event = await fetchEvent(scope, '/page');
		expect(await event.response.text()).toBe('mine');
		expect(scope.self.handlers[0]).toHaveBeenCalled();
		expect(scope.self.handlers[2]).not.toHaveBeenCalled();
	});

	it('excludes requests the handlers never see', async () => {
		const scope = createScope();
		const prado = runWorker(scope, { exclude: ['/index.php?sw=worker'] });
		const request = (url, init) => new Request(new URL(url, 'https://example.com').href, init);
		expect(prado.isExcluded(request('/page'))).toBe(false);
		expect(prado.isExcluded(request('/page', { method: 'POST' }))).toBe(true);
		expect(prado.isExcluded(request('https://other.example/page'))).toBe(true);
		expect(prado.isExcluded(request('/events', { headers: { Accept: 'text/event-stream' } }))).toBe(true);
		expect(prado.isExcluded(request('/page?PRADO_CALLBACK_TARGET=x'))).toBe(true);
		expect(prado.isExcluded(request('/index.php?sw=worker'))).toBe(true);
		expect(prado.isExcluded(request('/index.php?sw=worker&w=admin'))).toBe(true);
	});

	it('caches only cacheable responses', () => {
		const prado = runWorker(createScope(), {});
		expect(prado.isCacheable(basic('x'))).toBe(true);
		expect(prado.isCacheable(basic('x', { headers: { 'Cache-Control': 'public, max-age=60' } }))).toBe(true);
		expect(prado.isCacheable(basic('x', { headers: { 'Cache-Control': 'no-store' } }))).toBe(false);
		expect(prado.isCacheable(basic('x', { headers: { 'Cache-Control': 'private, max-age=0' } }))).toBe(false);
		expect(prado.isCacheable(basic('x', { status: 404 }))).toBe(false);
		expect(prado.isCacheable(new Response('x'))).toBe(false);
		expect(prado.isCacheable(undefined)).toBe(false);
	});

	it('stores and finds responses in this version', async () => {
		const scope = createScope();
		const prado = runWorker(scope, { version: 'v2' });
		const request = new Request('https://example.com/a');
		expect(await prado.store('runtime', request, basic('A'))).toBe(true);
		expect(await prado.store('runtime', request, basic('B', { headers: { 'Cache-Control': 'no-store' } }))).toBe(false);
		expect(await (await prado.fromCache(request)).text()).toBe('A');
		expect(await prado.fromCache('https://example.com/none')).toBeUndefined();
		expect([...scope.store.keys()]).toEqual(['prado-default-v2-runtime']);
	});

	it('precaches on install and skips a URL that fails', async () => {
		const scope = createScope();
		scope.fetch.mockImplementation(async (request) => (request.url.endsWith('/missing') ? basic('', { status: 404 }) : basic('ok')));
		const warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
		runWorker(scope, { version: 'v1', precache: ['/offline', '/missing'] });
		await dispatch(scope, 'install');
		expect([...scope.store.get('prado-default-v1-precache').keys()]).toEqual(['https://example.com/offline']);
		expect(warn).toHaveBeenCalledTimes(1);
		warn.mockRestore();
	});

	it('deletes only its own older caches on activate and claims clients', async () => {
		const scope = createScope();
		['prado-default-v0-runtime', 'prado-default-v1-runtime', 'prado-admin-v0-runtime', 'other-cache'].forEach((name) => scope.store.set(name, new Map()));
		const prado = runWorker(scope, { version: 'v1' });
		const task = vi.fn(async () => undefined);
		prado.onActivate(task);
		await dispatch(scope, 'activate');
		expect([...scope.store.keys()].sort()).toEqual(['other-cache', 'prado-admin-v0-runtime', 'prado-default-v1-runtime']);
		expect(task).toHaveBeenCalled();
		expect(scope.self.clients.claim).toHaveBeenCalled();
	});

	it('runs install tasks', async () => {
		const scope = createScope();
		const prado = runWorker(scope, {});
		const task = vi.fn(async () => undefined);
		prado.onInstall(task);
		await dispatch(scope, 'install');
		expect(task).toHaveBeenCalled();
	});

	it('answers the built-in messages', async () => {
		const scope = createScope();
		scope.store.set('prado-default-v1-runtime', new Map());
		scope.store.set('prado-default-v0-runtime', new Map());
		scope.store.set('prado-admin-v1-runtime', new Map());
		runWorker(scope, { version: 'v1' });
		expect(await message(scope, 'version')).toEqual({ ok: true, result: 'v1' });
		expect(await message(scope, 'skipWaiting')).toEqual({ ok: true, result: true });
		expect(scope.self.skipWaiting).toHaveBeenCalled();
		expect(await message(scope, 'clearCache')).toEqual({ ok: true, result: 2 });
		expect([...scope.store.keys()]).toEqual(['prado-admin-v1-runtime']);
	});

	it('answers a fragment message, a failure, and an unknown type', async () => {
		const scope = createScope();
		const prado = runWorker(scope, {});
		prado.onMessage('double', (value) => value * 2);
		prado.onMessage('later', (value) => Promise.resolve('later ' + value));
		prado.onMessage('fail', () => {
			throw new Error('broken');
		});
		expect(await message(scope, 'double', 4)).toEqual({ ok: true, result: 8 });
		expect(await message(scope, 'later', 'x')).toEqual({ ok: true, result: 'later x' });
		expect(await message(scope, 'fail')).toEqual({ ok: false, error: 'broken' });
		expect(await message(scope, 'nothing')).toEqual({ ok: false, error: 'Unknown message type: nothing' });
	});

	it('posts to every window it controls', async () => {
		const scope = createScope();
		const one = { postMessage: vi.fn() };
		const two = { postMessage: vi.fn() };
		scope.clients.push(one, two);
		const prado = runWorker(scope, {});
		expect(await prado.postToClients('refresh', { id: 1 })).toBe(2);
		expect(scope.self.clients.matchAll).toHaveBeenCalledWith({ type: 'window' });
		expect(one.postMessage).toHaveBeenCalledWith({ prado: 'serviceworker', type: 'refresh', data: { id: 1 } });
		expect(two.postMessage).toHaveBeenCalledTimes(1);
	});

	it('uses the navigation preload response from the network', async () => {
		const scope = createScope();
		const prado = runWorker(scope, {});
		const preloaded = basic('preloaded');
		expect(await prado.fromNetwork({ preloadResponse: Promise.resolve(preloaded), request: new Request('https://example.com/') })).toBe(preloaded);
		scope.fetch.mockResolvedValueOnce(basic('fetched'));
		expect(await (await prado.fromNetwork({ preloadResponse: Promise.resolve(undefined), request: new Request('https://example.com/') })).text()).toBe('fetched');
	});
});

describe('removal script', () => {
	it('activates at once, clears its caches, unregisters, and reloads its windows', async () => {
		const scope = createScope();
		['prado-admin-v1-runtime', 'prado-admin-v0-precache', 'prado-default-v1-runtime'].forEach((name) => scope.store.set(name, new Map()));
		const client = { url: 'https://example.com/admin/', navigate: vi.fn(async () => client) };
		scope.clients.push(client);
		runUnregister(scope, { worker: 'admin', scope: '/admin/' });
		await dispatch(scope, 'install');
		expect(scope.self.skipWaiting).toHaveBeenCalled();
		await dispatch(scope, 'activate');
		expect([...scope.store.keys()]).toEqual(['prado-default-v1-runtime']);
		expect(scope.self.registration.unregister).toHaveBeenCalled();
		expect(client.navigate).toHaveBeenCalledWith('https://example.com/admin/');
	});
});
