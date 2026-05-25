<?php

/**
 * THttpHeaderHsts class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\THttpHeaderName;

/**
 * THttpHeaderHsts class
 *
 * THttpHeaderHsts emits a `Strict-Transport-Security` (HSTS) header with
 * typed properties for each of its three components, preventing subtle value
 * string errors.
 *
 * ```xml
 * <module id="httpHeaders" class="THttpHeadersManager">
 *   <header class="THttpHeaderHsts" MaxAge="31536000" IncludeSubDomains="true" />
 * </module>
 * ```
 *
 * Or in PHP:
 * ```php
 * [
 *     'class'      => THttpHeaderHsts::class,
 *     'properties' => [
 *         'MaxAge'            => 31536000,
 *         'IncludeSubDomains' => true,
 *         'Preload'           => true,
 *     ],
 * ]
 * ```
 *
 * **Preload requirement.** The {@see https://hstspreload.org hstspreload.org}
 * list and all major browsers require `includeSubDomains` to be present when
 * `preload` is used. Setting {@see setPreload() Preload} to `true` without
 * also setting {@see setIncludeSubDomains() IncludeSubDomains} to `true` logs
 * a warning at send time (in {@see finalizeHeader()}).
 *
 * **Relationship with CSP.** The CSP directive
 * {@see TCspDirective::UpgradeInsecureRequests} upgrades sub-resource URLs
 * within a page load; HSTS enforces HTTPS at the transport layer for all
 * requests to the host. Use both for defence in depth.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see THttpHeaderName::StrictTransportSecurity
 * @see TCspDirective::UpgradeInsecureRequests
 */
class THttpHeaderHsts extends THttpHeader
{
	/**
	 * @var int max-age in seconds. Defaults to 31536000 (one year).
	 */
	private int $_maxAge = 31536000;

	/**
	 * @var bool whether to include the includeSubDomains token.
	 */
	private bool $_includeSubDomains = false;

	/**
	 * @var bool whether to include the preload token.
	 */
	private bool $_preload = false;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	/**
	 * Logs a warning when {@see getPreload() Preload} is `true` without
	 * {@see getIncludeSubDomains() IncludeSubDomains}, because browsers and the
	 * {@see https://hstspreload.org hstspreload.org} list will reject the directive.
	 */
	public function finalizeHeader(): void
	{
		if ($this->getPreload() && !$this->getIncludeSubDomains()) {
			Prado::log(
				'THttpHeaderHsts: Preload=true requires IncludeSubDomains=true.'
				. ' The preload directive will be rejected by browsers and the'
				. ' hstspreload.org list.',
				\Prado\Util\TLogger::WARNING,
				static::class
			);
		}
	}

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string `'Strict-Transport-Security'`.
	 */
	public function getHeaderName(): string
	{
		return THttpHeaderName::StrictTransportSecurity;
	}

	/**
	 * @return int `max-age` in seconds; default `31536000` (one year).
	 */
	public function getMaxAge(): int
	{
		return $this->_maxAge;
	}

	/**
	 * @param int|string $value seconds; coerced via {@see TPropertyValue::ensureInteger()}.
	 */
	public function setMaxAge(int|string $value): void
	{
		$this->_maxAge = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return bool `true` when `includeSubDomains` is included in the header value.
	 */
	public function getIncludeSubDomains(): bool
	{
		return $this->_includeSubDomains;
	}

	/**
	 * @param bool|string $value coerced via {@see TPropertyValue::ensureBoolean()}.
	 */
	public function setIncludeSubDomains(bool|string $value): void
	{
		$this->_includeSubDomains = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool `true` when `preload` is included in the header value.
	 */
	public function getPreload(): bool
	{
		return $this->_preload;
	}

	/**
	 * Requires {@see setIncludeSubDomains() IncludeSubDomains} to also be `true`;
	 * a mismatch is logged as a warning in {@see finalizeHeader()}.
	 * @param bool|string $value coerced via {@see TPropertyValue::ensureBoolean()}.
	 */
	public function setPreload(bool|string $value): void
	{
		$this->_preload = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string e.g. `max-age=31536000; includeSubDomains; preload`
	 */
	public function getHeaderValue(): string
	{
		$value = 'max-age=' . $this->getMaxAge();
		if ($this->getIncludeSubDomains()) {
			$value .= '; includeSubDomains';
		}
		if ($this->getPreload()) {
			$value .= '; preload';
		}
		return $value;
	}

	/**
	 * Parses `max-age=N`, `includeSubDomains`, and `preload` tokens
	 * (case-insensitive; unknown tokens silently ignored). Called automatically
	 * by the manager when a plain `HeaderName="Strict-Transport-Security"`
	 * config node is promoted via the name→class registry.
	 * @param mixed $value e.g. `"max-age=31536000; includeSubDomains; preload"`
	 */
	public function setHeaderValue($value): void
	{
		$value = TPropertyValue::ensureString($value);
		$tokens = preg_split('/\s*;\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
		if ($tokens === false) {
			return;
		}
		foreach ($tokens as $token) {
			$token = trim($token);
			if (preg_match('/^max-age\s*=\s*(\d+)$/i', $token, $m)) {
				$this->setMaxAge((int) $m[1]);
			} elseif (strcasecmp($token, 'includeSubDomains') === 0) {
				$this->setIncludeSubDomains(true);
			} elseif (strcasecmp($token, 'preload') === 0) {
				$this->setPreload(true);
			}
		}
	}
}
