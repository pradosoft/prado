<?php

/**
 * THttpHeadersManager class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Web\Services\TCspReportingService;
use Prado\Web\THttpHeaderName;
use Prado\Xml\TXmlElement;

/**
 * THttpHeadersManager class
 *
 * THttpHeadersManager is a module that sends additional HTTP headers alongside
 * every response.
 *
 * **Wiring.** Load the manager as an application module and set
 * {@see \Prado\Web\THttpResponse::setHeadersManager() THttpResponse.HeadersManager}
 * to the module ID so {@see \Prado\Web\THttpResponse} knows which manager to use.
 * XML configuration:
 *
 * ```xml
 * <module id="httpHeaders" class="THttpHeadersManager"> ... </module>
 * <module id="response" class="THttpResponse" HeadersManager="httpHeaders" />
 * ```
 *
 * **Disabling.** Set `HeadersManager=""` (empty string) on `THttpResponse` to
 * suppress header management entirely even when a `THttpHeadersManager` module
 * is present.
 *
 * Use {@see THttpHeaderName} constants for header names and {@see TCspDirective}
 * constants for CSP policy directive names in PHP configuration to avoid
 * hard-coded strings.
 *
 * **Name→class registry.** When a plain `<header HeaderName="..." HeaderValue="..." />`
 * node (or its PHP-array equivalent) uses a name registered in the
 * {@see getNameClassMap() name→class map}, the manager automatically promotes it to
 * the appropriate typed class instead of the {@see getDefaultHeaderClass() default class}.
 * The built-in map covers CSP, HSTS, and Reporting-Endpoints; extend it at runtime via
 * {@see addNameClassMap()} or {@see registerHeaderClass()}, or declare associations
 * directly in the module configuration using `<headerclass name="…" class="…" />`
 * (XML) or the `headerclasses` array key (PHP) — both are processed before any
 * `<header>` nodes so the class is available for auto-promotion immediately.
 *
 * **Header relationships.** Several headers must be used together to have the
 * intended effect; this class validates what it can and logs warnings for the rest:
 *
 * - **`report-to` ↔ `Reporting-Endpoints`** — the token in a
 *   {@see TCspDirective::ReportTo} directive must exactly match a name declared
 *   in a {@see THttpHeaderReportingEndpoints} header. {@see THttpHeaderCsp}
 *   validates this just before headers are sent (in
 *   {@see THttpHeaderBase::finalizeHeader()}) so headers added via the CRUD
 *   API after `init()` are included in the check.
 * - **COEP ↔ COOP** — `Cross-Origin-Embedder-Policy` and
 *   `Cross-Origin-Opener-Policy` must both be set to achieve cross-origin
 *   isolation (`SharedArrayBuffer` etc.). Set both as plain {@see THttpHeader}
 *   entries.
 * - **`frame-ancestors` ↔ `X-Frame-Options`** — set both for full browser
 *   coverage: `frame-ancestors` takes precedence in CSP-aware browsers;
 *   `X-Frame-Options` covers older ones.
 *
 * **Multi-value headers.** HTTP allows the same header name to appear more than
 * once (e.g. `Set-Cookie`, `Link`). Add multiple header instances with the same
 * name to the list via {@see addHeader()}, and override
 * {@see THttpHeaderBase::getReplace()} to return `false` on those headers so
 * each one is sent as a separate line rather than replacing the previous one.
 *
 * **Send pipeline.** When {@see ensureHeadersSent()} fires, three phases run in order:
 * (1) {@see finalizeReporterService()} wires the CSP reporter — or, when
 * {@see setReportingServiceMode() ReportingServiceMode} is `false` and a
 * {@see TCspReportingService} is already registered, resolves any
 * {@see THttpHeaderBase::REPORT_URI} sentinels without triggering auto-wiring;
 * (2) each header's {@see THttpHeaderBase::finalizeHeader()} runs; (3)
 * {@see validateHeaders()} performs cross-header relationship checks and logs warnings.
 *
 * **Subclass extension.** {@see buildHeader()} is `protected` and may be overridden
 * to customize how individual header entries are constructed from config.
 * {@see loadDefaultHeaders()} controls which headers are added automatically on
 * {@see init()}.
 *
 * PHP application configuration equivalent:
 * ```php
 * 'modules' => [
 *     'httpHeaders' => [
 *         'class' => 'THttpHeadersManager',
 *         'headerclasses' => [
 *             // Register a custom typed class for a non-standard header name.
 *             ['name' => 'X-My-Header', 'class' => MyApp\Web\HttpHeaders\TMyHeader::class],
 *         ],
 *         'headers' => [
 *             ['properties' => ['HeaderName' => THttpHeaderName::StrictTransportSecurity, 'HeaderValue' => 'max-age=31536000']],
 *             ['properties' => ['HeaderName' => THttpHeaderName::XContentTypeOptions,     'HeaderValue' => 'nosniff']],
 *             ['properties' => ['HeaderName' => THttpHeaderName::XFrameOptions,           'HeaderValue' => 'DENY']],
 *             [
 *                 'class' => THttpHeaderCsp::class,
 *                 'policies' => [
 *                     ['name' => TCspDirective::DefaultSrc, 'value' => "'self' www.gstatic.com NONCE"],
 *                     ['name' => TCspDirective::FrameSrc,   'value' => "'self' www.google.com"],
 *                 ],
 *             ],
 *         ],
 *     ],
 *     // No 'response' entry needed — Auto mode picks up 'httpHeaders' automatically.
 * ],
 * ```
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class THttpHeadersManager extends TModule
{
	/** Default class instantiated for generic headers. */
	public const DEFAULT_HEADER_CLASS = THttpHeader::class;

	/**
	 * Default endpoint name token used in the `Reporting-Endpoints` header and the
	 * CSP `report-to` directive when {@see setReportingEndpointName() ReportingEndpointName}
	 * is not overridden. Distinct from {@see TCspReportingService::SERVICE_ID}, which is
	 * the Prado module registry key for the reporter service.
	 */
	public const DEFAULT_REPORTING_SERVICE_NAME = 'prado-csp-reporter';

	/**
	 * Default value for {@see setReportingServiceId() ReportingServiceId}: auto-detects
	 * the reporter service by class at finalize time, falling back to
	 * {@see TCspReportingService::SERVICE_ID} when auto-registering.
	 */
	public const DEFAULT_REPORTING_SERVICE_ID = 'Auto';

	/** Default value for {@see setReportOnly() ReportOnly}: inherits from application mode. */
	public const DEFAULT_REPORT_ONLY = null;

	/** Default value for {@see setReportingServiceMode() ReportingServiceMode}: auto-wires when conditions are met. */
	public const DEFAULT_REPORTING_SERVICE_MODE = 'Auto';

	/**
	 * @var THttpHeaderBase[] list of headers in configuration / insertion order.
	 */
	private array $_headers = [];

	/**
	 * @var bool whether headers have been sent.
	 */
	private bool $_headersSent = false;

	/**
	 * @var bool whether header sending has been externally handled and
	 *   {@see ensureHeadersSent()} should be a no-op.
	 */
	private bool $_isHandled = false;

	/**
	 * @var string default class used when no `class` is specified in config.
	 */
	private string $_defaultHeaderClass = THttpHeader::class;

	/**
	 * @var array<string,string> lowercase header-name → typed-class registry.
	 *   Keys are always stored lowercase so lookup is O(1) and case-insensitive.
	 *   Lazily populated from {@see getDefaultNameClassMap()} on first access.
	 */
	private array $_nameClassMap = [];

	/**
	 * @var bool whether {@see $_nameClassMap} has been populated from defaults.
	 *   Tracks initialization separately from the map contents so that a subclass
	 *   overriding {@see getDefaultNameClassMap()} to return `[]` does not trigger
	 *   perpetual re-initialization.
	 */
	private bool $_nameClassMapInitialized = false;

	/**
	 * @var ?bool Whether to downgrade every enforcing `Content-Security-Policy` header
	 *   to `Content-Security-Policy-Report-Only` before sending. Orthogonal to
	 *   {@see $_reportingServiceMode}, which controls whether Prado manages the reporting
	 *   endpoint infrastructure.
	 *
	 *   `true`  — Always downgrade to report-only; violations are reported but not blocked.
	 *   `false` — Always keep CSP enforcing; violations block content.
	 *   `null`  — (default) Auto: resolves to `true` when the application runs in
	 *              {@see TApplicationMode::Debug} mode, `false` in Normal and Performance.
	 *              This lets a single configuration be safe in production and lenient in
	 *              development without any environment-specific overrides.
	 */
	private ?bool $_reportOnly = null;

	/**
	 * @var 'Auto'|bool Whether and how Prado manages the {@see TCspReportingService}
	 *   lifecycle — service registration, `Reporting-Endpoints` injection, and `report-to`
	 *   wiring into CSP headers. Orthogonal to {@see $_reportOnly}, which controls only
	 *   whether the browser enforces or merely reports violations.
	 *
	 *   `false`  — Hands off. No service is registered or linked; CSP headers are not
	 *               modified to reference an endpoint.
	 *   `true`   — Always ensure a service exists and wire it: inject `Reporting-Endpoints`
	 *               and a `report-to` directive into every {@see THttpHeaderCsp} that lacks
	 *               one.
	 *   `'Auto'` — Conditional. Activate service management only when evidence of reporting
	 *               is present:
	 *               (1) At config time: a {@see TCspReportingService} is already registered
	 *                   (found by class scan or by the stated literal service ID), OR
	 *                   {@see $_reportOnly} is `true`.
	 *               (2) At finalize time: any {@see THttpHeaderCsp} already carries a
	 *                   `report-to` directive (condition 3).
	 *               When none of these conditions are met the reporter is not wired.
	 */
	private bool|string $_reportingServiceMode = 'Auto';

	/**
	 * @var string Identifies *which* {@see TCspReportingService} in the Prado module
	 *   registry handles incoming CSP violation reports. Orthogonal to
	 *   {@see $_reportingServiceMode}, which controls *whether/how* headers link to the reporter.
	 *
	 *   `'Auto'`         — (default) scan the application's module registry for any service
	 *                       that is a {@see TCspReportingService} subclass; adopt its ID on
	 *                       first match. When no match is found, auto-register a new service
	 *                       under {@see TCspReportingService::SERVICE_ID} and adopt that ID.
	 *   Any other string — treat the value as an explicit module-registry key. The service
	 *                       registered under that key is used for URL construction; if the key
	 *                       is absent the service is auto-registered under it.
	 */
	private string $_reportingServiceId;

	/**
	 * @var string Endpoint name injected into `Reporting-Endpoints` and the
	 *   CSP `report-to` directive when the reporter service is active.
	 */
	private string $_reportingEndpointName;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Has set {@see getReportingEndpointName() ReportingEndpointName},
	 * {@see getReportingServiceId() ReportingServiceId}, and
	 * {@see getDefaultHeaderClass() DefaultHeaderClass} to their defaults.
	 */
	public function __construct()
	{
		$this->setDefaultHeaderClass(static::DEFAULT_HEADER_CLASS);
		$this->setReportingEndpointName(static::DEFAULT_REPORTING_SERVICE_NAME);
		$this->setReportingServiceId(static::DEFAULT_REPORTING_SERVICE_ID);
		parent::__construct();
	}

	/**
	 * Has loaded header-class associations and headers from `$config`, added default
	 * headers, and attached {@see ensureReportingServiceRegistered()} to
	 * {@see \Prado\TApplication::onConfiguration()} at priority 20.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this module.
	 * @return void
	 */
	public function init($config): void
	{
		parent::init($config);
		$normalized = $this->normalizeConfig($config);
		$this->loadHeaderClasses($normalized);
		$this->loadHeaders($normalized);
		$this->loadDefaultHeaders();
		$this->initComplete();
		// Defer reporter-service registration to onConfiguration so that other modules
		// loaded alongside this one can interact with THttpHeadersManager (e.g. register
		// their own TCspReportingService) before wiring is finalized.
		$this->getApplication()->attachEventHandler(
			'onConfiguration',
			[$this, 'ensureReportingServiceRegistered'],
			20
		);
	}

	/**
	 * Has called {@see THttpHeaderBase::initComplete()} on every header in insertion order.
	 * Override to add post-load validation or setup.
	 * @return void
	 */
	protected function initComplete(): void
	{
		foreach ($this->getHeaders() as $header) {
			$header->initComplete();
		}
	}

	/**
	 * Has resolved {@see getReportingServiceId() ReportingServiceId} from `'Auto'` to a
	 * concrete ID and, when conditions warrant it, has registered a {@see TCspReportingService}.
	 *
	 * **`'Auto'` mode.** (1) Scans the registry for an existing {@see TCspReportingService};
	 * when found, adopts its ID. (2) When not found and report-only is active or mode is
	 * `true`, registers under {@see TCspReportingService::SERVICE_ID} and adopts that ID.
	 * (3) Otherwise defers to {@see finalizeReporterService()} (condition 3: an existing
	 * `report-to` directive in any CSP header).
	 *
	 * **Literal-ID mode.** (1) Validates that the registered service is a
	 * {@see TCspReportingService} subclass; logs a warning if not. (2) When no service is
	 * registered under the stated ID and wiring is forced, registers a new service under it.
	 *
	 * Attached to {@see \Prado\TApplication::onConfiguration()} at priority 20 so all other
	 * modules have initialized first.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function ensureReportingServiceRegistered($sender = null, $param = null): void
	{
		$mode = $this->getReportingServiceMode();
		if ($mode === false) {
			return;
		}
		$app = $this->getApplication();
		if (!$app) {
			return;
		}
		$reportingServiceId = $this->getReportingServiceId();

		// Determine whether to force-register a service right now.
		// 'Auto' defers unless reportOnly is active or an existing service is found.
		$forceRegister = $mode === true || $this->resolveReportOnly();

		if ($reportingServiceId === 'Auto') {
			$foundId = $app->getRegisteredServiceByClass(TCspReportingService::class);
			if ($foundId !== null) {
				// Adopt the existing service's ID so finalizeReporterService() uses it
				// directly without performing a second class scan.
				$this->setReportingServiceId($foundId);
				return;
			}
			if ($forceRegister) {
				// Register under the default ID and adopt it.
				$serviceId = TCspReportingService::SERVICE_ID;
				$app->registerService($serviceId, TCspReportingService::class, ['AutoRegistered' => true]);
				$this->setReportingServiceId($serviceId);
			}
			// else: 'Auto' + reportOnly=false — defer to finalizeReporterService (condition 3).
		} else {
			$entry = $app->getRegisteredService($reportingServiceId);
			if ($entry !== null) {
				// Validate that the registered service is actually a TCspReportingService.
				if (!is_a($entry[0], TCspReportingService::class, true)) {
					Prado::log(
						'THttpHeadersManager: ReportingServiceId="' . $reportingServiceId
						. '" is registered as ' . $entry[0] . ', which is not a'
						. ' TCspReportingService subclass. CSP violation reports will not be received.',
						\Prado\Util\TLogger::WARNING,
						static::class
					);
				}
				return;
			}
			if ($forceRegister) {
				$app->registerService($reportingServiceId, TCspReportingService::class, ['AutoRegistered' => true]);
			}
			// else: 'Auto' + reportOnly=false — defer to finalizeReporterService (condition 3).
		}
	}

	/**
	 * Has added built-in default headers that are not already present in the list.
	 * Override to change or extend the defaults.
	 *
	 * **Default added:** {@see THttpHeaderContentType} (`Content-Type: text/html`) —
	 * charset resolved at send time from {@see \Prado\I18N\TGlobalization} or `UTF-8`.
	 * @return void
	 */
	protected function loadDefaultHeaders(): void
	{
		if (!$this->hasHeader(THttpHeaderName::ContentType)) {
			$header = new THttpHeaderContentType();
			$this->addHeader($header);
			$header->init([]);
		}
	}

	// =========================================================================
	// Name→class registry
	// =========================================================================

	/**
	 * Returns the built-in header-name → typed-class associations. Override to change
	 * or extend the defaults.
	 * @return array<string,string>
	 */
	protected function getDefaultNameClassMap(): array
	{
		return [
			THttpHeaderName::ContentDisposition => THttpHeaderContentDisposition::class,
			THttpHeaderName::ContentType => THttpHeaderContentType::class,
			THttpHeaderName::ContentSecurityPolicy => THttpHeaderCsp::class,
			THttpHeaderName::ContentSecurityPolicyReportOnly => THttpHeaderCsp::class,
			THttpHeaderName::StrictTransportSecurity => THttpHeaderHsts::class,
			THttpHeaderName::ReportingEndpoints => THttpHeaderReportingEndpoints::class,
		];
	}

	/**
	 * Returns the name→class registry, lazily populated from defaults on first call.
	 * Keys are lowercase — use `strtolower()` for direct array lookups.
	 * @return array<string,string>
	 */
	public function getNameClassMap(): array
	{
		$this->ensureNameClassMap();
		return $this->getNameClassMapDirect();
	}

	/**
	 * Has populated the name→class map from {@see getDefaultNameClassMap()} on first call; no-op thereafter.
	 * Keys are stored lowercase so all O(1) lookups are case-insensitive by design.
	 * @return void
	 */
	public function ensureNameClassMap(): void
	{
		if (!$this->_nameClassMapInitialized) {
			$this->_nameClassMapInitialized = true;
			$this->_nameClassMap = array_change_key_case($this->getDefaultNameClassMap(), CASE_LOWER);
		}
	}

	/**
	 * Raw backing-field read; may be empty until {@see ensureNameClassMap()} has been called.
	 * @return array<string,string>
	 */
	protected function getNameClassMapDirect(): array
	{
		return $this->_nameClassMap;
	}

	/**
	 * Has merged additional header-name → class entries into the registry.
	 * New entries may override existing ones. Keys are stored lowercase — matching is always
	 * case-insensitive.
	 * @param array<string,string> $map header name → fully-qualified class name
	 * @return void
	 */
	public function addNameClassMap(array $map): void
	{
		$this->_nameClassMap = array_merge($this->getNameClassMap(), array_change_key_case($map, CASE_LOWER));
	}

	/**
	 * Has set a single header-name → class entry in the registry.
	 * The key is stored lowercase — matching is always case-insensitive.
	 * @param string $headerName header name (any casing)
	 * @param string $class fully-qualified class name extending {@see THttpHeaderBase}
	 * @return void
	 */
	protected function setNameClassMap(string $headerName, string $class): void
	{
		$this->_nameClassMap[strtolower($headerName)] = $class;
	}

	/**
	 * Has registered a single header name → class association in the registry.
	 * @param string $headerName canonical header name (e.g. `'X-My-Header'`)
	 * @param string $class fully-qualified class name extending {@see THttpHeaderBase}
	 * @return void
	 */
	public function registerHeaderClass(string $headerName, string $class): void
	{
		$this->ensureNameClassMap();
		$this->setNameClassMap($headerName, $class);
	}

	// =========================================================================
	// CRUD
	// =========================================================================

	/**
	 * Returns all loaded headers in insertion order.
	 * @return THttpHeaderBase[]
	 */
	public function getHeaders(): array
	{
		return $this->_headers;
	}

	/**
	 * Has appended `$header` to the backing list without duplicate or manager checks.
	 * @param THttpHeaderBase $header
	 * @return void
	 */
	protected function addHeaderDirect(THttpHeaderBase $header): void
	{
		$this->_headers[] = $header;
	}

	/**
	 * Has spliced the header at `$index` out of the backing list in-place.
	 * @param int $index zero-based index into `$this->_headers`
	 * @return void
	 */
	protected function removeHeaderDirect(int $index): void
	{
		array_splice($this->_headers, $index, 1);
	}

	/**
	 * Has replaced the entire backing list with `$headers`.
	 * @param THttpHeaderBase[] $headers
	 * @return void
	 */
	protected function setHeadersDirect(array $headers): void
	{
		$this->_headers = $headers;
	}

	/**
	 * Has appended `$header` to the list and set its manager to `$this`.
	 * Adding the same instance a second time is a no-op.
	 * @param THttpHeaderBase $header
	 * @throws TInvalidOperationException if the header already belongs to a different manager
	 * @return void
	 */
	public function addHeader(THttpHeaderBase $header): void
	{
		$existing = $header->getManager();
		if ($existing !== null && $existing !== $this) {
			throw new TInvalidOperationException('httpheadersmanager_header_wrong_manager');
		}
		$headers = $this->getHeaders();
		if (in_array($header, $headers, true)) {
			return;
		}
		if ($existing === null) {
			$header->setManager($this);
		}
		$this->addHeaderDirect($header);
	}

	/**
	 * Removes headers matching `$header` and returns `true` when at least one
	 * was found, or `false` when none matched.
	 *
	 * - **Instance** — removes that exact object and returns immediately.
	 * - **String (name)** — removes *all* headers whose name matches
	 *   case-insensitively. For replacing (singleton) headers there is at most
	 *   one entry; for non-replacing multi-value headers such as `Set-Cookie`
	 *   or `Link` ({@see THttpHeaderBase::getReplace()} returns `false`) there
	 *   may be several, and all are removed.
	 *
	 * @param string|THttpHeaderBase $header instance or header name string
	 * @return bool `true` if at least one header was removed, `false` if none matched
	 */
	public function removeHeader(THttpHeaderBase|string $header): bool
	{
		if ($header instanceof THttpHeaderBase) {
			foreach ($this->getHeaders() as $i => $h) {
				if ($h === $header) {
					$this->removeHeaderDirect($i);
					return true;
				}
			}
			return false;
		}

		$removed = false;
		foreach (array_reverse(array_keys($this->_headers)) as $i) {
			if (strcasecmp($this->_headers[$i]->getHeaderName(), $header) === 0) {
				$this->removeHeaderDirect($i);
				$removed = true;
			}
		}
		return $removed;
	}

	/**
	 * Returns `true` when a loaded header matches `$nameOrClass` by `instanceof`
	 * (class name) or case-insensitive header name string.
	 * @param string $nameOrClass fully-qualified class name or header name string
	 */
	public function hasHeader(string $nameOrClass): bool
	{
		foreach ($this->getHeaders() as $header) {
			if (is_a($header, $nameOrClass) || strcasecmp($header->getHeaderName(), $nameOrClass) === 0) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the first header whose name matches (case-insensitive), or `null`.
	 * Use {@see getHeadersByName()} for multi-value headers such as `Set-Cookie`.
	 * @param string $name header name (e.g. `'Strict-Transport-Security'`)
	 * @return ?THttpHeaderBase
	 */
	public function getHeaderByName(string $name): ?THttpHeaderBase
	{
		foreach ($this->getHeaders() as $header) {
			if (strcasecmp($header->getHeaderName(), $name) === 0) {
				return $header;
			}
		}
		return null;
	}

	/**
	 * Returns all headers whose name matches (case-insensitive).
	 *
	 * Use this method when a header name may legally appear more than once in
	 * the same response — for example `Set-Cookie`, `Link`, or `Cache-Control`.
	 * Returns an empty array when no header with that name is loaded.
	 *
	 * @param string $name header name (e.g. `'Set-Cookie'`)
	 * @return THttpHeaderBase[]
	 */
	public function getHeadersByName(string $name): array
	{
		return array_values(array_filter(
			$this->getHeaders(),
			fn ($h) => strcasecmp($h->getHeaderName(), $name) === 0
		));
	}

	/**
	 * Returns all loaded headers that are instances of the given class.
	 * @template T of THttpHeaderBase
	 * @param class-string<T> $class fully-qualified class name
	 * @return T[]
	 */
	public function getHeadersByClass(string $class): array
	{
		return array_values(array_filter($this->getHeaders(), fn ($h) => $h instanceof $class));
	}

	// =========================================================================
	// Reporter Service properties
	// =========================================================================

	/**
	 * Raw backing-field read. Prefer {@see getReportOnly()} from external callers.
	 * @return ?bool `true` for always report-only, `false` for always enforcing, or
	 *   `null` for Auto (resolves to `true` in {@see TApplicationMode::Debug} mode,
	 *   `false` in Normal and Performance). Use {@see resolveReportOnly()} to get the
	 *   effective boolean value for the current request.
	 */
	protected function getReportOnlyDirect(): ?bool
	{
		return $this->_reportOnly;
	}

	/**
	 * Has written the resolved report-only flag directly to the backing field.
	 * Pass `null` to restore Auto. For the public API with string normalization,
	 * use {@see setReportOnly()}.
	 * @param ?bool $value `true`, `false`, or `null` for Auto.
	 * @return void
	 */
	public function setReportOnlyDirect(?bool $value): void
	{
		$this->_reportOnly = $value;
	}

	/**
	 * Returns the stored report-only flag: `true` (always report-only), `false` (always
	 * enforcing), or `null` (Auto — resolves to `true` in {@see TApplicationMode::Debug},
	 * `false` otherwise). Call {@see resolveReportOnly()} for the effective runtime value.
	 * @return ?bool
	 */
	public function getReportOnly(): ?bool
	{
		return $this->getReportOnlyDirect();
	}

	/**
	 * Has set whether enforcing `Content-Security-Policy` headers are downgraded to
	 * `Content-Security-Policy-Report-Only` before sending.
	 *
	 * - **`true`** — always report-only; violations reported but not blocked.
	 * - **`false`** — always enforcing; violations block content.
	 * - **`null` / `'Auto'`** *(default)* — auto from application mode: `true` in
	 *   {@see TApplicationMode::Debug}, `false` in Normal and Performance.
	 *
	 * Orthogonal to {@see setReportingServiceMode() ReportingServiceMode}.
	 *
	 * @param null|bool|string $value `true`/`'true'`, `false`/`'false'`, or
	 *   `null`/`'Auto'` (case-insensitive).
	 * @return void
	 */
	public function setReportOnly(bool|string|null $value): void
	{
		if ($value === null || (is_string($value) && strtolower($value) === 'auto')) {
			$value = null;
		} else {
			$value = TPropertyValue::ensureBoolean($value);
		}
		$this->setReportOnlyDirect($value);
	}

	/**
	 * Returns the effective report-only flag for the current request, resolving the
	 * `null` Auto state from the application mode: `true` in {@see TApplicationMode::Debug},
	 * `false` otherwise. A stored `true` or `false` is returned as-is.
	 * @return bool
	 */
	protected function resolveReportOnly(): bool
	{
		$value = $this->getReportOnly();
		if ($value !== null) {
			return $value;
		}
		$app = $this->getApplication();
		return $app !== null && $app->getMode() === TApplicationMode::Debug;
	}

	/**
	 * @return 'Auto'|bool the reporting service mode; `'Auto'` by default.
	 */
	public function getReportingServiceMode(): bool|string
	{
		return $this->_reportingServiceMode;
	}

	/**
	 * Has set whether and how Prado manages the {@see TCspReportingService} lifecycle.
	 *
	 * - **`'Auto'`** *(default)* — wires the reporter only when evidence of reporting is
	 *   present. See {@see $_reportingServiceMode} for the full condition list.
	 * - **`true`** — always registers a service and injects `Reporting-Endpoints` /
	 *   `report-to` into every {@see THttpHeaderCsp} that lacks them.
	 * - **`false`** — hands off; no registration or header modification. Any
	 *   {@see THttpHeaderBase::REPORT_URI} sentinels are still resolved if a
	 *   {@see TCspReportingService} is already registered.
	 *
	 * Service identity is controlled by {@see setReportingServiceId() ReportingServiceId};
	 * report-only conversion by {@see setReportOnly() ReportOnly}.
	 *
	 * @param bool|string $value `false`, `true`, `'auto'`, or `'Auto'` (case-insensitive).
	 * @return void
	 */
	public function setReportingServiceMode(bool|string $value): void
	{
		if (is_bool($value)) {
			// Already a bool — store directly without redundant ensureBoolean call.
		} elseif (strtolower($value) === 'auto') {
			$value = 'Auto';
		} else {
			$value = TPropertyValue::ensureBoolean($value);
		}
		$this->setReportingServiceModeDirect($value);
	}

	/**
	 * Has written the already-normalized mode value directly to the backing field.
	 * @param bool|string $value `false`, `true`, or `'Auto'`.
	 * @return void
	 */
	protected function setReportingServiceModeDirect(bool|string $value): void
	{
		$this->_reportingServiceMode = $value;
	}

	/**
	 * @return string the service ID source of truth: `'Auto'` (auto-detect by
	 *   class, falling back to {@see TCspReportingService::SERVICE_ID}) or a literal
	 *   service ID.
	 */
	public function getReportingServiceId(): string
	{
		return $this->_reportingServiceId;
	}

	/**
	 * Has set the service ID used to locate or register the {@see TCspReportingService}.
	 *
	 * - **`'Auto'`** *(default)* — auto-detects by class at finalize time; falls back
	 *   to {@see TCspReportingService::SERVICE_ID} when registering.
	 * - **Any other string** — used directly for URL construction and auto-registration.
	 *
	 * @param string $value `'Auto'` (case-insensitive) or a literal service ID.
	 * @return void
	 */
	public function setReportingServiceId(string $value): void
	{
		$this->_reportingServiceId = (strtolower($value) === 'auto') ? 'Auto' : $value;
	}

	/**
	 * Returns the endpoint name token used in `Reporting-Endpoints` and the CSP `report-to` directive.
	 * @return string
	 */
	public function getReportingEndpointName(): string
	{
		return $this->_reportingEndpointName;
	}

	/**
	 * Has set the endpoint name token used in `Reporting-Endpoints` and the CSP `report-to`
	 * directive. Change when the default `'prado-csp-reporter'` conflicts with an existing name.
	 * @param string $value alphanumeric token, hyphens and underscores allowed.
	 * @return void
	 */
	public function setReportingEndpointName(string $value): void
	{
		$this->_reportingEndpointName = $value;
	}

	// =========================================================================
	// Configuration loading
	// =========================================================================

	/**
	 * Has converted `<headerclass>` and `<header>` XML children to
	 * `['headerclasses' => [...], 'headers' => [...]]`.
	 *
	 * Each `<header>` entry carries `'properties'` (non-`class` attributes, PascalCase
	 * keys preserved), optional `'class'`, and `'config'` (the raw {@see TXmlElement}
	 * forwarded to {@see THttpHeaderBase::init()}).
	 *
	 * @param TXmlElement $config raw XML element passed to {@see init()}.
	 * @return array PHP array ready for {@see loadHeaderClasses()} and {@see loadHeaders()}.
	 */
	protected function configToArray(TXmlElement $config): array
	{
		$headerClasses = [];
		foreach ($config->getElementsByTagName('headerclass') as $element) {
			/** @var TXmlElement $element */
			$headerClasses[] = array_change_key_case($element->getAttributes()->toArray(), CASE_LOWER);
		}

		$headers = [];
		foreach ($config->getElementsByTagName('header') as $element) {
			/** @var TXmlElement $element */
			$attrs = $element->getAttributes()->toArray();
			$class = null;
			// 'class' is infrastructure (not a header property); extract it
			// case-insensitively so `Class=` and `class=` both work in XML.
			foreach ($attrs as $k => $v) {
				if (strcasecmp($k, 'class') === 0) {
					$class = $v;
					unset($attrs[$k]);
					break;
				}
			}
			$entry = ['properties' => $attrs, 'config' => $element];
			if ($class !== null) {
				$entry['class'] = $class;
			}
			$headers[] = $entry;
		}

		return ['headerclasses' => $headerClasses, 'headers' => $headers];
	}

	/**
	 * Returns a normalized PHP array from `$config`: `TXmlElement` is converted via
	 * {@see configToArray()}; an `array` is returned as-is; anything else returns `[]`.
	 * @param mixed $config raw value received by {@see init()}.
	 * @return array
	 */
	protected function normalizeConfig(mixed $config): array
	{
		if ($config instanceof TXmlElement) {
			return $this->configToArray($config);
		}
		return is_array($config) ? $config : [];
	}

	/**
	 * Returns the default class used when no `class` attribute is present in a header
	 * config entry. Also the class {@see buildHeader()} tests against for name→class
	 * promotion via the {@see getNameClassMap() name→class registry}.
	 * @return string fully-qualified class name
	 */
	public function getDefaultHeaderClass(): string
	{
		return $this->_defaultHeaderClass;
	}

	/**
	 * Public alias of {@see getDefaultHeaderClass()} retained for XML-property
	 * compatibility (`DefaultMappingClass="…"`). Prefer {@see getDefaultHeaderClass()}
	 * in new code.
	 * @return string fully-qualified class name
	 */
	public function getDefaultMappingClass(): string
	{
		return $this->getDefaultHeaderClass();
	}

	/**
	 * Has set the default class used when no `class` attribute is specified in a
	 * header config entry. Must extend {@see THttpHeaderBase}.
	 * @param string $class fully-qualified class name
	 */
	public function setDefaultHeaderClass(string $class): void
	{
		$this->_defaultHeaderClass = $class;
	}

	/**
	 * Has registered header-name → class associations from the `'headerclasses'` config key.
	 * Runs before headers are loaded so the registry is ready for auto-promotion.
	 * Each entry must supply `'name'` and `'class'` keys.
	 * @param array $config normalized configuration array.
	 * @throws TConfigurationException if a `name` or `class` key is missing from an entry.
	 * @return void
	 */
	protected function loadHeaderClasses(array $config): void
	{
		foreach ($config['headerclasses'] ?? [] as $entry) {
			$name = $entry['name'] ?? null;
			$class = $entry['class'] ?? null;
			if ($name === null || $class === null) {
				throw new TConfigurationException('httpheadersmanager_headerclass_missing');
			}
			$this->registerHeaderClass($name, $class);
		}
	}

	/**
	 * Has instantiated and initialized each header from the `'headers'` config key.
	 * Each entry supports `'class'`, `'properties'`, and `'config'` keys;
	 * see {@see buildHeader()} for promotion and init details.
	 * @param array $config normalized configuration array.
	 * @throws TConfigurationException if a specified class does not extend {@see THttpHeaderBase}.
	 * @return void
	 */
	protected function loadHeaders(array $config): void
	{
		$defaultClass = $this->getDefaultHeaderClass();
		foreach ($config['headers'] ?? [] as $header) {
			$class = $header['class'] ?? $defaultClass;
			$properties = $header['properties'] ?? [];
			// 'config' carries the raw TXmlElement for XML-sourced headers so that
			// typed headers can extract child elements in their configToArray() override.
			// For PHP-array-sourced headers the whole entry array is used as init config.
			$headerConfig = array_key_exists('config', $header) ? $header['config'] : $header;
			$this->buildHeader($class, $properties, $headerConfig);
		}
	}

	/**
	 * Has created a header of `$class`, applied `$properties`, registered it via
	 * {@see addHeader()}, and called {@see THttpHeaderBase::init()} with `$config`.
	 *
	 * When `$class` is the {@see getDefaultHeaderClass() default class} and `$properties`
	 * supplies a `HeaderName`, the {@see getNameClassMap() name→class registry} is
	 * consulted and the header is promoted to its typed class automatically (e.g.
	 * `HeaderName="Content-Security-Policy"` → {@see THttpHeaderCsp}).
	 *
	 * @param string $class class name; must extend {@see THttpHeaderBase}.
	 * @param iterable $properties name-value pairs forwarded to
	 *   {@see THttpHeaderBase::setSubproperty()}.
	 * @param array|\Prado\Xml\TXmlElement $config raw config node forwarded to
	 *   {@see THttpHeaderBase::init()}.
	 * @throws TConfigurationException if `$class` does not extend {@see THttpHeaderBase}.
	 * @return void
	 */
	protected function buildHeader(string $class, $properties, $config): void
	{
		// Auto-promote default class to a typed class via the name→class map.
		// Keys in the map are normalised to lowercase at storage time, so a direct
		// O(1) lookup is both correct and case-insensitive.
		$promoted = false;
		if ($class === $this->getDefaultHeaderClass()) {
			$headerName = $properties['HeaderName'] ?? null;
			if ($headerName !== null) {
				$nameClassMap = $this->getNameClassMap();
				$mapped = $nameClassMap[strtolower($headerName)] ?? null;
				if ($mapped !== null) {
					$class = $mapped;
					$promoted = true;
				}
			}
		}

		$header = Prado::createComponent($class);
		if (!($header instanceof THttpHeaderBase)) {
			throw new TConfigurationException('httpheadersmanager_header_required');
		}
		foreach ($properties as $name => $value) {
			// Typed classes determine their own header name — skip HeaderName when promoted.
			if ($promoted && $name === 'HeaderName') {
				continue;
			}
			$header->setSubproperty($name, $value);
		}
		$this->addHeader($header);
		$header->init($config);
	}

	// =========================================================================
	// Sending
	// =========================================================================

	/**
	 * Returns whether this manager has already emitted headers.
	 * @return bool `true` after the first successful {@see sendHeaders()} call.
	 */
	public function getHeadersSent(): bool
	{
		return $this->_headersSent;
	}

	/**
	 * Has marked the manager's sent state. Subclasses and test doubles may call this
	 * directly (e.g. after intercepting {@see sendHeaders()}).
	 * @param bool $value `true` once headers have been emitted.
	 */
	protected function setHeadersSent(bool $value): void
	{
		$this->_headersSent = $value;
	}

	/**
	 * When `true`, {@see ensureHeadersSent()} is a no-op.
	 * Distinct from {@see getHeadersSent()}: this flag is set by external code
	 * (e.g. a reverse proxy or test double), not by the manager's own send pipeline.
	 * @return bool `true` when sending is suppressed by external code.
	 */
	public function getIsHandled(): bool
	{
		return $this->_isHandled;
	}

	/**
	 * Has set the handled flag. Pass `true` to suppress the send pipeline (e.g. a reverse
	 * proxy or test harness has already written the headers); `false` re-enables it.
	 * @param bool|string $value coerced via {@see TPropertyValue::ensureBoolean()}.
	 */
	public function setIsHandled(bool|string $value): void
	{
		$this->_isHandled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Has sent all headers exactly once; subsequent calls and calls when
	 * {@see setIsHandled() IsHandled} is `true` are no-ops.
	 * @return void
	 */
	public function ensureHeadersSent(): void
	{
		if (!$this->getIsHandled() && !$this->getHeadersSent()) {
			$this->sendHeaders();
		}
	}

	/**
	 * Has finalized, emitted, and marked all headers as sent.
	 * Override to change the send orchestration; override {@see finalizeHeaders()}
	 * to add pre-send manager-level logic instead.
	 * @return void
	 */
	protected function sendHeaders(): void
	{
		$this->finalizeHeaders();
		$this->doSendHeaders();
		$this->setHeadersSent(true);
	}

	/**
	 * Has run the three-phase finalization pipeline: {@see finalizeReporterService()},
	 * then per-header {@see THttpHeaderBase::finalizeHeader()}, then
	 * {@see validateHeaders()} for cross-header relationship checks.
	 * Override to add manager-level pre-send logic.
	 * @return void
	 */
	protected function finalizeHeaders(): void
	{
		$this->finalizeReporterService();
		foreach ($this->getHeaders() as $header) {
			$header->finalizeHeader();
		}
		$this->validateHeaders();
	}

	/**
	 * Has run all manager-level cross-header validation checks. Called at the end of
	 * {@see finalizeHeaders()} once every header is in its final state.
	 *
	 * Built-in checks: {@see validateCoepCoopPair()} and
	 * {@see validateFrameAncestorsXFrameOptions()}. Each is a separate protected method
	 * so subclasses can override individual rules without replacing the whole suite.
	 * @return void
	 */
	protected function validateHeaders(): void
	{
		$this->validateCoepCoopPair();
		$this->validateFrameAncestorsXFrameOptions();
	}

	/**
	 * Has logged a warning when exactly one of `Cross-Origin-Embedder-Policy` (COEP) or
	 * `Cross-Origin-Opener-Policy` (COOP) is present without the other.
	 * Both are required for cross-origin isolation (`SharedArrayBuffer`,
	 * `performance.measureUserAgentSpecificMemory()`, etc.).
	 * @return void
	 */
	protected function validateCoepCoopPair(): void
	{
		$hasCoep = $this->hasHeader(THttpHeaderName::CrossOriginEmbedderPolicy);
		$hasCoop = $this->hasHeader(THttpHeaderName::CrossOriginOpenerPolicy);
		if ($hasCoep && !$hasCoop) {
			Prado::log(
				'Cross-Origin-Embedder-Policy is set without Cross-Origin-Opener-Policy.'
				. ' Both headers are required together for cross-origin isolation'
				. ' (SharedArrayBuffer, performance.measureUserAgentSpecificMemory(), etc.).',
				\Prado\Util\TLogger::WARNING,
				static::class
			);
		} elseif ($hasCoop && !$hasCoep) {
			Prado::log(
				'Cross-Origin-Opener-Policy is set without Cross-Origin-Embedder-Policy.'
				. ' Both headers are required together for cross-origin isolation'
				. ' (SharedArrayBuffer, performance.measureUserAgentSpecificMemory(), etc.).',
				\Prado\Util\TLogger::WARNING,
				static::class
			);
		}
	}

	/**
	 * Has logged a debug notice when a CSP header declares `frame-ancestors` but no
	 * `X-Frame-Options` header is present. Setting both provides the widest browser
	 * coverage — CSP-aware browsers honor `frame-ancestors`; older ones use `X-Frame-Options`.
	 * @return void
	 */
	protected function validateFrameAncestorsXFrameOptions(): void
	{
		if ($this->hasHeader(THttpHeaderName::XFrameOptions)) {
			return;
		}
		foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
			/** @var THttpHeaderCsp $csp */
			if ($csp->hasPolicy(TCspDirective::FrameAncestors)) {
				Prado::log(
					'A CSP header declares frame-ancestors but no X-Frame-Options header is present.'
					. ' Consider adding X-Frame-Options for older browser coverage.',
					\Prado\Util\TLogger::DEBUG,
					static::class
				);
				return;
			}
		}
	}

	/**
	 * Has emitted each header via {@see THttpHeaderBase::sendHeader()}.
	 * Override to wrap or instrument the emission loop.
	 * @return void
	 */
	protected function doSendHeaders(): void
	{
		$response = $this->getResponse();
		foreach ($this->getHeaders() as $header) {
			$header->sendHeader($response);
		}
	}

	/**
	 * Has wired the {@see TCspReportingService} into CSP headers for this request.
	 * Called first in {@see finalizeHeaders()}.
	 *
	 * {@see ensureReportingServiceRegistered()} will have resolved
	 * {@see getReportingServiceId() ReportingServiceId} from `'Auto'` to a concrete ID
	 * before this runs; the `'Auto'` path below is a safety net for bypassed environments.
	 *
	 * When mode is not `false`, has constructed the reporter URL via the URL manager
	 * (honoring any {@see \Prado\Web\TUrlMappingPattern}), ensured a
	 * {@see THttpHeaderReportingEndpoints} header declares the endpoint, injected
	 * `report-to` into every {@see THttpHeaderCsp} that lacks one, and — when
	 * {@see getReportOnly() ReportOnly} resolves `true` — downgraded enforcing CSP
	 * headers to `Content-Security-Policy-Report-Only`.
	 *
	 * When mode is `false`, has resolved any {@see THttpHeaderBase::REPORT_URI}
	 * sentinels in CSP and `Reporting-Endpoints` headers if a {@see TCspReportingService}
	 * is already registered, without triggering service registration or header injection.
	 */
	protected function finalizeReporterService(): void
	{
		$serviceMode = $this->getReportingServiceMode();
		$app = $this->getApplication();

		if (!$app) {
			return;
		}

		if ($serviceMode === false) {
			// Auto-wiring is disabled, but REPORT_URI sentinels in manually-configured
			// headers must still be replaced when a TCspReportingService is already
			// present in the application registry.
			$existingId = $app->getRegisteredServiceByClass(TCspReportingService::class);
			if ($existingId !== null) {
				$url = $this->buildReporterUrl($app, $existingId);
				foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
					if ($csp->hasReportUriPlaceholder()) {
						$csp->setPolicy(TCspDirective::ReportUri, $url);
					}
				}
				foreach ($this->getHeadersByClass(THttpHeaderReportingEndpoints::class) as $re) {
					foreach ($re->getEndpointNames() as $epName) {
						if ($re->getEndpointUrl($epName) === THttpHeaderReportingEndpoints::REPORT_URI) {
							$re->addEndpoint($epName, $url);
						}
					}
				}
			}
			return;
		}

		// ensureReportingServiceRegistered() resolves 'Auto' to a concrete ID before
		// headers are sent, so this is normally a direct read. The 'Auto' fallback
		// below is a safety net for environments where that hook was bypassed.
		$endpointName = $this->getReportingEndpointName();
		$serviceId = $this->getReportingServiceId();
		if ($serviceId === 'Auto') {
			if ($serviceMode === 'Auto') {
				// Mirror ensureReportingServiceRegistered()'s trigger logic: proceed when
				// report-only is active, OR (condition 3) when any CSP already references
				// this manager's own endpoint name. A report-to pointing at a different
				// endpoint — or a misplaced URL — must not trigger wiring.
				$inUse = $this->resolveReportOnly();
				if (!$inUse) {
					foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
						if (in_array($endpointName, $csp->getReportToNames(), true)
							|| $csp->hasReportUriPlaceholder()) {
							$inUse = true;
							break;
						}
					}
				}
				if (!$inUse) {
					foreach ($this->getHeadersByClass(THttpHeaderReportingEndpoints::class) as $re) {
						if ($re->hasReportUriPlaceholder()) {
							$inUse = true;
							break;
						}
					}
				}
				if (!$inUse) {
					return; // No condition met — skip wiring entirely.
				}
			}
			// Safety net (mode=true but hook bypassed) or condition 3/reportOnly met: resolve now.
			$serviceId = $app->getRegisteredServiceByClass(TCspReportingService::class)
				?? TCspReportingService::SERVICE_ID;
			if (!$app->hasRegisteredService($serviceId)) {
				$app->registerService($serviceId, TCspReportingService::class, ['AutoRegistered' => true]);
			}
			$this->setReportingServiceId($serviceId);
		}

		// Construct the reporter URL via the URL manager so any TUrlMappingPattern
		// defined for this service is honoured.
		$url = $this->buildReporterUrl($app, $serviceId);

		Prado::log(
			'Prado CSP Reporter Service is active at: ' . $url,
			\Prado\Util\TLogger::INFO,
			static::class
		);

		// Ensure a Reporting-Endpoints header exists and declares our endpoint.
		// Multiple Reporting-Endpoints headers may be configured; check all of them
		// before deciding whether the endpoint is already declared.
		$reHeaders = $this->getHeadersByClass(THttpHeaderReportingEndpoints::class);
		if (empty($reHeaders)) {
			$re = new THttpHeaderReportingEndpoints();
			$this->addHeader($re);
			$re->init([]);
			$reHeaders = [$re];
		}
		$endpointDeclared = false;
		foreach ($reHeaders as $re) {
			if ($re->hasEndpoint($endpointName)) {
				$endpointDeclared = true;
				break;
			}
		}
		if (!$endpointDeclared) {
			$reHeaders[0]->addEndpoint($endpointName, $url);
		}

		// Convert enforcing CSP headers to report-only when requested.
		if ($this->resolveReportOnly()) {
			foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
				if (!$csp->getReportOnly()) {
					$csp->setReportOnly(true);
					Prado::log(
						'CSP ReportOnly mode: enforcing CSP converted to report-only.',
						\Prado\Util\TLogger::INFO,
						static::class
					);
				}
			}
		}

		// Inject report-to into all CSP headers that do not already have one.
		foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
			if (!$csp->hasPolicy(TCspDirective::ReportTo)) {
				$csp->addPolicy(TCspDirective::ReportTo, $endpointName);
			}
		}

		// Fill in report-uri placeholders (sentinel or blank) with the reporter URL.
		foreach ($this->getHeadersByClass(THttpHeaderCsp::class) as $csp) {
			if ($csp->hasReportUriPlaceholder()) {
				$csp->setPolicy(TCspDirective::ReportUri, $url);
			}
		}

		// Fill in REPORT_URI sentinel URLs in Reporting-Endpoints headers.
		foreach ($this->getHeadersByClass(THttpHeaderReportingEndpoints::class) as $re) {
			foreach ($re->getEndpointNames() as $epName) {
				if ($re->getEndpointUrl($epName) === THttpHeaderReportingEndpoints::REPORT_URI) {
					$re->addEndpoint($epName, $url);
				}
			}
		}
	}

	/**
	 * Returns the reporter service URL for the `Reporting-Endpoints` header.
	 * Delegates to {@see \Prado\Web\THttpRequest::constructUrl()} so any
	 * {@see \Prado\Web\TUrlMappingPattern} for the service is honored.
	 * Protected so test doubles can return a fixed URL without a live HTTP request.
	 *
	 * @param TApplication $app       running application instance.
	 * @param string       $serviceId service ID to construct a URL for.
	 * @return string absolute URL for the reporter service endpoint.
	 */
	protected function buildReporterUrl(TApplication $app, string $serviceId): string
	{
		return $app->getRequest()->constructUrl($serviceId, '', [], false);
	}
}
