<?php

/**
 * TServiceWorkerService class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\TPropertyValue;
use Prado\Web\THttpHeaderName;
use Prado\Web\TMediaType;

/**
 * TServiceWorkerService class
 *
 * TServiceWorkerService serves the scripts and web app manifests of the
 * service workers of {@see TServiceWorkerModule}, and passes other requests to
 * the contributors that claim them ({@see IServiceWorkerRequestHandler}). The
 * module links or registers it, so an application configures the module
 * alone; a configured service takes properties as any service does.
 * ```xml
 * <services>
 *     <service id="sw" class="Prado\Web\Services\ServiceWorker\TServiceWorkerService" />
 * </services>
 * ```
 *
 * | Request | Response |
 * |---|---|
 * | `?sw=worker` | The script of the `default` worker |
 * | `?sw=worker&w=<name>` | The script of a named worker |
 * | `?sw=manifest`, `?sw=manifest&w=<name>` | The worker's manifest, as `application/manifest+json`; 404 without one |
 * | `?sw=<name>` | The contributor that claims the name; 404 when none does |
 *
 * Every script URL is in the entry script's directory, so its maximum scope
 * covers the worker scopes below it; the script response also carries
 * `Service-Worker-Allowed` with the worker's scope, for URL formats that put
 * the script deeper. Scripts and manifests carry `Cache-Control: no-cache` and
 * an `ETag`, and a matching `If-None-Match` is answered 304, so the browser's
 * update check transfers nothing when no contribution changed.
 *
 * {@see setModuleID ModuleID} names the module; `Auto` uses the first
 * TServiceWorkerModule of the application.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServiceWorkerService extends \Prado\TService
{
	/**
	 * The default service ID.
	 */
	public const SERVICE_ID = 'sw';

	/**
	 * The request of a worker script.
	 */
	public const REQUEST_WORKER = 'worker';

	/**
	 * The request of a web app manifest.
	 */
	public const REQUEST_MANIFEST = 'manifest';

	/**
	 * The request names a contributor cannot claim.
	 */
	public const RESERVED_REQUESTS = [self::REQUEST_WORKER, self::REQUEST_MANIFEST];

	/**
	 * The request parameter of a named worker.
	 */
	public const WORKER_PARAMETER = 'w';

	/** @var string the configured module ID */
	private string $_moduleID = TServiceWorkerModule::AUTO;

	/** @var bool whether the module registered this service */
	private bool $_autoRegistered = false;

	/** @var ?TServiceWorkerModule the module */
	private ?TServiceWorkerModule $_module = null;

	/**
	 * @return string the module ID, or `Auto`. Defaults to `Auto`.
	 */
	public function getModuleID(): string
	{
		return $this->_moduleID;
	}

	/**
	 * @param string $value the module ID, or `Auto` for the first TServiceWorkerModule
	 */
	public function setModuleID($value): void
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->_moduleID = ($value === '' || strcasecmp($value, TServiceWorkerModule::AUTO) === 0) ? TServiceWorkerModule::AUTO : $value;
		$this->_module = null;
	}

	/**
	 * @return bool whether TServiceWorkerModule registered this service. Defaults to false.
	 */
	public function getAutoRegistered(): bool
	{
		return $this->_autoRegistered;
	}

	/**
	 * @param bool|string $value whether TServiceWorkerModule registered this service
	 */
	public function setAutoRegistered($value): void
	{
		$this->_autoRegistered = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns the module of {@see getModuleID ModuleID}.
	 * @throws TConfigurationException if no TServiceWorkerModule has the ID, or the
	 *   application has none.
	 * @return TServiceWorkerModule the module
	 */
	public function getModule(): TServiceWorkerModule
	{
		if ($this->_module !== null) {
			return $this->_module;
		}
		$app = $this->getApplication();
		$id = $this->_moduleID;
		if ($id === TServiceWorkerModule::AUTO) {
			$id = array_key_first($app->getModulesByType(TServiceWorkerModule::class));
			if ($id === null) {
				throw new TConfigurationException('serviceworkerservice_module_required');
			}
		}
		$module = $app->getModule($id);
		if (!$module instanceof TServiceWorkerModule) {
			throw new TConfigurationException('serviceworkerservice_module_invalid', $id);
		}
		return $this->_module = $module;
	}

	/**
	 * Answers the request named by the service parameter.
	 * @throws THttpException 404 for an unknown worker, a worker without a
	 *   manifest, or a request name that no contributor claims.
	 */
	public function run()
	{
		$name = (string) $this->getRequest()->getServiceParameter();
		if ($name === '' || $name === self::REQUEST_WORKER) {
			$this->runWorker($this->getRequestedWorker());
		} elseif ($name === self::REQUEST_MANIFEST) {
			$this->runManifest($this->getRequestedWorker());
		} elseif (($handler = $this->getModule()->getRequestHandler($name)) !== null) {
			$handler->runServiceWorkerRequest($name, $this);
		} else {
			throw new THttpException(404, 'serviceworkerservice_request_unknown', $name);
		}
	}

	/**
	 * Returns the worker the `w` request parameter names, or the `default` worker.
	 * @throws THttpException 404 if the worker is not defined.
	 * @return TServiceWorkerDefinition the worker
	 */
	public function getRequestedWorker(): TServiceWorkerDefinition
	{
		$name = $this->getRequest()->itemAt(self::WORKER_PARAMETER);
		$name = is_string($name) && $name !== '' ? $name : TServiceWorkerModule::DEFAULT_WORKER;
		if (($worker = $this->getModule()->getWorker($name)) === null) {
			throw new THttpException(404, 'serviceworkerservice_worker_unknown', $name);
		}
		return $worker;
	}

	/**
	 * Sends the script of a worker.
	 * @param TServiceWorkerDefinition $worker the worker
	 */
	protected function runWorker(TServiceWorkerDefinition $worker): void
	{
		$response = $this->getResponse();
		$response->appendHeader(THttpHeaderName::ServiceWorkerAllowed . ': ' . $worker->getScope());
		$this->sendBody($this->getModule()->renderScript($worker), TMediaType::JAVASCRIPT);
	}

	/**
	 * Sends the manifest of a worker.
	 * @param TServiceWorkerDefinition $worker the worker
	 * @throws THttpException 404 if the worker has no manifest.
	 */
	protected function runManifest(TServiceWorkerDefinition $worker): void
	{
		if (($data = $worker->getManifestData()) === null) {
			throw new THttpException(404, 'serviceworkerservice_manifest_unknown', $worker->getName());
		}
		$this->sendBody(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), TMediaType::MANIFEST_JSON);
	}

	/**
	 * Sends a body with `Cache-Control: no-cache` and an `ETag`, or 304 when the
	 * request's `If-None-Match` matches.
	 * @param string $body the body
	 * @param string $mediaType the media type
	 */
	protected function sendBody(string $body, string $mediaType): void
	{
		$response = $this->getResponse();
		$etag = '"' . substr(hash('sha256', $body), 0, 32) . '"';
		$response->setContentType($mediaType);
		$response->setCharset('UTF-8');
		$response->appendHeader(THttpHeaderName::CacheControl . ': no-cache');
		$response->appendHeader(THttpHeaderName::ETag . ': ' . $etag);
		$match = (string) $this->getRequest()->getHeader(THttpHeaderName::IfNoneMatch, '');
		if ($match !== '' && in_array($etag, array_map('trim', explode(',', $match)), true)) {
			$response->setStatusCode(304);
			return;
		}
		$response->write($body);
	}
}
