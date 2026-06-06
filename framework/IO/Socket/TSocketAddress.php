<?php

/**
 * TSocketAddress class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\IO\TResourceUri;
use Prado\TComponent;
use Prado\Prado;

/**
 * TSocketAddress class.
 *
 * Describes a socket endpoint as an immutable value object holding transport scheme,
 * host, port, and (for unix/udg transports) path.  Parses and renders the URI forms used
 * by {@see stream_socket_client()} / {@see stream_socket_server()} and returned by
 * {@see stream_socket_get_name()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketAddress extends TComponent
{
	/** @var ?string The transport scheme (tcp, udp, tls, ssl, unix, udg), or null. */
	private ?string $_scheme;

	/** @var ?string The host (name or IP), or null. */
	private ?string $_host;

	/** @var ?int The port, or null. */
	private ?int $_port;

	/** @var ?string The path (unix/udg sockets), or null. */
	private ?string $_path;

	/**
	 * Builds a socket address from its components.
	 * @param ?string $scheme The transport scheme.
	 * @param ?string $host The host.
	 * @param ?int $port The port.
	 * @param ?string $path The unix/udg socket path.
	 */
	public function __construct(?string $scheme = null, ?string $host = null, ?int $port = null, ?string $path = null)
	{
		$this->setSchemeDirect($scheme);
		$this->setHostDirect($host);
		$this->setPortDirect($port);
		$this->setPathDirect($path);
		parent::__construct();
	}

	/**
	 * Parses a socket URI / name into a TSocketAddress.
	 * Accepts 'tcp://host:port', 'udp://host:port', 'tls://host:port',
	 * 'unix:///path/to.sock', or a bare 'host:port' (from stream_socket_get_name).
	 * @param string $uri The URI or name.
	 * @return self The parsed address.
	 */
	public static function parse(string $uri): self
	{
		$scheme = null;
		$rest = $uri;
		if (($pos = strpos($uri, '://')) !== false) {
			$scheme = strtolower(substr($uri, 0, $pos));
			$rest = substr($uri, $pos + 3);
		}
		if ($scheme === 'unix' || $scheme === 'udg') {
			return Prado::createComponent(self::class, $scheme, null, null, $rest);
		}
		$parsed = parse_url(($scheme ?? 'tcp') . '://' . $rest);
		if ($parsed === false) {
			return Prado::createComponent(self::class, $scheme, $rest !== '' ? $rest : null);
		}
		return Prado::createComponent(
			self::class,
			$scheme,
			$parsed['host'] ?? null,
			isset($parsed['port']) ? (int) $parsed['port'] : null,
			$parsed['path'] ?? null,
		);
	}

	/**
	 * Builds a socket address from a {@see TResourceUri}.
	 * Empty URI components map to null. A non-empty path becomes the unix/udg socket path.
	 * @param TResourceUri $uri The URI to read.
	 * @return self The address built from the URI components.
	 */
	public static function fromUri(TResourceUri $uri): self
	{
		$scheme = $uri->getScheme();
		$host = $uri->getHost();
		$path = $uri->getPath();
		return Prado::createComponent(
			self::class,
			$scheme !== '' ? $scheme : null,
			$host !== '' ? $host : null,
			$uri->getPort(),
			$path !== '' ? $path : null,
		);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw transport scheme.
	 * @return ?string The raw transport scheme.
	 */
	protected function getSchemeDirect(): ?string
	{
		return $this->_scheme;
	}

	/**
	 * Sets the raw transport scheme.
	 * @param ?string $value The raw transport scheme.
	 */
	protected function setSchemeDirect(?string $value): void
	{
		$this->_scheme = $value;
	}

	/**
	 * Returns the raw host.
	 * @return ?string The raw host.
	 */
	protected function getHostDirect(): ?string
	{
		return $this->_host;
	}

	/**
	 * Sets the raw host.
	 * @param ?string $value The raw host.
	 */
	protected function setHostDirect(?string $value): void
	{
		$this->_host = $value;
	}

	/**
	 * Returns the raw port.
	 * @return ?int The raw port.
	 */
	protected function getPortDirect(): ?int
	{
		return $this->_port;
	}

	/**
	 * Sets the raw port.
	 * @param ?int $value The raw port.
	 */
	protected function setPortDirect(?int $value): void
	{
		$this->_port = $value;
	}

	/**
	 * Returns the raw unix/udg socket path.
	 * @return ?string The raw unix/udg socket path.
	 */
	protected function getPathDirect(): ?string
	{
		return $this->_path;
	}

	/**
	 * Sets the raw unix/udg socket path.
	 * @param ?string $value The raw unix/udg socket path.
	 */
	protected function setPathDirect(?string $value): void
	{
		$this->_path = $value;
	}

	//
	// ─── Prado property accessors ────────────────────────────────────────────
	//

	/**
	 * Returns the transport scheme.
	 * @return ?string The transport scheme.
	 */
	public function getScheme(): ?string
	{
		return $this->getSchemeDirect();
	}

	/**
	 * Returns the host.
	 * @return ?string The host.
	 */
	public function getHost(): ?string
	{
		return $this->getHostDirect();
	}

	/**
	 * Returns the port.
	 * @return ?int The port.
	 */
	public function getPort(): ?int
	{
		return $this->getPortDirect();
	}

	/**
	 * Returns the unix/udg socket path.
	 * @return ?string The unix/udg socket path.
	 */
	public function getPath(): ?string
	{
		return $this->getPathDirect();
	}

	/**
	 * Returns the address as a {@see TResourceUri} (the ->Uri property).
	 * @return TResourceUri The address rendered as a PSR-7 URI.
	 */
	public function getUri(): TResourceUri
	{
		$uri = Prado::createComponent(TResourceUri::class);
		$scheme = $this->getSchemeDirect();
		if ($scheme !== null) {
			$uri = $uri->withScheme($scheme);
		}
		$path = $this->getPathDirect();
		if ($path !== null) {
			$uri = $uri->withPath($path);
		} else {
			$host = $this->getHostDirect();
			if ($host !== null) {
				$uri = $uri->withHost($host);
			}
			$port = $this->getPortDirect();
			if ($port !== null) {
				$uri = $uri->withPort($port);
			}
		}
		assert($uri instanceof TResourceUri);
		return $uri;
	}

	/**
	 * Renders the address as a URI string.
	 * @return string The address rendered as a URI.
	 */
	public function __toString(): string
	{
		$scheme = $this->getSchemeDirect();
		$path = $this->getPathDirect();
		$port = $this->getPortDirect();
		$prefix = $scheme !== null ? $scheme . '://' : '';
		if ($path !== null) {
			return $prefix . $path;
		}
		$hostPort = (string) $this->getHostDirect();
		if ($port !== null) {
			$hostPort .= ':' . $port;
		}
		return $prefix . $hostPort;
	}
}
