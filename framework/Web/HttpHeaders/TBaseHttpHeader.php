<?php

/**
 * TBaseHttpHeader class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Web\THttpHeaderName;
use Prado\Xml\TXmlElement;

/**
 * TBaseHttpHeader class
 *
 * TBaseHttpHeader is the abstract base for all typed HTTP header classes in the
 * HttpHeaders family. It owns the manager reference, the send/replace logic, and
 * the three lifecycle hooks ({@see init()}, {@see initComplete()},
 * {@see finalizeHeader()}) that subclasses override to build their header value.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseHttpHeader extends \Prado\TComponent
{
	/**
	 * Placeholder replaced at send time with the reporter URL from
	 * {@see THttpHeadersManager::finalizeReporterService()}.
	 * Used by {@see THttpHeaderCsp} (blank `report-uri` directive value normalized
	 * to this sentinel by {@see THttpHeaderCsp::setPolicy()}) and by
	 * {@see THttpHeaderReportingEndpoints} (blank endpoint URL normalized to this
	 * sentinel by {@see THttpHeaderReportingEndpoints::addEndpoint()}).
	 */
	public const REPORT_URI = 'REPORT_URI';

	/**
	 * Header names that may legally appear multiple times in the same HTTP
	 * response. {@see getReplace()} returns `false` for any header whose name
	 * matches one of these (case-insensitive).
	 *
	 * - {@see THttpHeaderName::SetCookie} — RFC 6265 §3: each cookie is a separate header.
	 * - {@see THttpHeaderName::Link} — RFC 8288: multiple Link headers are valid.
	 * - {@see THttpHeaderName::WWWAuthenticate} — RFC 7235: each challenge may be separate.
	 * - {@see THttpHeaderName::ContentSecurityPolicy} — CSP3: browser applies the intersection.
	 * - {@see THttpHeaderName::ContentSecurityPolicyReportOnly} — same as CSP.
	 *
	 * @var list<string>
	 */
	private const NON_REPLACING_HEADERS = [
		THttpHeaderName::SetCookie,
		THttpHeaderName::Link,
		THttpHeaderName::WWWAuthenticate,
		THttpHeaderName::ContentSecurityPolicy,
		THttpHeaderName::ContentSecurityPolicyReportOnly,
	];

	/**
	 * @var ?THttpHeadersManager the headers manager instance
	 */
	private $_manager;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Initializes the header from a PHP array, XML element, or `null`.
	 * Subclasses call {@see normalizeConfig()} to obtain a plain array regardless of input type:
	 *
	 * ```php
	 * public function init($config): void
	 * {
	 *     parent::init($config);
	 *     $this->loadFoo($this->normalizeConfig($config));
	 * }
	 * ```
	 *
	 * @param null|array|\Prado\Xml\TXmlElement $config configuration for this header.
	 */
	public function init($config): void
	{
	}

	/**
	 * Converts an XML config element to the PHP array format accepted by this header's
	 * `load*()` methods; override in subclasses that support child-element config.
	 * The base implementation returns `[]`.
	 * @param TXmlElement $config the raw XML element passed to {@see init()}.
	 * @return array PHP representation of `$config`'s child elements.
	 */
	protected function configToArray(TXmlElement $config): array
	{
		return [];
	}

	/**
	 * Normalizes `$config` to a PHP array: `TXmlElement` → {@see configToArray()},
	 * `array` → as-is, everything else → `[]`.
	 * @param mixed $config raw value received by {@see init()}.
	 * @return array normalized PHP array.
	 */
	final protected function normalizeConfig(mixed $config): array
	{
		if ($config instanceof TXmlElement) {
			return $this->configToArray($config);
		}
		return is_array($config) ? $config : [];
	}

	/**
	 * Called after all headers are loaded; override for cross-header setup that requires
	 * sibling headers to be present. Validation and warnings belong in
	 * {@see finalizeHeader()}, not here, as header state may still change before send time.
	 */
	public function initComplete(): void
	{
	}

	/**
	 * Called immediately before headers are sent; override for last-minute value
	 * adjustments, validation, and misconfiguration warnings.
	 */
	public function finalizeHeader(): void
	{
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return ?THttpHeadersManager the headers manager instance.
	 */
	public function getManager(): ?THttpHeadersManager
	{
		return $this->_manager;
	}

	/**
	 * @param ?THttpHeadersManager $value the headers manager instance.
	 */
	public function setManager($value): void
	{
		$this->_manager = $value;
	}

	/**
	 * Returns `false` for headers in {@see NON_REPLACING_HEADERS} (multi-value headers),
	 * `true` for all others; maps to PHP's {@see header()} `$replace` argument.
	 * @return bool `false` for multi-value headers, `true` for singletons.
	 */
	public function getReplace(): bool
	{
		$name = $this->getHeaderName();
		foreach (self::NON_REPLACING_HEADERS as $h) {
			if (strcasecmp($name, $h) === 0) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return string the name of the header.
	 */
	abstract public function getHeaderName(): string;

	/**
	 * @return string the textual value of the header.
	 */
	abstract public function getHeaderValue(): string;

	/**
	 * Sets the header value from a raw configuration string.
	 * Store it verbatim or parse it into typed properties.
	 * @param mixed $value
	 */
	abstract public function setHeaderValue($value): void;

	// =========================================================================
	// Actions / rendering
	// =========================================================================

	/**
	 * Sends this header to the response via the {@see getReplace()}-derived replace flag.
	 * @param ?\Prado\Web\THttpResponse $response
	 */
	public function sendHeader($response = null): void
	{
		if ($response === null) {
			$response = $this->getManager()?->getResponse();
		}
		if ($response) {
			$response->appendHeader((string) $this, $this->getReplace());
		} else {
			$this->header((string) $this, $this->getReplace());
		}
	}

	/**
	 * Renders the header as a `Name: Value` string.
	 * @return string the header line
	 */
	public function __toString(): string
	{
		return $this->getHeaderName() . ': ' . $this->getHeaderValue();
	}

	/**
	 * Wraps PHP's built-in {@see \header()} as a protected seam for unit testing.
	 * @param string $header        raw header string, e.g. `X-Frame-Options: DENY`.
	 * @param bool   $replace       replace an existing same-name header; default `true`.
	 * @param int    $response_code HTTP response code to force; `0` leaves it unchanged.
	 */
	protected function header(string $header, bool $replace = true, int $response_code = 0): void
	{
		header($header, $replace, $response_code);
	}
}
