// @vitest-environment node
/**
 * Tests for the fetch strategies fragment (serviceworker/worker-strategies.js).
 */

import { describe, it, expect, vi, afterEach } from 'vitest';
import { basic, createScope, dispatch, fetchEvent, runWorker, source } from './workerHarness.js';

const DEFAULTS = { assets: '', networkFirst: [], networkTimeout: 0, offline: '', navigationPreload: false };

function start(config, options = {}) {
	const scope = createScope();
	const prado = runWorker(scope, Object.assign({ version: 'v1' }, options), [
		{ name: 'strategies', code: source('worker-strategies.js'), config: Object.assign({}, DEFAULTS, config) },
	]);
	return { scope, prado };
}

afterEach(() => {
	vi.useRealTimers();
});

describe('fetch strategies', () => {
	it('adds no fetch listener with no strategy', () => {
		const { scope } = start({});
		expect(scope.listeners.fetch).toBeUndefined();
	});

	it('answers assets cache first and stores the network response', async () => {
		const { scope } = start({ assets: '/assets/' });
		scope.fetch.mockResolvedValueOnce(basic('asset v1'));
		const first = await fetchEvent(scope, '/assets/1a2b/prado.js');
		expect(await first.response.text()).toBe('asset v1');
		expect([...scope.store.get('prado-default-v1-runtime').keys()]).toEqual(['https://example.com/assets/1a2b/prado.js']);

		scope.fetch.mockResolvedValueOnce(basic('asset v2'));
		const second = await fetchEvent(scope, '/assets/1a2b/prado.js');
		expect(await second.response.text()).toBe('asset v1');
		expect(scope.fetch).toHaveBeenCalledTimes(1);

		const other = await fetchEvent(scope, '/index.php');
		expect(other.responded).toBeUndefined();
	});

	it('answers network-first paths at a boundary', async () => {
		const { scope } = start({ networkFirst: ['/index.php?page=Help', '/docs/'] });
		for (const url of ['/index.php?page=Help', '/index.php?page=Help.Faq', '/index.php?page=Help&x=1', '/docs/a']) {
			scope.fetch.mockResolvedValueOnce(basic('fresh'));
			const event = await fetchEvent(scope, url);
			expect(event.responded, url).toBe(true);
			expect(await event.response.text()).toBe('fresh');
		}
		for (const url of ['/index.php?page=Helper', '/doc']) {
			const event = await fetchEvent(scope, url);
			expect(event.responded, url).toBeUndefined();
		}
	});

	it('answers network first from the cache when the network fails', async () => {
		const { scope } = start({ networkFirst: ['/news/'] });
		scope.fetch.mockResolvedValueOnce(basic('first'));
		await fetchEvent(scope, '/news/today');
		scope.fetch.mockRejectedValueOnce(new TypeError('offline'));
		const event = await fetchEvent(scope, '/news/today');
		expect(await event.response.text()).toBe('first');
	});

	it('answers network first from the cache after the timeout', async () => {
		const { scope } = start({ networkFirst: ['/news/'], networkTimeout: 50 });
		scope.fetch.mockResolvedValueOnce(basic('first'));
		await fetchEvent(scope, '/news/today');

		vi.useFakeTimers();
		let release;
		scope.fetch.mockImplementationOnce(() => new Promise((resolve) => {
			release = () => resolve(basic('late'));
		}));
		const pending = fetchEvent(scope, '/news/today');
		await vi.advanceTimersByTimeAsync(60);
		release();
		const event = await pending;
		expect(await event.response.text()).toBe('first');
	});

	it('waits for the network after the timeout without a cached copy', async () => {
		const { scope } = start({ networkFirst: ['/news/'], networkTimeout: 10 });
		vi.useFakeTimers();
		let release;
		scope.fetch.mockImplementationOnce(() => new Promise((resolve) => {
			release = () => resolve(basic('late'));
		}));
		const pending = fetchEvent(scope, '/news/new');
		await vi.advanceTimersByTimeAsync(20);
		release();
		const event = await pending;
		expect(await event.response.text()).toBe('late');
	});

	it('does not store a response that forbids it', async () => {
		const { scope } = start({ networkFirst: ['/me'] });
		scope.fetch.mockResolvedValueOnce(basic('private', { headers: { 'Cache-Control': 'private' } }));
		await fetchEvent(scope, '/me');
		expect(scope.store.has('prado-default-v1-runtime')).toBe(false);
	});

	it('shows the offline page for a failed navigation', async () => {
		const { scope } = start({ offline: '/index.php?page=Offline' }, { precache: ['/index.php?page=Offline'] });
		scope.fetch.mockResolvedValueOnce(basic('offline page'));
		await dispatch(scope, 'install');

		scope.fetch.mockRejectedValueOnce(new TypeError('offline'));
		const event = await fetchEvent(scope, '/index.php?page=Home', { mode: 'navigate' });
		expect(await event.response.text()).toBe('offline page');

		scope.fetch.mockResolvedValueOnce(basic('home'));
		const online = await fetchEvent(scope, '/index.php?page=Home', { mode: 'navigate' });
		expect(await online.response.text()).toBe('home');

		const image = await fetchEvent(scope, '/logo.png');
		expect(image.responded).toBeUndefined();
	});

	it('rejects a failed navigation without an offline page', async () => {
		const { scope } = start({ navigationPreload: true });
		scope.fetch.mockRejectedValueOnce(new TypeError('offline'));
		await expect(fetchEvent(scope, '/index.php', { mode: 'navigate' })).rejects.toThrow('offline');
	});

	it('uses the navigation preload response', async () => {
		const { scope } = start({ navigationPreload: true });
		const event = await fetchEvent(scope, '/index.php', { mode: 'navigate', preloadResponse: Promise.resolve(basic('preloaded')) });
		expect(await event.response.text()).toBe('preloaded');
		expect(scope.fetch).not.toHaveBeenCalled();
	});

	it('enables or disables navigation preload on activate', async () => {
		const enabled = start({ navigationPreload: true });
		await dispatch(enabled.scope, 'activate');
		expect(enabled.scope.self.registration.navigationPreload.enable).toHaveBeenCalled();

		const disabled = start({});
		await dispatch(disabled.scope, 'activate');
		expect(disabled.scope.self.registration.navigationPreload.disable).toHaveBeenCalled();
	});

	it('leaves excluded requests to the network', async () => {
		const { scope } = start({ assets: '/assets/' }, { exclude: ['/assets/private/'] });
		const event = await fetchEvent(scope, '/assets/private/x.js');
		expect(event.responded).toBeUndefined();
		const post = await fetchEvent(scope, '/assets/a.js', { method: 'POST', body: 'x' });
		expect(post.responded).toBeUndefined();
	});
});
