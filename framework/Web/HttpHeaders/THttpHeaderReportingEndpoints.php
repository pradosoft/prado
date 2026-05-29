<?php

/**
 * THttpHeaderReportingEndpoints class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Web\THttpHeaderName;
use Prado\Xml\TXmlElement;

/**
 * THttpHeaderReportingEndpoints class
 *
 * THttpHeaderReportingEndpoints emits a `Reporting-Endpoints` header that
 * declares named HTTPS endpoints to which the browser sends violation and
 * error reports. Each endpoint name must match exactly the token used in the
 * {@see TCspDirective::ReportTo} directive of a {@see THttpHeaderCsp} header.
 *
 * {@see THttpHeaderCsp} validates that every `report-to` directive value
 * references a name declared here, logging a warning for any unresolved
 * reference just before headers are sent (in {@see THttpHeaderCsp::finalizeHeader()}).
 *
 * ```xml
 * <module id="httpHeaders" class="THttpHeadersManager">
 *   <header class="THttpHeaderReportingEndpoints">
 *     <endpoint Name="csp-endpoint" Url="https://example.com/csp-reports" />
 *     <endpoint Name="default"      Url="https://example.com/reports" />
 *   </header>
 *   <header class="THttpHeaderCsp">
 *     <policy Name="default-src">'self'</policy>
 *     <policy Name="report-to">csp-endpoint</policy>
 *   </header>
 * </module>
 * ```
 *
 * Or in PHP:
 * ```php
 * [
 *     'class'     => THttpHeaderReportingEndpoints::class,
 *     'endpoints' => [
 *         ['name' => 'csp-endpoint', 'url' => 'https://example.com/csp-reports'],
 *         ['name' => 'default',      'url' => 'https://example.com/reports'],
 *     ],
 * ]
 * ```
 *
 * **Compatibility.** `Reporting-Endpoints` is the modern replacement for the
 * legacy `Report-To` header. For older browser compatibility, send both
 * alongside a `report-uri` CSP directive:
 * ```
 * Reporting-Endpoints: csp-endpoint="https://example.com/csp-reports"
 * Report-To: {"group":"csp-endpoint","max_age":86400,"endpoints":[{"url":"https://example.com/csp-reports"}]}
 * Content-Security-Policy: ...; report-uri https://example.com/csp-reports; report-to csp-endpoint
 * ```
 * Browsers that support `report-to` ignore `report-uri` when both are present.
 *
 * **Endpoint URLs must be HTTPS.** All HTTP endpoints are rejected by browsers.
 *
 * **Replace behaviour.** `Reporting-Endpoints` is a singleton header; only one
 * instance is sent per response. {@see getReplace()} returns `true` so any
 * earlier value for this header is replaced when headers are flushed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see THttpHeaderName::ReportingEndpoints
 * @see THttpHeaderName::ReportTo for the legacy header.
 * @see TCspDirective::ReportTo for the CSP directive that references endpoint names.
 * @see TCspDirective::ReportUri for the deprecated CSP fallback directive.
 */
class THttpHeaderReportingEndpoints extends THttpHeaderBase
{
	/**
	 * @var array<string, string> endpoint name => URL map.
	 */
	private array $_endpoints = [];

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Loads endpoint definitions from the configuration node.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this header.
	 */
	public function init($config): void
	{
		parent::init($config);
		$this->loadEndpoints($this->normalizeConfig($config));
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string `'Reporting-Endpoints'`.
	 */
	public function getHeaderName(): string
	{
		return THttpHeaderName::ReportingEndpoints;
	}

	/**
	 * @param string $name endpoint name to check; trimmed before lookup.
	 * @return bool `true` when the name is registered.
	 */
	public function hasEndpoint(string $name): bool
	{
		return array_key_exists(trim($name), $this->_endpoints);
	}

	/**
	 * Adds or replaces a named endpoint. Both `$name` and `$url` are trimmed before
	 * storage so that lookups and header value output are always consistent.
	 * A blank `$url` is normalized to the {@see REPORT_URI} sentinel so that
	 * {@see THttpHeadersManager::finalizeReporterService()} can inject the live URL.
	 * @param string $name endpoint name — must match exactly the token used in
	 *   the {@see TCspDirective::ReportTo} directive of a {@see THttpHeaderCsp} header.
	 * @param string $url  HTTPS URL to which the browser sends reports, or `''` to
	 *   request auto-injection via the {@see REPORT_URI} sentinel.
	 */
	public function addEndpoint(string $name, string $url): void
	{
		$url = trim($url);
		$this->_endpoints[trim($name)] = ($url === '') ? self::REPORT_URI : $url;
	}

	/**
	 * Returns the stored URL for the named endpoint, or `null` when the name is absent.
	 * The returned value may be the {@see REPORT_URI} sentinel when the endpoint was
	 * configured with a blank URL and the live URL has not yet been injected.
	 * @param string $name endpoint name; trimmed before lookup.
	 * @return ?string the stored URL or `null`.
	 */
	public function getEndpointUrl(string $name): ?string
	{
		return $this->_endpoints[trim($name)] ?? null;
	}

	/**
	 * Returns `true` when at least one endpoint URL holds the {@see REPORT_URI}
	 * sentinel, indicating that {@see THttpHeadersManager::finalizeReporterService()}
	 * should replace it with the live reporter URL at send time.
	 */
	public function hasReportUriPlaceholder(): bool
	{
		return in_array(self::REPORT_URI, $this->_endpoints, true);
	}

	/**
	 * Removes a named endpoint. Has no effect when the name is not registered.
	 * The name is trimmed before lookup for consistency with {@see addEndpoint()}.
	 * @param string $name endpoint name to remove.
	 * @return bool `true` when the endpoint was present and removed, `false` otherwise.
	 */
	public function removeEndpoint(string $name): bool
	{
		$name = trim($name);
		if (!array_key_exists($name, $this->_endpoints)) {
			return false;
		}
		unset($this->_endpoints[$name]);
		return true;
	}

	/**
	 * @return string[] names of all declared endpoints.
	 */
	public function getEndpointNames(): array
	{
		return array_keys($this->_endpoints);
	}

	/**
	 * @return string e.g. `csp-endpoint="https://example.com/csp-reports", default="https://example.com/reports"`
	 */
	public function getHeaderValue(): string
	{
		$parts = [];
		foreach ($this->_endpoints as $name => $url) {
			$parts[] = $name . '="' . $url . '"';
		}
		return implode(', ', $parts);
	}

	/**
	 * Parses a structured field string of `name="url"` pairs (comma-separated;
	 * entries not matching the pattern are silently skipped). Called automatically
	 * by the manager when a plain `HeaderName="Reporting-Endpoints"` config node
	 * is promoted via the name→class registry.
	 * @param mixed $value e.g. `'csp-endpoint="https://example.com/csp-reports", default="https://example.com/reports"'`
	 */
	public function setHeaderValue($value): void
	{
		$value = (string) $value;
		$entries = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
		if ($entries === false) {
			return;
		}
		foreach ($entries as $entry) {
			if (preg_match('/^([A-Za-z0-9_-]+)\s*=\s*"([^"]*)"$/', trim($entry), $m)) {
				$this->addEndpoint($m[1], $m[2]);
			}
		}
	}

	// =========================================================================
	// Protected helpers
	// =========================================================================

	/**
	 * Converts `<endpoint Name="…" Url="…" />` XML child elements to the PHP
	 * array shape `['endpoints' => [['name' => '…', 'url' => '…'], …]]`.
	 *
	 * Uses {@see TXmlElement::getElementsAttrArrayByTagName()} to collect each
	 * element's attribute map, then lowercases every key so the result matches
	 * the PHP config format.
	 *
	 * @param TXmlElement $config the raw XML element passed to {@see init()}.
	 * @return array PHP representation of the endpoint child elements.
	 */
	protected function configToArray(TXmlElement $config): array
	{
		return ['endpoints' => array_map(
			fn (array $attrs) => array_change_key_case($attrs, CASE_LOWER),
			$config->getElementsAttrArrayByTagName('endpoint')
		)];
	}

	/**
	 * Loads endpoint definitions from a normalised PHP array configuration.
	 * XML configuration is converted to this format before this method is called
	 * (see {@see configToArray()} and {@see normalizeConfig()}).
	 * Entries with a missing or blank `name` key are silently skipped — an
	 * empty-string endpoint name would produce a malformed header value.
	 * @param array $config normalised configuration array.
	 */
	protected function loadEndpoints(array $config): void
	{
		foreach ($config['endpoints'] ?? [] as $endpoint) {
			$name = $endpoint['name'] ?? '';
			if (trim($name) !== '') {
				$this->addEndpoint($name, $endpoint['url'] ?? '');
			}
		}
	}
}
