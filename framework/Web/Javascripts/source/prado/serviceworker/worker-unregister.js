/*! PRADO service worker removal | github.com/pradosoft/prado */

/**
 * Service worker that removes itself.
 *
 * TServiceWorkerService serves this script, after `PRADO_SERVICE_WORKER`, for a
 * disabled worker. The browser installs it as an update of the deployed worker.
 * It activates at once, deletes the worker's caches (prefix `prado-<worker>-`),
 * unregisters, and reloads the windows it controlled, which then load without a
 * worker.
 */
(function (self, options)
{
	'use strict';

	const prefix = 'prado-' + options.worker + '-';

	self.addEventListener('install', (event) => {
		event.waitUntil(self.skipWaiting());
	});

	self.addEventListener('activate', (event) => {
		event.waitUntil((async () => {
			const clients = await self.clients.matchAll({ type: 'window' });
			const names = (await self.caches.keys()).filter((key) => key.startsWith(prefix));
			await Promise.all(names.map((name) => self.caches.delete(name)));
			await self.registration.unregister();
			await Promise.all(clients.map((client) => client.navigate(client.url).catch(() => null)));
		})());
	});
})(self, PRADO_SERVICE_WORKER);
