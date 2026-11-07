/*! PRADO service worker fetch strategies | github.com/pradosoft/prado */
/* global prado, config */

/**
 * Fetch strategies fragment of TServiceWorkerModule.
 *
 * TServiceWorkerScript runs this file as the body of a fragment function, with
 * the runtime API as `prado` and this configuration as `config`:
 *
 * | Key | Requests | Strategy |
 * |---|---|---|
 * | `assets` | Path under this asset base URL | Cache first; the network response is stored |
 * | `networkFirst` | Path and query starting with one of these prefixes | Network first; after `networkTimeout` ms the cached copy answers |
 * | `offline` | A navigation whose network request fails | This precached URL |
 * | `navigationPreload` | Navigations | The browser starts the request while the worker starts |
 *
 * A prefix matches at a boundary: `/index.php?page=Help` matches
 * `/index.php?page=Help` and `/index.php?page=Help.Faq`, and not
 * `/index.php?page=Helper`. Responses are stored in the `runtime` cache when
 * `prado.isCacheable()` allows it.
 */

const CACHE = 'runtime';
const networkFirstPrefixes = config.networkFirst || [];
const timeout = config.networkTimeout || 0;

/**
 * Returns whether a path starts with a prefix that ends at a boundary.
 * @param {string} path - the path and query of the request
 * @param {string} prefix - the prefix
 * @returns {boolean} whether the prefix matches
 */
function matchesPrefix(path, prefix)
{
	if (!path.startsWith(prefix)) {
		return false;
	}
	if (path.length === prefix.length || /[/?&.#=]$/.test(prefix)) {
		return true;
	}
	return '/?&.#'.indexOf(path.charAt(prefix.length)) !== -1;
}

/**
 * Returns the precached offline page, if any.
 * @returns {Promise<Response|undefined>} the offline page
 */
function offlinePage()
{
	return config.offline ? prado.fromCache(config.offline) : Promise.resolve(undefined);
}

/**
 * Answers from the cache, or else from the network and stores the response.
 * @param {FetchEvent} event - the fetch event
 * @param {Request} request - the request
 * @returns {Promise<Response>} the response
 */
async function cacheFirst(event, request)
{
	const cached = await prado.fromCache(request);
	if (cached) {
		return cached;
	}
	const response = await fetch(request);
	event.waitUntil(prado.store(CACHE, request, response));
	return response;
}

/**
 * Answers from the network and stores the response. The cached copy answers
 * when the network fails, or when it takes longer than `networkTimeout`.
 * @param {FetchEvent} event - the fetch event
 * @param {Request} request - the request
 * @returns {Promise<Response>} the response
 */
async function networkFirst(event, request)
{
	const network = prado.fromNetwork(event).then((response) => {
		event.waitUntil(prado.store(CACHE, request, response));
		return response;
	});
	network.catch(() => null);
	try {
		if (timeout > 0) {
			const timer = new Promise((resolve) => setTimeout(() => resolve(null), timeout));
			const first = await Promise.race([network, timer]);
			if (first) {
				return first;
			}
			const cached = await prado.fromCache(request);
			if (cached) {
				return cached;
			}
		}
		return await network;
	} catch (error) {
		const cached = (await prado.fromCache(request)) || (request.mode === 'navigate' ? await offlinePage() : undefined);
		if (cached) {
			return cached;
		}
		throw error;
	}
}

/**
 * Answers a navigation from the network, or with the offline page when the
 * network fails.
 * @param {FetchEvent} event - the fetch event
 * @returns {Promise<Response>} the response
 */
async function navigate(event)
{
	try {
		return await prado.fromNetwork(event);
	} catch (error) {
		const page = await offlinePage();
		if (page) {
			return page;
		}
		throw error;
	}
}

if (config.assets) {
	prado.onFetch((event, request) => (new URL(request.url).pathname.startsWith(config.assets) ? cacheFirst(event, request) : undefined));
}

if (networkFirstPrefixes.length > 0) {
	prado.onFetch((event, request) => {
		const url = new URL(request.url);
		const path = url.pathname + url.search;
		return networkFirstPrefixes.some((prefix) => matchesPrefix(path, prefix)) ? networkFirst(event, request) : undefined;
	});
}

if (config.offline || config.navigationPreload) {
	prado.onFetch((event, request) => (request.mode === 'navigate' ? navigate(event) : undefined));
}

prado.onActivate(() => {
	const preload = self.registration && self.registration.navigationPreload;
	if (!preload) {
		return undefined;
	}
	return config.navigationPreload ? preload.enable() : preload.disable();
});
