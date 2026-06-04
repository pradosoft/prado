<?php

/**
 * THttpHeaderCsp class
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\THttpHeaderName;
use Prado\Xml\TXmlElement;

/**
 * THttpHeaderCsp class
 *
 * THttpHeaderCsp emits a `Content-Security-Policy` header. Set
 * {@see setReportOnly() ReportOnly} to `true` to emit
 * `Content-Security-Policy-Report-Only` instead — the same directives apply
 * but violations are reported without blocking resources.
 *
 * Configure via {@see \Prado\Web\HttpHeaders\THttpHeadersManager THttpHeadersManager}:
 *
 * ```xml
 * <module id="headers" class="THttpHeadersManager">
 *   <header class="THttpHeaderCsp">
 *      <policy Name="default-src">'self' www.gstatic.com NONCE</policy>
 *      <policy Name="frame-src">'self' www.google.com</policy>
 *   </header>
 * </module>
 * ```
 *
 * Or in PHP:
 * ```php
 * [
 *     'class'      => THttpHeaderCsp::class,
 *     'properties' => ['ReportOnly' => true],
 *     'policies'   => [
 *         ['name' => TCspDirective::DefaultSrc, 'value' => "'self' NONCE"],
 *     ],
 * ]
 * ```
 *
 * The manager also accepts a plain `HeaderName="Content-Security-Policy"` node
 * and calls {@see setHeaderValue()} to parse the directives automatically:
 * ```php
 * ['properties' => [
 *     'HeaderName'  => THttpHeaderName::ContentSecurityPolicy,
 *     'HeaderValue' => "default-src 'self'; script-src 'self' 'nonce-abc'",
 * ]]
 * ```
 *
 * The placeholder {@see NONCE} in any policy value is replaced at render time
 * with the per-request nonce from
 * {@see \Prado\Web\Javascripts\TJavaScript::getScriptNonce()}.
 *
 * The placeholder {@see REPORT_URI} as a `report-uri` directive value (or a blank
 * value, which {@see setPolicy()} normalizes to the sentinel) is replaced at send time
 * with the live reporter URL when {@see THttpHeadersManager} wires up the CSP
 * reporting service.
 *
 * **Note:** {@see TCspDirective::Sandbox} is silently ignored by browsers in
 * report-only mode. When `ReportOnly` is `true` and `sandbox` is present,
 * {@see finalizeHeader()} logs a warning and omits the directive from the emitted value.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/CSP
 * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy
 */
class THttpHeaderCsp extends TBaseHttpHeader
{
	/**
	 * @var array<string,string>|string directive name => value map, or a raw
	 *   unparseable value string as a fallback.
	 */
	protected $_policies = [];

	/**
	 * @var bool whether to emit the report-only variant of the header.
	 */
	private $_reportOnly = false;

	/**
	 * Placeholder replaced at render time with the `'nonce-<value>'` expression
	 * from {@see \Prado\Web\Javascripts\TJavaScript::getScriptNonce()}.
	 */
	public const NONCE = 'NONCE';

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Loads policies from `$config` and seeds the per-request nonce via
	 * {@see TJavaScript::setScriptNonce()} when any policy value references {@see NONCE}.
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this header.
	 */
	public function init($config): void
	{
		parent::init($config);
		$this->loadPolicies($this->normalizeConfig($config));

		$policies = $this->getPolicies();
		if (is_array($policies)) {
			foreach ($policies as $value) {
				if (str_contains($value, self::NONCE)) {
					$nonce = Prado::getApplication()?->getSecurityManager()?->getCSPNonce();
					if ($nonce !== null) {
						TJavaScript::setScriptNonce($nonce);
					}
					break;
				}
			}
		}
	}

	/**
	 * Strips `sandbox` (silently ignored by browsers in report-only mode) and warns
	 * for each `report-to` endpoint name not declared in a sibling
	 * {@see THttpHeaderReportingEndpoints} header.
	 */
	public function finalizeHeader(): void
	{
		// Sandbox is silently ignored by browsers in report-only mode — strip it.
		if ($this->getReportOnly() && $this->hasPolicy(TCspDirective::Sandbox)) {
			Prado::log(
				'The CSP "sandbox" directive is silently ignored by browsers in'
				. ' report-only mode and has been omitted from the header value.',
				\Prado\Util\TLogger::WARNING,
				static::class
			);
			$this->removePolicy(TCspDirective::Sandbox);
		}

		// Validate report-to endpoint names against sibling Reporting-Endpoints headers.
		$reportToNames = $this->getReportToNames();
		if (empty($reportToNames) || ($manager = $this->getManager()) === null) {
			return;
		}

		$declaredNames = [];
		foreach ($manager->getHeaders() as $header) {
			if ($header instanceof THttpHeaderReportingEndpoints) {
				foreach ($header->getEndpointNames() as $name) {
					$declaredNames[$name] = true;
				}
			}
		}
		foreach ($reportToNames as $name) {
			if (!isset($declaredNames[$name])) {
				Prado::log(
					'CSP report-to references endpoint "' . $name . '" which is not'
					. ' declared in any Reporting-Endpoints header.',
					\Prado\Util\TLogger::WARNING,
					static::class
				);
			}
		}
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string `'Content-Security-Policy'` or `'Content-Security-Policy-Report-Only'`.
	 */
	public function getHeaderName(): string
	{
		return $this->getReportOnly()
			? THttpHeaderName::ContentSecurityPolicyReportOnly
			: THttpHeaderName::ContentSecurityPolicy;
	}

	/**
	 * @return bool `true` when the header is sent in report-only mode.
	 */
	public function getReportOnly(): bool
	{
		return $this->_reportOnly;
	}

	/**
	 * When `true`, emits `Content-Security-Policy-Report-Only` instead of
	 * `Content-Security-Policy` — violations are reported but resources are not blocked.
	 * @param bool|string $value coerced via {@see TPropertyValue::ensureBoolean()}.
	 */
	public function setReportOnly($value): void
	{
		$this->_reportOnly = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Returns the raw `_policies` backing field without processing.
	 * @return array<string,string>|string the directive map or raw fallback string.
	 */
	protected function getPoliciesDirect(): array|string
	{
		return $this->_policies;
	}

	/**
	 * Replaces the entire `_policies` backing field without validation.
	 * @param array<string,string>|string $policies directive map or raw fallback string.
	 */
	protected function setPoliciesDirect(array|string $policies): void
	{
		$this->_policies = $policies;
	}

	/**
	 * Returns `true` when policies are a structured directive map (array); `false`
	 * when stored as a raw unparseable string by {@see setHeaderValue()}.
	 * When `false`, {@see setPolicy()}, {@see getPolicy()}, {@see hasPolicy()},
	 * and {@see removePolicy()} are silent no-ops.
	 * @since 4.4.0
	 */
	public function isPoliciesStructured(): bool
	{
		return is_array($this->_policies);
	}

	/**
	 * Returns `true` when the backing field is a structured map with at least one directive.
	 */
	protected function hasPoliciesDirect(): bool
	{
		return $this->isPoliciesStructured() && $this->_policies !== [];
	}

	/**
	 * Returns `true` when the named directive is present in the structured map;
	 * names are lowercased before lookup per the CSP3 case-insensitivity rule.
	 * @param string $name directive name (case-insensitive)
	 */
	protected function hasPolicyDirect(string $name): bool
	{
		return $this->isPoliciesStructured() && array_key_exists(strtolower($name), $this->_policies);
	}

	/**
	 * Returns the value of a single directive, or `null` when absent or policies are a
	 * raw string; names are lowercased before lookup per the CSP3 case-insensitivity rule.
	 * @param string $name directive name (case-insensitive), e.g. {@see TCspDirective::ReportTo}
	 * @return ?string directive value (`''` for bare directives), or `null` if absent.
	 */
	protected function getPolicyDirect(string $name): ?string
	{
		return $this->isPoliciesStructured() ? ($this->_policies[strtolower($name)] ?? null) : null;
	}

	/**
	 * Sets a single directive in the backing field; no-op when policies are a raw string.
	 * @param string $name  directive name (should already be lowercase)
	 * @param string $value directive value; `''` for bare directives.
	 */
	protected function setPolicyDirect(string $name, string $value): void
	{
		if ($this->isPoliciesStructured()) {
			$this->_policies[$name] = $value;
		}
	}

	/**
	 * Removes a single directive from the backing field; name is lowercased before unset.
	 * @param string $name directive name (case-insensitive)
	 */
	protected function removePolicyDirect(string $name): void
	{
		unset($this->_policies[strtolower($name)]);
	}

	// -------------------------------------------------------------------------
	// Collection accessors
	// -------------------------------------------------------------------------

	/**
	 * @return bool `true` when at least one directive is present in the parsed map;
	 *   `false` for an empty map or a raw string.
	 */
	public function hasPolicies(): bool
	{
		return $this->hasPoliciesDirect();
	}

	/**
	 * @return array<string,string>|string directive map, or a raw unparseable string.
	 */
	public function getPolicies(): array|string
	{
		return $this->getPoliciesDirect();
	}

	/**
	 * Replaces the entire directive map via {@see setPolicy()} per entry, or stores a raw
	 * string as-is (unparseable fallback).
	 * @param array<string,string>|string $policies directive map or raw string.
	 */
	public function setPolicies(array|string $policies): void
	{
		if (is_array($policies)) {
			$this->setPoliciesDirect([]);
			foreach ($policies as $name => $value) {
				$this->setPolicy((string) $name, (string) $value);
			}
		} else {
			$this->setPoliciesDirect($policies);
		}
	}

	// -------------------------------------------------------------------------
	// Single-entry accessors
	// -------------------------------------------------------------------------

	/**
	 * @param string $name directive name (case-insensitive, trimmed); e.g. {@see TCspDirective::ReportTo}
	 * @return bool `true` when the directive is present in the structured map;
	 *   always `false` when policies are stored as a raw string.
	 */
	public function hasPolicy(string $name): bool
	{
		return $this->hasPolicyDirect(trim($name));
	}

	/**
	 * Returns the value of a single directive, or `null` when the directive is
	 * absent or policies are stored as a raw string.
	 * @param string $name directive name (case-insensitive, trimmed); e.g. {@see TCspDirective::ReportTo}
	 * @return ?string the directive value (`''` for bare directives), or `null` if absent.
	 */
	public function getPolicy(string $name): ?string
	{
		return $this->getPolicyDirect(trim($name));
	}

	/**
	 * Sets or replaces a single directive. No-op when policies are a raw string.
	 * `$name` is trimmed and lowercased per the CSP3 case-insensitivity rule;
	 * `$value` is trimmed so stored values match what {@see getHeaderValue()} emits.
	 * A blank `$value` for {@see TCspDirective::ReportUri} is normalized to the
	 * {@see REPORT_URI} sentinel so the stored state is always canonical.
	 * @param string $name  directive name (case-insensitive), e.g. {@see TCspDirective::ReportTo}
	 * @param string $value directive value; defaults to `''` for bare directives like `upgrade-insecure-requests`.
	 */
	public function setPolicy(string $name, string $value = ''): void
	{
		$name = strtolower(trim($name));
		$value = trim($value);
		if ($name === TCspDirective::ReportUri && $value === '') {
			$value = self::REPORT_URI;
		}
		$this->setPolicyDirect($name, $value);
	}

	/**
	 * Alias of {@see setPolicy()}. Retained for readability at call sites that
	 * emphasise adding a new directive rather than replacing an existing one.
	 * @param string $name  directive name, e.g. {@see TCspDirective::ReportTo}
	 * @param string $value directive value; defaults to `''` for bare directives.
	 */
	public function addPolicy(string $name, string $value = ''): void
	{
		$this->setPolicy($name, $value);
	}

	/**
	 * Removes a directive by name. Returns `true` when the directive was present
	 * and removed, `false` when it was already absent or policies are a raw string.
	 * The name is trimmed and lowercased for consistent lookup.
	 * @param string $name directive name (case-insensitive), e.g. {@see TCspDirective::Sandbox}
	 * @return bool whether the directive was found and removed.
	 */
	public function removePolicy(string $name): bool
	{
		$name = strtolower(trim($name));
		if (!$this->hasPolicy($name)) {
			return false;
		}
		$this->removePolicyDirect($name);
		return true;
	}

	// -------------------------------------------------------------------------
	// Derived / computed
	// -------------------------------------------------------------------------

	/**
	 * Returns the `report-to` endpoint group name as a one-element array,
	 * or `[]` when absent or empty.
	 * @return string[]
	 */
	public function getReportToNames(): array
	{
		$name = $this->getPolicy(TCspDirective::ReportTo);
		// Values are trimmed by setPolicy() at store time; no re-trim needed here.
		return ($name !== null && $name !== '') ? [$name] : [];
	}

	/**
	 * Returns `true` when the `report-uri` directive holds the {@see REPORT_URI}
	 * sentinel, indicating that {@see THttpHeadersManager::finalizeReporterService()}
	 * should replace it with the live reporter URL at send time.
	 */
	public function hasReportUriPlaceholder(): bool
	{
		return $this->getPolicyDirect(TCspDirective::ReportUri) === self::REPORT_URI;
	}

	// -------------------------------------------------------------------------
	// Header value
	// -------------------------------------------------------------------------

	/**
	 * Builds the CSP value by joining all directives with `'; '`, replacing {@see NONCE}
	 * placeholders with the current nonce; returns the raw string when unparseable.
	 * @return string the header value.
	 */
	public function getHeaderValue(): string
	{
		if (!$this->isPoliciesStructured()) {
			return (string) $this->getPoliciesDirect();
		}
		$nonce = TJavaScript::getScriptNonce();
		$nonceDirective = $nonce !== null ? '\'nonce-' . $nonce . '\'' : null;
		$parts = [];
		foreach ($this->getPoliciesDirect() as $name => $value) {
			if ($nonceDirective !== null) {
				$value = str_replace(self::NONCE, $nonceDirective, $value);
			}
			$parts[] = trim($value) !== '' ? $name . ' ' . $value : $name;
		}
		return implode('; ', $parts);
	}

	/**
	 * Parses a raw CSP directive string into the policy map via {@see setPolicy()},
	 * which lowercases names and normalizes a blank `report-uri` to {@see REPORT_URI};
	 * stores unparseable input as-is via {@see setPoliciesDirect()}.
	 * @param mixed $value e.g. `"default-src 'self'; script-src 'self' 'nonce-abc'"`
	 */
	public function setHeaderValue($value): void
	{
		$value = TPropertyValue::ensureString($value);
		$directives = preg_split('/\s*;\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
		if ($directives === false || empty($directives)) {
			$this->setPoliciesDirect($value);
			return;
		}
		$policies = [];
		foreach ($directives as $directive) {
			$directive = trim($directive);
			if ($directive === '') {
				continue;
			}
			// Directive name is one or more alphanumeric/hyphen/underscore chars.
			// Everything after the first whitespace run is the value.
			if (preg_match('/^([a-zA-Z0-9_-]+)(?:\s+(.+))?$/', $directive, $m)) {
				$policies[$m[1]] = $m[2] ?? '';
			} else {
				// Unparseable segment — store raw and stop.
				$this->setPoliciesDirect($value);
				return;
			}
		}
		// Route each parsed directive through setPolicy() so that name lowercasing
		// and report-uri normalization are applied consistently.
		$this->setPoliciesDirect([]);
		foreach ($policies as $name => $pvalue) {
			$this->setPolicy($name, $pvalue);
		}
	}

	// =========================================================================
	// Protected helpers
	// =========================================================================

	/**
	 * Converts `<policy Name="…">value</policy>` XML child elements to
	 * `['policies' => [['name' => '…', 'value' => '…'], …]]`.
	 * @param TXmlElement $config the raw XML element passed to {@see init()}.
	 * @return array PHP representation of the policy child elements.
	 */
	protected function configToArray(TXmlElement $config): array
	{
		$policies = [];
		foreach ($config->getElementsByTagName('policy') as $element) {
			$policies[] = array_change_key_case($element->getAttributes()->toArray(), CASE_LOWER)
				+ ['value' => $element->getValue()];
		}
		return ['policies' => $policies];
	}

	/**
	 * Loads CSP directives from a normalised PHP array; entries with a missing or
	 * blank `name` key are silently skipped.
	 * @param array $config normalised configuration array.
	 */
	protected function loadPolicies(array $config): void
	{
		foreach ($config['policies'] ?? [] as $policy) {
			$name = $policy['name'] ?? '';
			if (trim($name) !== '') {
				$this->addPolicy($name, $policy['value'] ?? '');
			}
		}
	}
}
