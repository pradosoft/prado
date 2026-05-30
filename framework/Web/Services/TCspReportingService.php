<?php

/**
 * TCspReportingService class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Prado;
use Prado\TService;
use Prado\Util\TLogger;
use Prado\Web\THttpHeaderName;
use Prado\Web\TMediaType;

/**
 * TCspReportingService class
 *
 * TCspReportingService is a lightweight Prado service that receives
 * Content-Security-Policy (CSP) violation reports sent by browsers and logs
 * them via Prado's standard logging infrastructure.
 *
 * Browsers send violation reports as HTTP POST requests to the URL declared in
 * the `report-to` CSP directive and the accompanying `Reporting-Endpoints`
 * header. TCspReportingService handles both payload formats browsers currently use:
 *
 * - **Legacy format** (`Content-Type: application/csp-report`, {@see TMediaType::CSP_REPORT}):
 *   `{"csp-report": { "blocked-uri": "...", "violated-directive": "...", ... }}`
 * - **Modern format** (`Content-Type: application/reports+json`, {@see TMediaType::REPORTS_JSON}):
 *   `[{"type": "csp-violation", "body": { "blockedURL": "...", ... }}]`
 *
 * **Registration.** Add the service to your application configuration. The
 * service ID (`csp-reporting` by default) must match the
 * {@see \Prado\Web\HttpHeaders\THttpHeadersManager::setReportingServiceId() ReportingServiceId}
 * property on {@see \Prado\Web\HttpHeaders\THttpHeadersManager}:
 *
 * ```xml
 * <services>
 * 		<service id="csp-reporting" class="Prado\Web\Services\TCspReportingService" />
 * </services>
 * ```
 *
 * Or in PHP application config:
 * ```php
 * 'services' => [
 *     'csp-reporting' => ['class' => TCspReportingService::class],
 * ],
 * ```
 *
 * **Automatic wiring via `ReporterMode`.** Set
 * {@see \Prado\Web\HttpHeaders\THttpHeadersManager::setReporterMode() THttpHeadersManager.ReporterMode}
 * to wire this service into your CSP headers automatically — no manual URL
 * configuration required. Three values are supported:
 *
 * - **`false`** *(default)* — no automatic wiring; configure headers manually.
 * - **`true`** — the manager resolves this service's URL via the URL manager,
 *   injects it into `Reporting-Endpoints`, and adds a `report-to` directive to
 *   every CSP header that lacks one. CSP remains **enforcing**: resources are
 *   blocked *and* violations are reported.
 * - **`'Auto'`** — same as `true`, but additionally converts every enforcing
 *   `Content-Security-Policy` header to `Content-Security-Policy-Report-Only`
 *   before the response is sent. Resources are **never blocked**; only violation
 *   reports are generated. Use this during initial CSP roll-out or development
 *   to audit violations without breaking your pages, then switch to `true` once
 *   the policy is stable.
 *
 * **Events.** Each violation fires {@see onViolation()} with a
 * {@see TCspViolationParameter} argument. Attach handlers to process or store
 * violations beyond the default log output.
 *
 * The service is instantiated during `TApplication::initApplication()`, so it is
 * available via {@see getInstance()} from the `onInitComplete` application event
 * onwards.Attach handlers from a module's `init()` by hooking either event:
 *
 * ```php
 * // In a TModule::init() — attach after all services are ready:
 * $this->getApplication()->onInitComplete[] = function () {
 *     TCspReportingService::getInstance()?->onViolation[] = [$this, 'handleCspViolation'];
 * };
 *
 * public function handleCspViolation(TCspReportingService $sender, TCspViolationParameter $param): void
 * {
 *     // Store $param->getReport() in your database, notify Sentry, etc.
 * }
 * ```
 *
 * **Log output.** Every violation received is logged at {@see TLogger::WARNING}
 * level under the `Prado.Web.Services.TCspReportingService` category regardless
 * of event handlers. Use Prado's route configuration to direct these entries to
 * a file, database, or external sink.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see \Prado\Web\HttpHeaders\THttpHeadersManager::setReporterMode()
 * @see \Prado\Web\HttpHeaders\THttpHeaderCsp
 * @see \Prado\Web\HttpHeaders\THttpHeaderReportingEndpoints
 * @see TCspViolationParameter
 */
class TCspReportingService extends TService
{
	/**
	 * Default service ID used when registering this service in app configuration.
	 * Must match {@see \Prado\Web\HttpHeaders\THttpHeadersManager::setReportingServiceId()}.
	 */
	public const SERVICE_ID = 'csp-reporting';

	/**
	 * @var bool `true` when this service was registered automatically by
	 *   {@see \Prado\Web\HttpHeaders\THttpHeadersManager} (i.e. `ReporterMode`
	 *   is `true` or `'Auto'` and no service was found in the app config).
	 *   `false` (default) means the developer declared the service explicitly.
	 */
	private bool $_autoRegistered = false;

	/**
	 * Returns `true` when this service was auto-registered by
	 * {@see \Prado\Web\HttpHeaders\THttpHeadersManager}, `false` when declared
	 * explicitly in app configuration.
	 * @return bool `true` when auto-registered
	 */
	public function getAutoRegistered(): bool
	{
		return $this->_autoRegistered;
	}

	/**
	 * Sets whether this service was registered automatically by the header manager.
	 * @param bool|string $value `true` when auto-registered; coerced via
	 *   {@see \Prado\TPropertyValue::ensureBoolean()}
	 */
	public function setAutoRegistered(bool|string $value): void
	{
		$this->_autoRegistered = \Prado\TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns the running instance of TCspReportingService when this service is
	 * the active service for the current request, or `null` in all other contexts
	 * (e.g. during a normal page request).
	 *
	 * The service is instantiated during `TApplication::initApplication()`, so
	 * `getInstance()` returns a non-null value from the `onInitComplete`
	 * application event onwards. Both `onInitComplete` and `onPreRunService`
	 * are valid points at which to attach {@see onViolation} handlers:
	 *
	 * ```php
	 * $this->getApplication()->onInitComplete[] = function () {
	 *     TCspReportingService::getInstance()?->onViolation[] = [$this, 'myHandler'];
	 * };
	 * ```
	 *
	 * @param ?\Prado\TApplication $app application instance; defaults to
	 *   {@see \Prado\Prado::getApplication()}
	 * @return ?static the active service instance, or `null`
	 * @todo remove this, already added to TService on master branch
	 */
	public static function getInstance(?\Prado\TApplication $app = null): ?static
	{
		$app ??= Prado::getApplication();
		$service = $app?->getService();
		return ($service instanceof static) ? $service : null;
	}

	/**
	 * Handles an incoming CSP violation report POST from the browser.
	 *
	 * Returns HTTP 204 No Content on success, 405 Method Not Allowed for
	 * non-POST requests, and 400 Bad Request for malformed JSON bodies.
	 */
	public function run(): void
	{
		$request = $this->getRequest();
		$response = $this->getResponse();

		if ($request->getRequestType() !== 'POST') {
			$response->setStatusCode(405);
			$response->appendHeader(THttpHeaderName::Allow . ': POST');
			return;
		}

		$body = $this->readBody();

		if (empty($body)) {
			$response->setStatusCode(204);
			return;
		}

		$data = json_decode($body, true);
		if ($data === null) {
			$response->setStatusCode(400);
			return;
		}

		if (isset($data['csp-report']) && is_array($data['csp-report'])) {
			// Legacy format: TMediaType::CSP_REPORT → {"csp-report": {...}}
			$this->logViolation($data['csp-report']);
		} elseif (is_array($data)) {
			// Modern format: TMediaType::REPORTS_JSON → [{type, age, body}, ...]
			foreach ($data as $report) {
				if (
					is_array($report)
					&& ($report['type'] ?? '') === 'csp-violation'
					&& isset($report['body'])
					&& is_array($report['body'])
				) {
					$this->logViolation($report['body']);
				}
			}
		}

		$response->setStatusCode(204);
	}

	/**
	 * Returns the raw HTTP request body.
	 *
	 * The default implementation reads from `php://input`, which is the
	 * standard PHP stream for the raw POST body. Override this method in a
	 * subclass to supply a synthetic body for unit testing without needing a
	 * live HTTP request:
	 *
	 * ```php
	 * class TestableCspReportingService extends TCspReportingService
	 * {
	 *     public false|string $body = '';
	 *
	 *     protected function readBody(): false|string
	 *     {
	 *         return $this->body;
	 *     }
	 * }
	 * ```
	 *
	 * @return false|string the raw request body, or `false` when the stream
	 *   cannot be opened
	 */
	protected function readBody(): false|string
	{
		return file_get_contents('php://input');
	}

	/**
	 * Raises the {@see onViolation()} event and logs the violation.
	 *
	 * Called once per violation object extracted from the browser's POST body.
	 * Subclasses may override this method to add pre- or post-processing around
	 * the event and log entry.
	 *
	 * @param array<string,mixed> $report the parsed violation object
	 */
	protected function logViolation(array $report): void
	{
		$param = new TCspViolationParameter($report);

		Prado::log(
			'Prado CSP violation report received —'
				. ' directive="' . $param->getEffectiveDirective() . '"'
				. ' blocked="' . $param->getBlockedUrl() . '"'
				. ' document="' . $param->getDocumentUrl() . '"'
				. ' raw=' . json_encode($report),
			TLogger::WARNING,
			static::class
		);

		$this->onViolation($param);
	}

	/**
	 * Raises the `OnViolation` event.
	 *
	 * Attach handlers to this event to process or store CSP violations in
	 * addition to the default log output. The recommended attachment point is
	 * from a module using the application's `onPreRunService` event — see the
	 * class docblock for a complete example.
	 *
	 * @param TCspViolationParameter $param violation data
	 */
	public function onViolation(TCspViolationParameter $param): void
	{
		$this->raiseEvent('onViolation', $this, $param);
	}
}
