<?php

/**
 * TRequest class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Http;

use Prado\IO\TResourceUri;
use Prado\IO\TStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * TRequest class
 *
 * TRequest is the immutable PSR-7 {@see RequestInterface} of an outgoing HTTP request:
 * the method, the URI, the request target, the headers, and the body.  An
 * {@see \Prado\IO\HttpClient\THttpClient} sends it.
 * ```php
 * $request = new TRequest('POST', 'https://api.example.com/items', ['Content-Type' => 'application/json'], '{"name":"a"}');
 * $request = $request->withHeader('Accept', 'application/json');
 * ```
 *
 * The method keeps its case.  The `Host` header follows the URI's host and port and is
 * the first header: the constructor and {@see withUri()} set it, except that
 * `withUri($uri, true)` keeps a `Host` header that is already present.  The request
 * target is the URI's origin form (`/path?query`, `/` for an empty path) until
 * {@see withRequestTarget()} sets another form.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRequest extends TMessage implements RequestInterface
{
	/** @var string The request method. */
	private string $_method;

	/** @var UriInterface The request URI. */
	private UriInterface $_uri;

	/** @var ?string The explicit request target, or null for the URI's origin form. */
	private ?string $_requestTarget = null;

	/**
	 * Builds a request.
	 * @param string $method The request method, such as `GET`.
	 * @param string|UriInterface $uri The request URI.
	 * @param array<string, string|string[]> $headers The headers by name.
	 * @param mixed $body The body: a {@see \Psr\Http\Message\StreamInterface}, a string, a resource, or null for none.
	 * @param string $version The protocol version.
	 * @throws \InvalidArgumentException When the method, URI, a header, or the version is invalid.
	 */
	public function __construct(string $method, string|UriInterface $uri, array $headers = [], mixed $body = null, string $version = self::DEFAULT_PROTOCOL_VERSION)
	{
		parent::__construct();
		$this->setMethodDirect($this->filterMethod($method));
		$this->setUriDirect($uri instanceof UriInterface ? $uri : new TResourceUri($uri));
		$this->setHeaders($headers);
		$this->setProtocolVersionDirect($this->filterProtocolVersion($version));
		if (!$this->hasHeader('Host')) {
			$this->updateHostFromUri();
		}
		if ($body !== null && $body !== '') {
			$this->setBodyDirect(TStream::for($body));
		}
	}

	//
	// ─── Protected raw accessors ─────────────────────────────────────────────
	//

	/** @return string Returns the stored method. */
	protected function getMethodDirect(): string
	{
		return $this->_method;
	}

	/** @param string $value Stores the method. */
	protected function setMethodDirect(string $value): void
	{
		$this->_method = $value;
	}

	/** @return UriInterface Returns the stored URI. */
	protected function getUriDirect(): UriInterface
	{
		return $this->_uri;
	}

	/** @param UriInterface $value Stores the URI. */
	protected function setUriDirect(UriInterface $value): void
	{
		$this->_uri = $value;
	}

	/** @return ?string Returns the stored explicit request target. */
	protected function getRequestTargetDirect(): ?string
	{
		return $this->_requestTarget;
	}

	/** @param ?string $value Stores the explicit request target. */
	protected function setRequestTargetDirect(?string $value): void
	{
		$this->_requestTarget = $value;
	}

	//
	// ─── PSR-7 RequestInterface ──────────────────────────────────────────────
	//

	/**
	 * Returns the request target: the explicit target, or the URI's origin form.
	 * @return string The request target.
	 */
	public function getRequestTarget(): string
	{
		$target = $this->getRequestTargetDirect();
		if ($target !== null) {
			return $target;
		}
		$uri = $this->getUriDirect();
		$target = $uri->getPath();
		if ($target === '') {
			$target = '/';
		} elseif ($target[0] !== '/') {
			$target = '/' . $target;
		}
		$query = $uri->getQuery();
		if ($query !== '') {
			$target .= '?' . $query;
		}
		return $target;
	}

	/**
	 * Returns an instance with the specified request target, such as the absolute form or `*`.
	 * @param string $requestTarget The request target.
	 * @throws \InvalidArgumentException When the target contains whitespace.
	 * @return static The new instance.
	 */
	public function withRequestTarget(string $requestTarget): static
	{
		if (preg_match('/\s/', $requestTarget) === 1) {
			throw new \InvalidArgumentException('An HTTP request target cannot contain whitespace.');
		}
		$clone = clone $this;
		$clone->setRequestTargetDirect($requestTarget);
		return $clone;
	}

	/**
	 * Returns the request method, in the case it was given.
	 * @return string The method.
	 */
	public function getMethod(): string
	{
		return $this->getMethodDirect();
	}

	/**
	 * Returns an instance with the specified method.  The case is kept.
	 * @param string $method The method.
	 * @throws \InvalidArgumentException When the method is not a token.
	 * @return static The new instance, or this one when unchanged.
	 */
	public function withMethod(string $method): static
	{
		$method = $this->filterMethod($method);
		if ($method === $this->getMethodDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setMethodDirect($method);
		return $clone;
	}

	/**
	 * Returns the request URI.
	 * @return UriInterface The URI.
	 */
	public function getUri(): UriInterface
	{
		return $this->getUriDirect();
	}

	/**
	 * Returns an instance with the specified URI.  The `Host` header follows the new URI's
	 * host unless the URI has none, or `$preserveHost` is true and a `Host` header exists.
	 * @param UriInterface $uri The URI.
	 * @param bool $preserveHost Whether to keep an existing `Host` header.
	 * @return static The new instance, or this one when the URI is the same.
	 */
	public function withUri(UriInterface $uri, bool $preserveHost = false): static
	{
		if ($uri === $this->getUriDirect()) {
			return $this;
		}
		$clone = clone $this;
		$clone->setUriDirect($uri);
		if (!$preserveHost || !$clone->hasHeader('Host')) {
			$clone->updateHostFromUri();
		}
		return $clone;
	}

	//
	// ─── Helpers ─────────────────────────────────────────────────────────────
	//

	/**
	 * Sets the `Host` header, first in the header list, from the URI's host and any
	 * non-default port.  A URI without a host leaves the headers unchanged.
	 */
	protected function updateHostFromUri(): void
	{
		$uri = $this->getUriDirect();
		$host = $uri->getHost();
		if ($host === '') {
			return;
		}
		$port = $uri->getPort();
		if ($port !== null) {
			$host .= ':' . $port;
		}
		$this->setHeaderDirect($this->getHeaderNamesDirect()['host'] ?? 'Host', [$host], true);
	}

	/**
	 * Validates a request method as an RFC 9110 token.
	 * @param string $method The method.
	 * @throws \InvalidArgumentException When the method is not a token.
	 * @return string The method.
	 */
	protected function filterMethod(string $method): string
	{
		if (preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $method) !== 1) {
			throw new \InvalidArgumentException("Invalid HTTP method: '{$method}'.");
		}
		return $method;
	}
}
