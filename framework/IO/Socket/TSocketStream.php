<?php

/**
 * TSocketStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Exceptions\TSocketException;
use Prado\Prado;
use Prado\IO\TStream;

/**
 * TSocketStream class.
 *
 * Represents a connected socket as a {@see TStream}: TCP, UDP, or Unix-domain, selected
 * by the transport scheme of the connect URI.  As a {@see \Psr\Http\Message\StreamInterface},
 * it interoperates with pipes and files, and with {@see \Prado\IO\Socket\TSocketServer::select()}
 * for multiplexing across many streams at once.
 *
 * Out-of-band data and connectionless (UDP) peer addressing are exposed as methods
 * ({@see recvFrom()}/{@see sendTo()}/{@see recvOOB()}/{@see sendOOB()}).
 *
 * Events ('on' prefix).  Each is a real method taking a single mixed $param:
 *  - onConnect: after a connection is established.
 *  - onShutdown: after the connection is shut down.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketStream extends TStream
{
	/**
	 * Connects a client socket with {@see stream_socket_client()}.
	 * @param string $uri The endpoint, e.g. 'tcp://127.0.0.1:80', 'tls://host:443',
	 *   'udp://host:53', 'unix:///tmp/app.sock'.
	 * @param ?float $timeout Connect timeout in seconds; null uses default_socket_timeout.
	 * @param int $flags stream_socket_client flags. Default STREAM_CLIENT_CONNECT.
	 * @param mixed $context An optional stream context.
	 * @throws TSocketException When the connection fails.
	 * @return self The connected socket.
	 */
	public static function connect(string $uri, ?float $timeout = null, int $flags = STREAM_CLIENT_CONNECT, mixed $context = null): self
	{
		$errno = 0;
		$errstr = '';
		$timeout ??= (float) ini_get('default_socket_timeout');
		$ctx = $context ?? stream_context_create();
		$resource = @stream_socket_client($uri, $errno, $errstr, $timeout, $flags, $ctx);
		if ($resource === false) {
			throw new TSocketException($errno, $errstr !== '' ? $errstr : ('Unable to connect to ' . $uri));
		}
		$socket = Prado::createComponent(self::class);
		$socket->attachResource($resource, true);
		$socket->onConnect($uri);
		return $socket;
	}

	/**
	 * Binds a local datagram (or otherwise connectionless) socket with
	 * {@see stream_socket_server()} using STREAM_SERVER_BIND only — no listen, no
	 * accept.  This is the UDP-server counterpart to {@see connect()}: receive with
	 * {@see recvFrom()} (capturing the sender) and reply with {@see sendTo()}.
	 * ```php
	 * $server = TSocketStream::bind('udp://0.0.0.0:5000');
	 * $msg = $server->recvFrom(1500, 0, $peer);
	 * $server->sendTo('ack', 0, $peer);
	 * ```
	 * @param string $uri The local endpoint, e.g. 'udp://0.0.0.0:5000', 'udg:///tmp/dg.sock'.
	 * @param int $flags stream_socket_server flags. Default STREAM_SERVER_BIND (datagram).
	 * @param mixed $context An optional stream context.
	 * @throws TSocketException When the bind fails.
	 * @return self The bound socket.
	 */
	public static function bind(string $uri, int $flags = STREAM_SERVER_BIND, mixed $context = null): self
	{
		$errno = 0;
		$errstr = '';
		$ctx = $context ?? stream_context_create();
		$resource = @stream_socket_server($uri, $errno, $errstr, $flags, $ctx);
		if ($resource === false) {
			throw new TSocketException($errno, $errstr !== '' ? $errstr : ('Unable to bind ' . $uri));
		}
		$socket = Prado::createComponent(self::class);
		$socket->attachResource($resource, true);
		return $socket;
	}

	/**
	 * Creates a connected pair of sockets ({@see stream_socket_pair()}), useful for
	 * in-process or parent/child (post-fork) communication.  A null domain selects
	 * STREAM_PF_UNIX on POSIX and STREAM_PF_INET on Windows, which lacks Unix pairs.
	 * @param ?int $domain STREAM_PF_UNIX, STREAM_PF_INET or STREAM_PF_INET6; null auto-selects.
	 * @param int $type STREAM_SOCK_STREAM or STREAM_SOCK_DGRAM.
	 * @param int $protocol The protocol (0 selects the default for the type).
	 * @throws TSocketException When the pair cannot be created.
	 * @return array{0: self, 1: self} The two connected sockets.
	 */
	public static function pair(?int $domain = null, int $type = STREAM_SOCK_STREAM, int $protocol = 0): array
	{
		$domain ??= (PHP_OS_FAMILY === 'Windows') ? STREAM_PF_INET : STREAM_PF_UNIX;
		$pair = @stream_socket_pair($domain, $type, $protocol);
		if ($pair === false) {
			throw new TSocketException(0, 'Unable to create a socket pair');
		}
		$first = Prado::createComponent(self::class);
		$first->attachResource($pair[0], true);
		$second = Prado::createComponent(self::class);
		$second->attachResource($pair[1], true);
		return [$first, $second];
	}

	/**
	 * Returns the remote (peer) address.
	 * @return ?TSocketAddress The remote (peer) address, or null when unavailable.
	 */
	public function getRemoteAddress(): ?TSocketAddress
	{
		return $this->socketName(true);
	}

	/**
	 * Returns the local address.
	 * @return ?TSocketAddress The local address, or null when unavailable.
	 */
	public function getLocalAddress(): ?TSocketAddress
	{
		return $this->socketName(false);
	}

	/**
	 * Resolves the local or remote socket name into a {@see TSocketAddress}.
	 * @param bool $remote Whether to resolve the remote (peer) name.
	 * @return ?TSocketAddress The address, or null when unavailable.
	 */
	private function socketName(bool $remote): ?TSocketAddress
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return null;
		}
		$name = @stream_socket_get_name($resource, $remote);
		return ($name === false || $name === '') ? null : TSocketAddress::parse($name);
	}

	/**
	 * Receives data, optionally with flags and the peer address (connectionless).
	 * @param int $length Maximum bytes to receive.
	 * @param int $flags STREAM_OOB and/or STREAM_PEEK. Default 0.
	 * @param ?string &$address Set to the peer address (for datagram sockets).
	 * @return false|string The bytes received, or false on failure.
	 */
	public function recvFrom(int $length, int $flags = 0, ?string &$address = null): false|string
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return stream_socket_recvfrom($resource, $length, $flags, $address);
	}

	/**
	 * Sends data, optionally with flags and an explicit peer address (connectionless).
	 * @param string $data The bytes to send.
	 * @param int $flags STREAM_OOB. Default 0.
	 * @param ?string $address The destination address (for datagram sockets).
	 * @return false|int The number of bytes sent, or false on failure.
	 */
	public function sendTo(string $data, int $flags = 0, ?string $address = null): false|int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return $address === null
			? stream_socket_sendto($resource, $data, $flags)
			: stream_socket_sendto($resource, $data, $flags, $address);
	}

	/**
	 * Receives out-of-band data.
	 * @param int $length Maximum bytes to receive.
	 * @return false|string The OOB bytes, or false on failure.
	 */
	public function recvOOB(int $length): false|string
	{
		return $this->recvFrom($length, STREAM_OOB);
	}

	/**
	 * Sends out-of-band data.
	 * @param string $data The bytes to send.
	 * @return false|int The number of bytes sent, or false on failure.
	 */
	public function sendOOB(string $data): false|int
	{
		return $this->sendTo($data, STREAM_OOB);
	}

	/**
	 * Turns encryption on or off ({@see stream_socket_enable_crypto()}).
	 * @param bool $enable Whether to enable (true) or disable (false) crypto.
	 * @param ?int $method A STREAM_CRYPTO_METHOD_* constant, or null for negotiated.
	 * @return bool|int True/false, or 0 when more data is needed (non-blocking).
	 */
	public function enableCrypto(bool $enable = true, ?int $method = null): bool|int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return $method === null
			? stream_socket_enable_crypto($resource, $enable)
			: stream_socket_enable_crypto($resource, $enable, $method);
	}

	/**
	 * Returns the TLS crypto metadata negotiated on the connection (protocol, cipher, ALPN).
	 * @return array The `crypto` block of the stream metadata, or [] when the connection is not encrypted.
	 */
	public function getCryptoMeta(): array
	{
		$crypto = $this->getMetadata('crypto');
		return is_array($crypto) ? $crypto : [];
	}

	/**
	 * Returns the ALPN protocol negotiated during the TLS handshake (e.g. 'h2', 'http/1.1').
	 * A client confirms the server's choice and a server reads the client's; HTTP/2 over TLS
	 * requires 'h2'.
	 * @return ?string The negotiated ALPN protocol, or null when none was negotiated or the
	 *   connection is not encrypted.
	 */
	public function getAlpnProtocol(): ?string
	{
		$alpn = $this->getCryptoMeta()['alpn_protocol'] ?? null;
		return ($alpn === null || $alpn === '') ? null : $alpn;
	}

	/**
	 * Shuts down reception and/or transmission on the socket.
	 * @param int $how STREAM_SHUT_RD, STREAM_SHUT_WR or STREAM_SHUT_RDWR (default).
	 * @return bool Whether the shutdown succeeded.
	 */
	public function shutdown(int $how = STREAM_SHUT_RDWR): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		$result = stream_socket_shutdown($resource, $how);
		if ($result) {
			$this->onShutdown($how);
		}
		return $result;
	}

	/**
	 * Lists the transports available in this PHP build.
	 * @return array The transports available in this PHP build ({@see stream_get_transports()}).
	 */
	public static function getTransports(): array
	{
		return stream_get_transports();
	}

	/**
	 * Indicates whether a transport is available in this PHP build.
	 * @param string $transport A transport name (e.g. 'tcp', 'tls').
	 * @return bool Whether the transport is available.
	 */
	public static function supportsTransport(string $transport): bool
	{
		return in_array($transport, stream_get_transports(), true);
	}

	/**
	 * Raised after a connection is established.
	 * @param mixed $param The endpoint URI.
	 */
	public function onConnect(mixed $param): void
	{
		$this->raiseEvent('onConnect', $this, $param);
	}

	/**
	 * Raised after the connection is shut down.
	 * @param mixed $param The shutdown mode.
	 */
	public function onShutdown(mixed $param): void
	{
		$this->raiseEvent('onShutdown', $this, $param);
	}
}
