<?php

/**
 * TResourceUri class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\TComponent;
use Psr\Http\Message\UriInterface;

/**
 * TResourceUri class
 *
 * Represent an immutable, general-purpose PSR-7 {@see UriInterface} (RFC 3986) — a
 * Uniform *Resource* Identifier for any resource the IO layer addresses: files
 * (`file://`), in-process streams (`php://`), wrapped streams (`compress.zlib://`),
 * and sockets (`tcp://`, `udp://`, `unix://`), not just HTTP.  The short name TUri
 * is taken by {@see \Prado\Web\TUri}, which has extended this class as the
 * Prado-specific PSR-7 URI.
 *
 * Instances are immutable: every `with*` method returns a clone with one
 * component changed and never mutates the receiver.  Normalize the scheme and host
 * to lower case, suppress default ports from the authority, percent-encode
 * user-info/path/query/fragment, and avoid double-encoding existing triplets.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TResourceUri extends TComponent implements UriInterface
{
	private const CHAR_UNRESERVED = 'A-Za-z0-9_\-\.~';
	private const CHAR_SUBDELIMS = '!\$&\'\(\)\*\+,;=';

	private string $_scheme = '';
	private string $_userInfo = '';
	private string $_host = '';
	private ?int $_port = null;
	private string $_path = '';
	private string $_query = '';
	private string $_fragment = '';

	/**
	 * Parse the given URI string into its components.
	 * @param string $uri The URI to represent ('' for an empty URI).
	 * @throws \InvalidArgumentException When the URI cannot be parsed.
	 */
	public function __construct(string $uri = '')
	{
		if ($uri !== '') {
			$parts = $this->parseUri($uri);
			$this->setSchemeDirect(isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '');
			$this->setHostDirect(isset($parts['host']) ? $this->filterHost($parts['host']) : '');
			$this->setPortDirect(isset($parts['port']) ? $this->filterPort((int) $parts['port']) : null);
			$this->setPathDirect(isset($parts['path']) ? $this->filterPath($parts['path']) : '');
			$this->setQueryDirect(isset($parts['query']) ? $this->filterQueryOrFragment($parts['query']) : '');
			$this->setFragmentDirect(isset($parts['fragment']) ? $this->filterQueryOrFragment($parts['fragment']) : '');
			$user = $parts['user'] ?? '';
			if ($user !== '') {
				$this->setUserInfoDirect($this->filterUserInfo($user, $parts['pass'] ?? null));
			}
		}
		parent::__construct();
	}

	/**
	 * Parse a URI string into its components.  Subclasses customize the failure
	 * exception by overriding {@see createParseException()} rather than re-parsing.
	 * @param string $uri The URI to parse.
	 * @throws \Throwable When the URI cannot be parsed (see {@see createParseException()}).
	 * @return array The {@see parse_url()} components.
	 */
	protected function parseUri(string $uri): array
	{
		$parts = @parse_url($uri);
		if ($parts === false) {
			throw $this->createParseException($uri);
		}
		return $parts;
	}

	/**
	 * Build the exception thrown when a URI cannot be parsed.  Override to throw a
	 * domain-specific exception (see {@see \Prado\Web\TUri}).
	 * @param string $uri The offending URI.
	 * @return \Throwable The exception to throw.
	 */
	protected function createParseException(string $uri): \Throwable
	{
		return new \InvalidArgumentException("Unable to parse URI: {$uri}");
	}

	//
	// ─── Protected raw accessors (self-encapsulation for subclasses) ─────────
	//

	/** @return string Return the raw scheme. */
	protected function getSchemeDirect(): string
	{
		return $this->_scheme;
	}

	/** @param string $value Set the raw scheme (already normalized). */
	protected function setSchemeDirect(string $value): void
	{
		$this->_scheme = $value;
	}

	/** @return string Return the raw user-info. */
	protected function getUserInfoDirect(): string
	{
		return $this->_userInfo;
	}

	/** @param string $value Set the raw user-info (`user[:pass]`, already encoded). */
	protected function setUserInfoDirect(string $value): void
	{
		$this->_userInfo = $value;
	}

	/** @return string Return the raw host. */
	protected function getHostDirect(): string
	{
		return $this->_host;
	}

	/** @param string $value Set the raw host (already normalized). */
	protected function setHostDirect(string $value): void
	{
		$this->_host = $value;
	}

	/** @return ?int Return the raw port, with no default-port suppression. */
	protected function getPortDirect(): ?int
	{
		return $this->_port;
	}

	/** @param ?int $value Set the raw port (already validated). */
	protected function setPortDirect(?int $value): void
	{
		$this->_port = $value;
	}

	/** @return string Return the raw path. */
	protected function getPathDirect(): string
	{
		return $this->_path;
	}

	/** @param string $value Set the raw path (already encoded). */
	protected function setPathDirect(string $value): void
	{
		$this->_path = $value;
	}

	/** @return string Return the raw query. */
	protected function getQueryDirect(): string
	{
		return $this->_query;
	}

	/** @param string $value Set the raw query (already encoded). */
	protected function setQueryDirect(string $value): void
	{
		$this->_query = $value;
	}

	/** @return string Return the raw fragment. */
	protected function getFragmentDirect(): string
	{
		return $this->_fragment;
	}

	/** @param string $value Set the raw fragment (already encoded). */
	protected function setFragmentDirect(string $value): void
	{
		$this->_fragment = $value;
	}

	//
	// ─── PSR-7 getters ───────────────────────────────────────────────────────
	//

	/**
	 * Retrieve the scheme component of the URI (lower-cased, '' when absent).
	 * @return string The scheme.
	 */
	public function getScheme(): string
	{
		return $this->getSchemeDirect();
	}

	/**
	 * Retrieve the authority component (`[user-info@]host[:port]`), with the
	 * default port for the scheme suppressed.
	 * @return string The authority, or '' when no host is present.
	 */
	public function getAuthority(): string
	{
		$host = $this->getHostDirect();
		if ($host === '') {
			return '';
		}
		$userInfo = $this->getUserInfoDirect();
		if ($userInfo !== '') {
			$host = $userInfo . '@' . $host;
		}
		$port = $this->getPort();
		if ($port !== null) {
			$host .= ':' . $port;
		}
		return $host;
	}

	/**
	 * Retrieve the user-information component (percent-encoded).
	 * @return string The user-info, or '' when absent.
	 */
	public function getUserInfo(): string
	{
		return $this->getUserInfoDirect();
	}

	/**
	 * Retrieve the host component (lower-cased).
	 * @return string The host, or '' when absent.
	 */
	public function getHost(): string
	{
		return $this->getHostDirect();
	}

	/**
	 * Retrieve the port component, returning null when it is absent or equal to
	 * the default port for the current scheme.
	 * @return ?int The port, or null.
	 */
	public function getPort(): ?int
	{
		$port = $this->getPortDirect();
		if ($port === null) {
			return null;
		}
		if (TUriDefaultPort::forScheme($this->getSchemeDirect()) === $port) {
			return null;
		}
		return $port;
	}

	/**
	 * Retrieve the path component (percent-encoded).
	 * @return string The path.
	 */
	public function getPath(): string
	{
		return $this->getPathDirect();
	}

	/**
	 * Retrieve the query string (percent-encoded, without the leading '?').
	 * @return string The query.
	 */
	public function getQuery(): string
	{
		return $this->getQueryDirect();
	}

	/**
	 * Retrieve the fragment (percent-encoded, without the leading '#').
	 * @return string The fragment.
	 */
	public function getFragment(): string
	{
		return $this->getFragmentDirect();
	}

	//
	// ─── PSR-7 immutable with* methods ───────────────────────────────────────
	//

	/**
	 * Return an instance with the specified scheme.
	 * @param string $scheme The scheme ('' removes it).
	 * @throws \InvalidArgumentException When the scheme is invalid.
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withScheme(string $scheme): UriInterface
	{
		$scheme = $this->filterScheme($scheme);
		if ($scheme === $this->getSchemeDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setSchemeDirect($scheme);
		return $clone;
	}

	/**
	 * Return an instance with the specified user information.
	 * @param string $user The user name ('' removes the user-info).
	 * @param ?string $password The password, or null.
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withUserInfo(string $user, ?string $password = null): UriInterface
	{
		$info = $this->filterUserInfo($user, $password);
		if ($info === $this->getUserInfoDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setUserInfoDirect($info);
		return $clone;
	}

	/**
	 * Return an instance with the specified host.
	 * @param string $host The host ('' removes it).
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withHost(string $host): UriInterface
	{
		$host = $this->filterHost($host);
		if ($host === $this->getHostDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setHostDirect($host);
		return $clone;
	}

	/**
	 * Return an instance with the specified port.
	 * @param ?int $port The port (null removes it).
	 * @throws \InvalidArgumentException When the port is out of range.
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withPort(?int $port): UriInterface
	{
		$port = $port === null ? null : $this->filterPort($port);
		if ($port === $this->getPortDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setPortDirect($port);
		return $clone;
	}

	/**
	 * Return an instance with the specified path.
	 * @param string $path The path (percent-encoded as needed).
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withPath(string $path): UriInterface
	{
		$path = $this->filterPath($path);
		if ($path === $this->getPathDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setPathDirect($path);
		return $clone;
	}

	/**
	 * Return an instance with the specified query string.
	 * @param string $query The query (percent-encoded as needed).
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withQuery(string $query): UriInterface
	{
		$query = $this->filterQueryOrFragment($query);
		if ($query === $this->getQueryDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setQueryDirect($query);
		return $clone;
	}

	/**
	 * Return an instance with the specified fragment.
	 * @param string $fragment The fragment (percent-encoded as needed).
	 * @return UriInterface The new instance (or the same one when unchanged).
	 */
	public function withFragment(string $fragment): UriInterface
	{
		$fragment = $this->filterQueryOrFragment($fragment);
		if ($fragment === $this->getFragmentDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setFragmentDirect($fragment);
		return $clone;
	}

	/**
	 * Return the string representation of the URI, recomposed from its components.
	 * @return string The URI reference.
	 */
	public function __toString(): string
	{
		$scheme = $this->getSchemeDirect();
		$authority = $this->getAuthority();
		$path = $this->getPathDirect();

		$uri = '';
		if ($scheme !== '') {
			$uri .= $scheme . ':';
		}
		if ($authority !== '') {
			$uri .= '//' . $authority;
		} elseif ($scheme === 'file' && str_starts_with($path, '/')) {
			// A file scheme with an empty authority but an absolute path keeps the
			// '//' (file:///etc/hosts) without corrupting a rootless path.
			$uri .= '//';
		}
		if ($authority !== '' && $path !== '' && $path[0] !== '/') {
			$path = '/' . $path;
		} elseif ($authority === '' && str_starts_with($path, '//')) {
			$path = '/' . ltrim($path, '/');
		}
		$uri .= $path;
		if (($query = $this->getQueryDirect()) !== '') {
			$uri .= '?' . $query;
		}
		if (($fragment = $this->getFragmentDirect()) !== '') {
			$uri .= '#' . $fragment;
		}
		return $uri;
	}

	//
	// ─── Protected normalizers / validators ──────────────────────────────────
	//

	/**
	 * Normalize a scheme: lower-case it, strip any trailing ':' or '://', and
	 * validate it against RFC 3986 (`ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )`).
	 * @param string $scheme The raw scheme ('' removes the scheme).
	 * @throws \InvalidArgumentException When the scheme is syntactically invalid.
	 * @return string The normalized scheme.
	 */
	protected function filterScheme(string $scheme): string
	{
		$scheme = strtolower($scheme);
		$scheme = preg_replace('#:(//)?$#', '', $scheme) ?? $scheme;
		if ($scheme !== '' && !preg_match('/^[a-z][a-z0-9+\-.]*$/', $scheme)) {
			throw new \InvalidArgumentException("Invalid scheme: {$scheme}");
		}
		return $scheme;
	}

	/**
	 * Normalize a host to lower case.
	 * @param string $host The raw host.
	 * @return string The lower-cased host.
	 */
	protected function filterHost(string $host): string
	{
		return strtolower($host);
	}

	/**
	 * Validate a port against the 0..65535 range.
	 * @param int $port The port to validate.
	 * @throws \InvalidArgumentException When the port is outside 0..65535.
	 * @return int The validated port.
	 */
	protected function filterPort(int $port): int
	{
		if ($port < 0 || $port > 65535) {
			throw new \InvalidArgumentException("Invalid port: {$port}; must be between 0 and 65535");
		}
		return $port;
	}

	/**
	 * Build a percent-encoded user-info string from its components (RFC 3986
	 * §3.2.1: unreserved / sub-delims / pct), preserving the ':' separator.
	 * @param string $user The user ('' yields empty user-info).
	 * @param ?string $password The password, or null.
	 * @return string The encoded `user[:pass]`.
	 */
	protected function filterUserInfo(string $user, ?string $password = null): string
	{
		if ($user === '') {
			return '';
		}
		$info = $this->encodeUserInfoComponent($user);
		if ($password !== null && $password !== '') {
			$info .= ':' . $this->encodeUserInfoComponent($password);
		}
		return $info;
	}

	/**
	 * Percent-encode the disallowed characters of a single user-info component.
	 * @param string $component A user-info component (user or password).
	 * @return string The encoded component.
	 */
	private function encodeUserInfoComponent(string $component): string
	{
		return preg_replace_callback(
			'/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUBDELIMS . '%]+|%(?![A-Fa-f0-9]{2}))/',
			fn (array $m): string => rawurlencode($m[0]),
			$component,
		) ?? $component;
	}

	/**
	 * Percent-encode a path, leaving already-encoded triplets and allowed
	 * characters (including '/' and ':@') intact.
	 * @param string $path The raw path.
	 * @return string The encoded path.
	 */
	protected function filterPath(string $path): string
	{
		return preg_replace_callback(
			'/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUBDELIMS . '%:@\/]+|%(?![A-Fa-f0-9]{2}))/',
			fn (array $m): string => rawurlencode($m[0]),
			$path,
		) ?? $path;
	}

	/**
	 * Percent-encode a query or fragment, leaving allowed characters (including
	 * '/?:@') and existing triplets intact.
	 * @param string $value The raw query or fragment.
	 * @return string The encoded value.
	 */
	protected function filterQueryOrFragment(string $value): string
	{
		return preg_replace_callback(
			'/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUBDELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
			fn (array $m): string => rawurlencode($m[0]),
			$value,
		) ?? $value;
	}
}
