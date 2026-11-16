<?php

/**
 * TMessage class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Http;

use Prado\IO\TStream;
use Prado\TComponent;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * TMessage class
 *
 * TMessage is the immutable PSR-7 {@see MessageInterface} base of {@see TRequest} and
 * {@see TResponse}: the protocol version, the header fields, and the body stream.
 *
 * Header names are case-insensitive for lookup and keep the case they were first given
 * with.  Every header holds a list of values; {@see getHeaderLine()} joins them with a
 * comma.  A name must be an RFC 9110 token and a value must not contain CR, LF, or NUL;
 * either violation throws an `\InvalidArgumentException`.  Values are trimmed of
 * surrounding spaces and tabs.
 *
 * Every `with*` method returns a clone and leaves the receiver unchanged.  A clone shares
 * the body stream.  The body is an empty {@see TStream} until one is set.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TMessage extends TComponent implements MessageInterface
{
	/** The default protocol version. */
	public const DEFAULT_PROTOCOL_VERSION = '1.1';

	/** @var string The HTTP protocol version. */
	private string $_protocolVersion = self::DEFAULT_PROTOCOL_VERSION;

	/** @var array<string, string[]> The header values, keyed by the original header name. */
	private array $_headers = [];

	/** @var array<string, string> The original header name, keyed by the lower-case name. */
	private array $_headerNames = [];

	/** @var ?StreamInterface The body, or null until it is first read or set. */
	private ?StreamInterface $_body = null;

	//
	// ─── Protected raw accessors ─────────────────────────────────────────────
	//

	/** @return string Returns the stored protocol version. */
	protected function getProtocolVersionDirect(): string
	{
		return $this->_protocolVersion;
	}

	/** @param string $value Stores the protocol version. */
	protected function setProtocolVersionDirect(string $value): void
	{
		$this->_protocolVersion = $value;
	}

	/** @return array<string, string[]> Returns the stored headers by original name. */
	protected function getHeadersDirect(): array
	{
		return $this->_headers;
	}

	/** @return array<string, string> Returns the original header names by lower-case name. */
	protected function getHeaderNamesDirect(): array
	{
		return $this->_headerNames;
	}

	/**
	 * Stores one header's values under its name, replacing any header of the same name in
	 * any case.
	 * @param string $name The validated header name.
	 * @param string[] $values The validated values.
	 * @param bool $first Whether the header moves to the front of the header list.
	 */
	protected function setHeaderDirect(string $name, array $values, bool $first = false): void
	{
		$this->unsetHeaderDirect($name);
		$this->_headerNames[strtolower($name)] = $name;
		if ($first) {
			$this->_headers = [$name => $values] + $this->_headers;
		} else {
			$this->_headers[$name] = $values;
		}
	}

	/**
	 * Removes a header in any case.
	 * @param string $name The header name.
	 */
	protected function unsetHeaderDirect(string $name): void
	{
		$lower = strtolower($name);
		if (isset($this->_headerNames[$lower])) {
			unset($this->_headers[$this->_headerNames[$lower]], $this->_headerNames[$lower]);
		}
	}

	/** @return ?StreamInterface Returns the stored body, or null when none is set. */
	protected function getBodyDirect(): ?StreamInterface
	{
		return $this->_body;
	}

	/** @param ?StreamInterface $value Stores the body. */
	protected function setBodyDirect(?StreamInterface $value): void
	{
		$this->_body = $value;
	}

	/**
	 * Replaces all headers with the given map.  A value is a string or a list of strings.
	 * @param array<string, string|string[]> $headers The headers by name.
	 * @throws \InvalidArgumentException When a name or value is invalid.
	 */
	protected function setHeaders(array $headers): void
	{
		$this->_headers = [];
		$this->_headerNames = [];
		foreach ($headers as $name => $value) {
			$name = $this->filterHeaderName((string) $name);
			$values = $this->filterHeaderValues($value);
			$lower = strtolower($name);
			if (isset($this->_headerNames[$lower])) {
				$original = $this->_headerNames[$lower];
				$this->_headers[$original] = array_merge($this->_headers[$original], $values);
			} else {
				$this->_headerNames[$lower] = $name;
				$this->_headers[$name] = $values;
			}
		}
	}

	//
	// ─── PSR-7 MessageInterface ──────────────────────────────────────────────
	//

	/**
	 * Returns the HTTP protocol version, such as `1.1` or `2`.
	 * @return string The protocol version.
	 */
	public function getProtocolVersion(): string
	{
		return $this->getProtocolVersionDirect();
	}

	/**
	 * Returns an instance with the specified HTTP protocol version.
	 * @param string $version The protocol version, such as `1.1`.
	 * @throws \InvalidArgumentException When the version is not digits with an optional minor part.
	 * @return static The new instance, or this one when unchanged.
	 */
	public function withProtocolVersion(string $version): static
	{
		$version = $this->filterProtocolVersion($version);
		if ($version === $this->getProtocolVersionDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setProtocolVersionDirect($version);
		return $clone;
	}

	/**
	 * Returns every header with its values, keyed by the name as first given.
	 * @return array<string, string[]> The headers.
	 */
	public function getHeaders(): array
	{
		return $this->getHeadersDirect();
	}

	/**
	 * Reports whether a header exists, by case-insensitive name.
	 * @param string $name The header name.
	 * @return bool Whether the header exists.
	 */
	public function hasHeader(string $name): bool
	{
		return isset($this->getHeaderNamesDirect()[strtolower($name)]);
	}

	/**
	 * Returns the values of a header, by case-insensitive name.
	 * @param string $name The header name.
	 * @return string[] The values, or an empty array when the header is absent.
	 */
	public function getHeader(string $name): array
	{
		$original = $this->getHeaderNamesDirect()[strtolower($name)] ?? null;
		return $original === null ? [] : $this->getHeadersDirect()[$original];
	}

	/**
	 * Returns the values of a header joined with a comma, by case-insensitive name.
	 * @param string $name The header name.
	 * @return string The joined values, or `''` when the header is absent.
	 */
	public function getHeaderLine(string $name): string
	{
		return implode(', ', $this->getHeader($name));
	}

	/**
	 * Returns an instance with the header replaced by the given value or values.
	 * @param string $name The case-insensitive header name.
	 * @param string|string[] $value The value or values.
	 * @throws \InvalidArgumentException When the name or a value is invalid.
	 * @return static The new instance.
	 */
	public function withHeader(string $name, $value): static
	{
		$name = $this->filterHeaderName($name);
		$values = $this->filterHeaderValues($value);
		$clone = clone $this;
		$clone->setHeaderDirect($name, $values);
		return $clone;
	}

	/**
	 * Returns an instance with the given value or values appended to the header.  An absent
	 * header is added.
	 * @param string $name The case-insensitive header name.
	 * @param string|string[] $value The value or values.
	 * @throws \InvalidArgumentException When the name or a value is invalid.
	 * @return static The new instance.
	 */
	public function withAddedHeader(string $name, $value): static
	{
		$name = $this->filterHeaderName($name);
		$values = $this->filterHeaderValues($value);
		$original = $this->getHeaderNamesDirect()[strtolower($name)] ?? null;
		$clone = clone $this;
		if ($original === null) {
			$clone->setHeaderDirect($name, $values);
		} else {
			$clone->_headers[$original] = array_merge($clone->_headers[$original], $values);
		}
		return $clone;
	}

	/**
	 * Returns an instance without the header, by case-insensitive name.
	 * @param string $name The header name.
	 * @return static The new instance, or this one when the header is absent.
	 */
	public function withoutHeader(string $name): static
	{
		if (!$this->hasHeader($name)) {
			return $this;
		}
		$clone = clone $this;
		$clone->unsetHeaderDirect($name);
		return $clone;
	}

	/**
	 * Returns the body.  A message without a body has an empty {@see TStream}.
	 * @return StreamInterface The body.
	 */
	public function getBody(): StreamInterface
	{
		$body = $this->getBodyDirect();
		if ($body === null) {
			$body = TStream::fromString('');
			$this->setBodyDirect($body);
		}
		return $body;
	}

	/**
	 * Returns an instance with the specified body.
	 * @param StreamInterface $body The body.
	 * @return static The new instance, or this one when the body is the same.
	 */
	public function withBody(StreamInterface $body): static
	{
		if ($body === $this->getBodyDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setBodyDirect($body);
		return $clone;
	}

	//
	// ─── Validation ──────────────────────────────────────────────────────────
	//

	/**
	 * Validates a protocol version: digits with an optional `.` and minor digits.
	 * @param string $version The protocol version.
	 * @throws \InvalidArgumentException When the version is invalid.
	 * @return string The version.
	 */
	protected function filterProtocolVersion(string $version): string
	{
		if (preg_match('/^\d+(?:\.\d+)?$/', $version) !== 1) {
			throw new \InvalidArgumentException("Invalid HTTP protocol version: '{$version}'.");
		}
		return $version;
	}

	/**
	 * Validates a header name as an RFC 9110 token.
	 * @param string $name The header name.
	 * @throws \InvalidArgumentException When the name is not a token.
	 * @return string The name.
	 */
	protected function filterHeaderName(string $name): string
	{
		if (preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name) !== 1) {
			throw new \InvalidArgumentException("Invalid HTTP header name: '{$name}'.");
		}
		return $name;
	}

	/**
	 * Validates header values and trims surrounding spaces and tabs.  A value holds only
	 * visible characters, spaces, tabs, and obs-text; CR, LF, and NUL are rejected.
	 * @param mixed $value A string, a number, or a non-empty list of them.
	 * @throws \InvalidArgumentException When a value is invalid or the list is empty.
	 * @return string[] The values.
	 */
	protected function filterHeaderValues(mixed $value): array
	{
		$values = is_array($value) ? array_values($value) : [$value];
		if ($values === []) {
			throw new \InvalidArgumentException('An HTTP header needs at least one value.');
		}
		$out = [];
		foreach ($values as $item) {
			if (!is_string($item) && !is_int($item) && !is_float($item)) {
				throw new \InvalidArgumentException('An HTTP header value must be a string or a number.');
			}
			$item = trim((string) $item, " \t");
			if (preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/D', $item) !== 1) {
				throw new \InvalidArgumentException('An HTTP header value contains an invalid character.');
			}
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * Returns the property names a serialized message omits when they hold defaults.
	 * @param array $exprops The excluded property names, by reference.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_body === null) {
			$exprops[] = "\0" . self::class . "\0_body";
		}
	}
}
