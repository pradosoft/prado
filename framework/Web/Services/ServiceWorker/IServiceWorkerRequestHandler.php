<?php

/**
 * IServiceWorkerRequestHandler interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

/**
 * IServiceWorkerRequestHandler interface
 *
 * IServiceWorkerRequestHandler answers requests of {@see TServiceWorkerService}
 * beyond the worker script and the manifest. A contributor of
 * {@see TServiceWorkerModule} that implements it claims request names; the
 * service passes `?sw=<name>` to the contributor that claims the name. A Web
 * Push module claims `subscribe` and `unsubscribe`, for example.
 *
 * The names `worker` and `manifest` belong to the service and cannot be claimed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IServiceWorkerRequestHandler
{
	/**
	 * @return string[] the request names this handler answers
	 */
	public function getServiceWorkerRequestNames(): array;

	/**
	 * Answers a request through the service's response.
	 * @param string $name the request name
	 * @param TServiceWorkerService $service the running service
	 */
	public function runServiceWorkerRequest(string $name, TServiceWorkerService $service): void;
}
