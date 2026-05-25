<?php

/**
 * THttpHeader class file
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\HttpHeaders;

use Prado\TPropertyValue;

/**
 * THttpHeader class
 *
 * THttpHeader represents a generic HTTP header as a name–value pair.
 *
 * {@see getReplace()} returns `false` automatically for well-known multi-value
 * headers (`Set-Cookie`, `Link`, `WWW-Authenticate`,
 * `Content-Security-Policy`, `Content-Security-Policy-Report-Only`); all
 * others return `true`. Use {@see setReplace()} to override per instance;
 * pass `null` to revert to auto-detection.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.4.0
 */
class THttpHeader extends THttpHeaderBase
{
	/**
	 * @var string Header name.
	 */
	private string $_name = '';

	/**
	 * @var string Header value.
	 */
	private string $_value = '';

	/**
	 * @var ?bool per-instance replace override.
	 *   `null` — delegate to the base-class auto-detection. (default)
	 *   `true`/`false` — explicit override set via {@see setReplace()}.
	 */
	private ?bool $_replace = null;

	// =========================================================================
	// Properties
	// =========================================================================

	/**
	 * @return string the name of the header.
	 */
	public function getHeaderName(): string
	{
		return $this->_name;
	}

	/**
	 * Sets the header name, trimming surrounding whitespace before storage.
	 * @param string $name header name, e.g. `Content-Type`.
	 */
	public function setHeaderName($name): void
	{
		$this->_name = trim(TPropertyValue::ensureString($name));
	}

	/**
	 * @return string the textual value of the header.
	 */
	public function getHeaderValue(): string
	{
		return $this->_value;
	}

	/**
	 * Sets the value of the header.
	 * @param string $value header value, e.g. `text/html; charset=UTF-8`.
	 */
	public function setHeaderValue($value): void
	{
		$this->_value = TPropertyValue::ensureString($value);
	}

	/**
	 * Returns the explicit override set via {@see setReplace()}, or delegates
	 * to the base-class auto-detection when no override has been set.
	 * @return bool `true` to replace an existing same-name header, `false` to append.
	 */
	public function getReplace(): bool
	{
		return $this->_replace ?? parent::getReplace();
	}

	/**
	 * Overrides the auto-detected replace flag for this instance.
	 * Pass `null` to revert to auto-detection; any other value is coerced to `bool`.
	 * @param null|bool|string $value `true`/`false` to override, or `null` to clear.
	 */
	public function setReplace(mixed $value): void
	{
		$this->_replace = ($value === null) ? null : TPropertyValue::ensureBoolean($value);
	}
}
