/*! PRADO service worker client | github.com/pradosoft/prado */

/**
 * Prado.ServiceWorker registers the page's service worker and exchanges
 * messages with it. TServiceWorker renders the registration:
 *
 * ```javascript
 * Prado.ServiceWorker.register('/index.php?sw=worker', { scope: '/', worker: 'default' });
 * ```
 *
 * The client raises document events:
 *
 * | Event | When | `detail` |
 * |---|---|---|
 * | `prado:serviceworkerready` | A worker controls the page | `{registration}` |
 * | `prado:serviceworkerupdate` | A new worker waits for the old one's pages to close | `{registration, worker, activate()}` |
 * | `prado:serviceworkermessage` | The worker posts to its pages (`prado.postToClients()`) | `{type, data}` |
 *
 * `detail.activate()` tells the waiting worker to skip waiting and reloads the
 * page once the new worker controls it, which reloads the worker's other pages
 * too as they navigate.
 *
 * ```javascript
 * document.addEventListener('prado:serviceworkerupdate', (e) => {
 *     if (confirm('A new version is available. Reload?')) {
 *         e.detail.activate();
 *     }
 * });
 * const version = await Prado.ServiceWorker.postMessage('version');
 * ```
 *
 * Without service worker support (`navigator.serviceWorker`, which needs a
 * secure context), register() resolves to null and postMessage() rejects.
 */
Prado.ServiceWorker = {
	/** @type {object<string, Promise<ServiceWorkerRegistration|null>>} registrations by script URL */
	registrations: {},

	/** @type {number} milliseconds a postMessage() waits for the reply */
	timeout: 10000,

	/** @type {boolean} whether the client listens to the container */
	listening: false,

	/** @type {boolean} whether a controller change reloads the page */
	reloading: false,

	/**
	 * @returns {boolean} whether the browser supports service workers here
	 */
	isSupported()
	{
		return typeof navigator !== 'undefined' && !!navigator.serviceWorker;
	},

	/**
	 * Registers a worker script once per page load.
	 * @param {string} url - the script URL
	 * @param {object} [options] - `scope` and `worker`
	 * @returns {Promise<ServiceWorkerRegistration|null>} the registration, or null
	 *   without support or when the registration fails
	 */
	register(url, options)
	{
		if (!this.isSupported()) {
			return Promise.resolve(null);
		}
		if (this.registrations[url]) {
			return this.registrations[url];
		}
		options = options || {};
		this.listen();
		const settings = { updateViaCache: 'none' };
		if (options.scope) {
			settings.scope = options.scope;
		}
		this.registrations[url] = navigator.serviceWorker.register(url, settings).then((registration) => {
			this.track(registration);
			if (navigator.serviceWorker.controller) {
				this.dispatch('prado:serviceworkerready', { registration });
			}
			return registration;
		}, (error) => {
			console.warn('PRADO service worker: registration of ' + url + ' failed.', error);
			return null;
		});
		return this.registrations[url];
	},

	/**
	 * Listens to the container's messages and controller changes once.
	 */
	listen()
	{
		if (this.listening) {
			return;
		}
		this.listening = true;
		const container = navigator.serviceWorker;
		container.addEventListener('message', (event) => {
			const message = event.data;
			if (message && message.prado === 'serviceworker') {
				this.dispatch('prado:serviceworkermessage', { type: message.type, data: message.data });
			}
		});
		container.addEventListener('controllerchange', () => {
			if (this.reloading) {
				this.reloading = false;
				this.reload();
				return;
			}
			container.ready.then((registration) => this.dispatch('prado:serviceworkerready', { registration }));
		});
	},

	/**
	 * Reports a waiting worker now and each new worker that finishes installing.
	 * A worker that installs while no worker controls the page is the first one,
	 * and activates without waiting.
	 * @param {ServiceWorkerRegistration} registration - the registration
	 */
	track(registration)
	{
		if (registration.waiting && navigator.serviceWorker.controller) {
			this.dispatchUpdate(registration, registration.waiting);
		}
		registration.addEventListener('updatefound', () => {
			const worker = registration.installing;
			if (!worker) {
				return;
			}
			worker.addEventListener('statechange', () => {
				if (worker.state === 'installed' && navigator.serviceWorker.controller) {
					this.dispatchUpdate(registration, worker);
				}
			});
		});
	},

	/**
	 * Raises `prado:serviceworkerupdate`.
	 * @param {ServiceWorkerRegistration} registration - the registration
	 * @param {ServiceWorker} worker - the waiting worker
	 */
	dispatchUpdate(registration, worker)
	{
		this.dispatch('prado:serviceworkerupdate', {
			registration,
			worker,
			activate: () => this.activate(registration),
		});
	},

	/**
	 * Activates the waiting worker of a registration and reloads the page when it
	 * takes control.
	 * @param {ServiceWorkerRegistration} registration - the registration
	 * @returns {boolean} whether a worker was waiting
	 */
	activate(registration)
	{
		const worker = registration.waiting;
		if (!worker) {
			return false;
		}
		this.reloading = true;
		worker.postMessage({ type: 'skipWaiting' });
		return true;
	},

	/**
	 * Sends a message to the worker that controls the page, or else to the active
	 * worker of the ready registration, and waits for the reply.
	 * @param {string} type - the message type
	 * @param {*} [data] - the message data
	 * @returns {Promise<*>} the handler's result; rejects when the handler throws,
	 *   no handler has the type, or no reply comes within {@link timeout} ms
	 */
	postMessage(type, data)
	{
		if (!this.isSupported()) {
			return Promise.reject(new Error('Service workers are not supported.'));
		}
		const container = navigator.serviceWorker;
		const target = container.controller
			? Promise.resolve(container.controller)
			: container.ready.then((registration) => registration.active);
		return target.then((worker) => new Promise((resolve, reject) => {
			if (!worker) {
				reject(new Error('No active service worker.'));
				return;
			}
			const channel = new MessageChannel();
			const timer = setTimeout(() => {
				channel.port1.close();
				reject(new Error('The service worker did not reply to ' + type + '.'));
			}, this.timeout);
			channel.port1.onmessage = (event) => {
				clearTimeout(timer);
				channel.port1.close();
				const reply = event.data || {};
				if (reply.ok) {
					resolve(reply.result);
				} else {
					reject(new Error(reply.error || 'The service worker failed ' + type + '.'));
				}
			};
			worker.postMessage({ type, data }, [channel.port2]);
		}));
	},

	/**
	 * Dispatches a document event.
	 * @param {string} name - the event name
	 * @param {object} detail - the event detail
	 */
	dispatch(name, detail)
	{
		document.dispatchEvent(new CustomEvent(name, { detail }));
	},

	/**
	 * Reloads the page.
	 */
	reload()
	{
		window.location.reload();
	},
};
