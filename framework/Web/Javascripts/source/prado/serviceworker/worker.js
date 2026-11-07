/*! PRADO service worker runtime | github.com/pradosoft/prado */
/* exported prado */

/**
 * Service worker runtime.
 *
 * TServiceWorkerService serves this file as the core of a worker script. The
 * script declares `PRADO_SERVICE_WORKER`, the worker's options, before it, and
 * adds each contributor fragment and the closing `prado.start()` after it:
 *
 * ```javascript
 * const PRADO_SERVICE_WORKER = {"version": "…", "worker": "default", "scope": "/", …};
 * // this runtime
 * prado.addFragment('name', function (prado, config) { … }, {…});
 * prado.start();
 * ```
 *
 * | Option | Use |
 * |---|---|
 * | `version` | Hash of the script; part of every cache name |
 * | `worker` | Worker name; part of every cache name |
 * | `scope` | Scope URL path of the registration |
 * | `precache` | URLs stored in the `precache` cache on `install` |
 * | `exclude` | URL path prefixes the fetch handlers never see, such as the service's own requests |
 *
 * Cache names are `prado-<worker>-<version>-<name>`. Every worker of an origin
 * shares Cache Storage, so `activate` deletes only the caches of this worker's
 * older versions (prefix `prado-<worker>-`).
 *
 * The browser accepts `fetch` listeners only while the script first runs, so
 * fragments add fetch handlers before `prado.start()`. Without a fetch handler
 * the worker registers no `fetch` listener, and requests skip the worker.
 *
 * A message from a page is `{type, data}`. The handler of the type replies on
 * the message's port with `{ok: true, result}`, or `{ok: false, error}` when it
 * throws or no handler has the type. Built-in types:
 *
 * | Type | Result |
 * |---|---|
 * | `version` | The script version |
 * | `skipWaiting` | Activates a waiting worker |
 * | `clearCache` | Deletes this worker's caches; resolves to the count |
 */
var prado = (function (self, options)
{
	'use strict';

	const prefix = 'prado-' + options.worker + '-';
	const current = prefix + options.version + '-';
	const fetchHandlers = [];
	const messageHandlers = new Map();
	const installTasks = [];
	const activateTasks = [];
	const origin = self.location.origin;
	let started = false;

	const api = {
		/** @type {string} the script version */
		version: options.version,
		/** @type {string} the worker name */
		worker: options.worker,
		/** @type {string} the scope URL path */
		scope: options.scope,
		/** @type {object} the worker options */
		options,
		/** @type {string} the cache name prefix of every version of this worker */
		cachePrefix: prefix,

		/**
		 * Returns the name of a cache of this worker's version.
		 * @param {string} name - the cache name within the worker
		 * @returns {string} the Cache Storage name
		 */
		cacheName(name)
		{
			return current + name;
		},

		/**
		 * Runs a contributor fragment.
		 * @param {string} name - the fragment name
		 * @param {function} fragment - the fragment, called with this API and its config
		 * @param {object} config - the fragment configuration
		 */
		addFragment(name, fragment, config)
		{
			fragment(api, config || {}, name);
		},

		/**
		 * Adds a fetch handler. A handler returns a promise of the response to answer
		 * the request, or a falsy value to pass it to the next handler. Requests that
		 * no handler answers go to the network.
		 * @param {function(FetchEvent, Request): (Promise<Response>|undefined)} handler - the handler
		 */
		onFetch(handler)
		{
			if (started) {
				throw new Error('prado.onFetch() is only allowed before prado.start().');
			}
			fetchHandlers.push(handler);
		},

		/**
		 * Sets the handler of a message type. The handler's return value, or the value
		 * of its promise, is the reply.
		 * @param {string} type - the message type
		 * @param {function(*, ExtendableMessageEvent): *} handler - the handler
		 */
		onMessage(type, handler)
		{
			messageHandlers.set(type, handler);
		},

		/**
		 * Adds a task that `install` waits for.
		 * @param {function(): Promise} task - the task
		 */
		onInstall(task)
		{
			installTasks.push(task);
		},

		/**
		 * Adds a task that `activate` waits for.
		 * @param {function(): Promise} task - the task
		 */
		onActivate(task)
		{
			activateTasks.push(task);
		},

		/**
		 * Returns whether the fetch handlers skip a request: a method other than GET,
		 * another origin, a server event stream, or an excluded path.
		 * @param {Request} request - the request
		 * @returns {boolean} whether the request bypasses the handlers
		 */
		isExcluded(request)
		{
			if (request.method !== 'GET') {
				return true;
			}
			const url = new URL(request.url);
			if (url.origin !== origin) {
				return true;
			}
			if ((request.headers.get('Accept') || '').indexOf('text/event-stream') !== -1) {
				return true;
			}
			if (url.searchParams.has('PRADO_CALLBACK_TARGET')) {
				return true;
			}
			const path = url.pathname + url.search;
			return (options.exclude || []).some((excluded) => path.startsWith(excluded));
		},

		/**
		 * Returns whether a response may be cached: a successful same-origin response
		 * without `Cache-Control: no-store` or `private`.
		 * @param {Response} response - the response
		 * @returns {boolean} whether the response is cacheable
		 */
		isCacheable(response)
		{
			if (!response || !response.ok || response.type !== 'basic') {
				return false;
			}
			const control = (response.headers.get('Cache-Control') || '').toLowerCase();
			return !/(^|[\s,])(no-store|private)([\s,=]|$)/.test(control);
		},

		/**
		 * Fetches a request from the network, using the navigation preload response
		 * when the browser has one.
		 * @param {FetchEvent} event - the fetch event
		 * @returns {Promise<Response>} the network response
		 */
		async fromNetwork(event)
		{
			const preloaded = event.preloadResponse ? await event.preloadResponse : undefined;
			return preloaded || fetch(event.request);
		},

		/**
		 * Stores a response in a cache of this version, when it is cacheable.
		 * @param {string} name - the cache name within the worker
		 * @param {Request} request - the request
		 * @param {Response} response - the response; a clone is stored
		 * @returns {Promise<boolean>} whether the response was stored
		 */
		async store(name, request, response)
		{
			if (!api.isCacheable(response)) {
				return false;
			}
			const copy = response.clone();
			const cache = await self.caches.open(api.cacheName(name));
			await cache.put(request, copy);
			return true;
		},

		/**
		 * Looks a request up in the caches of this version.
		 * @param {Request|string} request - the request or URL
		 * @returns {Promise<Response|undefined>} the cached response
		 */
		async fromCache(request)
		{
			const names = (await self.caches.keys()).filter((key) => key.startsWith(current));
			for (const name of names) {
				const cache = await self.caches.open(name);
				const response = await cache.match(request);
				if (response) {
					return response;
				}
			}
			return undefined;
		},

		/**
		 * Deletes this worker's caches.
		 * @param {boolean} [olderOnly] - whether to keep the caches of this version
		 * @returns {Promise<number>} the number of deleted caches
		 */
		async clearCaches(olderOnly)
		{
			const names = (await self.caches.keys()).filter(
				(key) => key.startsWith(prefix) && !(olderOnly && key.startsWith(current))
			);
			await Promise.all(names.map((name) => self.caches.delete(name)));
			return names.length;
		},

		/**
		 * Posts a message to every window this worker controls. Each page raises
		 * `prado:serviceworkermessage` with `detail` `{type, data}`.
		 * @param {string} type - the message type
		 * @param {*} data - the message data
		 * @returns {Promise<number>} the number of windows
		 */
		async postToClients(type, data)
		{
			const clients = await self.clients.matchAll({ type: 'window' });
			clients.forEach((client) => client.postMessage({ prado: 'serviceworker', type, data }));
			return clients.length;
		},

		/**
		 * Adds the fetch listener when a fragment added a fetch handler. The script
		 * calls it after the last fragment.
		 */
		start()
		{
			if (started) {
				return;
			}
			started = true;
			if (fetchHandlers.length > 0) {
				self.addEventListener('fetch', onFetchEvent);
			}
		},
	};

	/**
	 * Answers a request with the first handler that returns a response.
	 * @param {FetchEvent} event - the fetch event
	 */
	function onFetchEvent(event)
	{
		const request = event.request;
		if (api.isExcluded(request)) {
			return;
		}
		for (const handler of fetchHandlers) {
			const response = handler(event, request);
			if (response) {
				event.respondWith(response);
				return;
			}
		}
	}

	/**
	 * Stores the precache URLs. A URL that fails is logged and skipped, so one
	 * missing file does not stop the install.
	 * @returns {Promise<void>}
	 */
	async function precache()
	{
		const urls = options.precache || [];
		if (urls.length === 0) {
			return;
		}
		const cache = await self.caches.open(api.cacheName('precache'));
		await Promise.all(urls.map((url) => cache.add(new Request(new URL(url, self.location.href).href, { cache: 'reload' })).catch((error) => {
			console.warn('PRADO service worker: precache of ' + url + ' failed.', error);
		})));
	}

	api.onMessage('version', () => options.version);
	api.onMessage('skipWaiting', () => self.skipWaiting().then(() => true));
	api.onMessage('clearCache', () => api.clearCaches(false));

	self.addEventListener('install', (event) => {
		event.waitUntil(Promise.all([precache(), ...installTasks.map((task) => task())]));
	});

	self.addEventListener('activate', (event) => {
		event.waitUntil(
			api.clearCaches(true)
				.then(() => Promise.all(activateTasks.map((task) => task())))
				.then(() => self.clients.claim())
		);
	});

	self.addEventListener('message', (event) => {
		const message = event.data || {};
		const port = event.ports && event.ports[0];
		const handler = messageHandlers.get(message.type);
		const reply = (body) => {
			if (port) {
				port.postMessage(body);
			}
		};
		if (!handler) {
			reply({ ok: false, error: 'Unknown message type: ' + message.type });
			return;
		}
		const done = Promise.resolve()
			.then(() => handler(message.data, event))
			.then((result) => reply({ ok: true, result }), (error) => reply({ ok: false, error: String(error && error.message || error) }));
		if (event.waitUntil) {
			event.waitUntil(done);
		}
	});

	return api;
})(self, PRADO_SERVICE_WORKER);
