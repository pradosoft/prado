/**
 * Service worker harness: runs the worker runtime and fragments as
 * TServiceWorkerScript renders them, against a fake ServiceWorkerGlobalScope.
 *
 * The scope records listeners and keeps Cache Storage in maps; `fetch` is a
 * mock the test sets. Responses from `basic()` are same-origin ('basic').
 */

import { readFileSync } from 'fs';
import { resolve } from 'path';
import { vi } from 'vitest';

const SOURCE = 'framework/Web/Javascripts/source/prado/serviceworker/';

export const ORIGIN = 'https://example.com';

/**
 * Reads a service worker source file.
 * @param {string} file - the file name
 * @returns {string} the source
 */
export function source(file) {
	return readFileSync(resolve(process.cwd(), SOURCE + file), 'utf-8');
}

/**
 * Creates a same-origin response.
 * @param {string} body - the body
 * @param {object} [init] - status and headers
 * @returns {Response} the response, typed 'basic'
 */
export function basic(body, init) {
	const response = new Response(body, init);
	Object.defineProperty(response, 'type', { value: 'basic' });
	return response;
}

/**
 * @param {Request|string} request - a request or URL
 * @returns {string} the absolute URL
 */
function urlOf(request) {
	return new URL(typeof request === 'string' ? request : request.url, ORIGIN).href;
}

/**
 * Creates a fake ServiceWorkerGlobalScope.
 * @returns {object} the scope, its listeners, its caches, its clients, and its fetch mock
 */
export function createScope() {
	const listeners = {};
	const store = new Map();
	const clients = [];
	const fetchMock = vi.fn(async () => basic('network'));
	const caches = {
		async keys() {
			return [...store.keys()];
		},
		async open(name) {
			if (!store.has(name)) {
				store.set(name, new Map());
			}
			const entries = store.get(name);
			return {
				async put(request, response) {
					entries.set(urlOf(request), response);
				},
				async match(request) {
					const response = entries.get(urlOf(request));
					return response ? response.clone() : undefined;
				},
				async add(request) {
					const response = await fetchMock(request);
					if (!response.ok) {
						throw new TypeError('Bad response');
					}
					entries.set(urlOf(request), response);
				},
			};
		},
		async delete(name) {
			return store.delete(name);
		},
	};
	const self = {
		location: { origin: ORIGIN, href: ORIGIN + '/index.php?sw=worker' },
		addEventListener(type, listener) {
			(listeners[type] = listeners[type] || []).push(listener);
		},
		caches,
		clients: {
			matchAll: vi.fn(async () => clients),
			claim: vi.fn(async () => undefined),
		},
		skipWaiting: vi.fn(async () => undefined),
		registration: {
			navigationPreload: { enable: vi.fn(async () => undefined), disable: vi.fn(async () => undefined) },
			unregister: vi.fn(async () => true),
		},
	};
	return { self, listeners, store, clients, fetch: fetchMock };
}

/**
 * Runs a worker script in a scope.
 * @param {object} scope - the scope from createScope()
 * @param {object} options - the PRADO_SERVICE_WORKER options
 * @param {Array<{name: string, code: string, config: object}>} [fragments] - the fragments
 * @returns {object} the runtime API `prado`
 */
export function runWorker(scope, options, fragments = []) {
	const full = Object.assign({ version: 'v1', worker: 'default', scope: '/', precache: [], exclude: [], fragments: {} }, options);
	fragments.forEach((fragment) => {
		full.fragments[fragment.name] = fragment.config || {};
	});
	let code = 'const PRADO_SERVICE_WORKER = ' + JSON.stringify(full) + ';\n' + source('worker.js') + '\n';
	fragments.forEach((fragment) => {
		const name = JSON.stringify(fragment.name);
		code += 'prado.addFragment(' + name + ', function (prado, config) {\n' + fragment.code + '\n}, PRADO_SERVICE_WORKER.fragments[' + name + ']);\n';
	});
	code += 'prado.start();\nreturn prado;';
	return new Function('self', 'fetch', code)(scope.self, scope.fetch);
}

/**
 * Runs the removal script in a scope.
 * @param {object} scope - the scope from createScope()
 * @param {object} options - the PRADO_SERVICE_WORKER options
 */
export function runUnregister(scope, options) {
	const code = 'const PRADO_SERVICE_WORKER = ' + JSON.stringify(options) + ';\n' + source('worker-unregister.js');
	new Function('self', code)(scope.self);
}

/**
 * Dispatches an extendable event to the scope's listeners and waits for it.
 * @param {object} scope - the scope
 * @param {string} type - the event type
 * @param {object} [fields] - extra event fields
 * @returns {Promise<object>} the event, with `response` when a listener answered
 */
export async function dispatch(scope, type, fields = {}) {
	const pending = [];
	const event = Object.assign({
		waitUntil(promise) {
			pending.push(promise);
		},
		respondWith(response) {
			event.responded = true;
			event.response = response;
		},
	}, fields);
	(scope.listeners[type] || []).forEach((listener) => listener(event));
	if (event.response) {
		event.response = await event.response;
	}
	await Promise.all(pending);
	return event;
}

/**
 * Dispatches a fetch event.
 * @param {object} scope - the scope
 * @param {string} url - the URL, absolute or on the origin
 * @param {object} [init] - request init, plus `mode` and `preloadResponse`
 * @returns {Promise<object>} the event
 */
export function fetchEvent(scope, url, init = {}) {
	const { mode, preloadResponse, ...requestInit } = init;
	const request = new Request(new URL(url, ORIGIN).href, requestInit);
	if (mode) {
		Object.defineProperty(request, 'mode', { value: mode });
	}
	return dispatch(scope, 'fetch', { request, preloadResponse });
}

/**
 * Sends a message to the worker and returns the reply.
 * @param {object} scope - the scope
 * @param {string} type - the message type
 * @param {*} [data] - the message data
 * @returns {Promise<object>} the reply
 */
export async function message(scope, type, data) {
	let reply;
	const port = { postMessage(body) { reply = body; } };
	await dispatch(scope, 'message', { data: { type, data }, ports: [port] });
	return reply;
}
