<?php

/**
 * TSocketStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TSocketException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFuture;
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
 * {@see connectAsync()} and {@see enableCryptoAsync()} return a {@see TFuture} and wait for
 * readiness on {@see TAsync::getLoop()}, so a fiber that connects or negotiates TLS yields to
 * the other fibers.  Host-name resolution inside {@see stream_socket_client()} blocks the
 * process because PHP has no asynchronous resolver.  A caller that must not block passes an
 * IP address, resolved by a non-blocking resolver such as `amphp/dns`, and names the host in
 * the `ssl` context option `peer_name`.
 *
 * ```php
 * $context = stream_context_create(['ssl' => ['peer_name' => 'example.com']]);
 * $socket = TSocketStream::connectAsync('tcp://93.184.215.14:443', 5.0, $context)->await();
 * $socket->enableCryptoAsync()->await();
 * $stream = new TAsyncStream($socket);
 * ```
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
	// =========================================================================
	// Socket Setup
	// =========================================================================

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
	 * Connects a client socket without blocking the process on the connect.  The socket opens
	 * with STREAM_CLIENT_ASYNC_CONNECT and non-blocking, the loop waits for writability, and the
	 * connect succeeds when the socket has a peer.  The socket stays non-blocking after the
	 * future resolves, ready for a {@see \Prado\IO\Async\TAsyncStream}.
	 *
	 * - Host-name resolution inside {@see stream_socket_client()} blocks the process; pass an
	 *   IP address to connect without blocking.
	 * - A TLS scheme is not accepted, because PHP cannot finish its handshake after an
	 *   asynchronous connect.  Connect over tcp:// and call {@see enableCryptoAsync()}; the `ssl`
	 *   options of $context, such as `peer_name`, apply to that handshake.
	 *
	 * | Outcome | Future |
	 * |---|---|
	 * | the socket has a peer | resolves with the connected socket and raises {@see onConnect} |
	 * | the open or the connect fails | rejects with a {@see TSocketException}; the socket closes |
	 * | $timeout elapses first | rejects with a {@see TSocketException}; the socket closes |
	 * | $token is cancelled first | rejects with the token's {@see TCancelledException}; the socket closes |
	 * | $token is already cancelled | rejects with its reason; no socket opens |
	 * @param string $uri The endpoint, e.g. 'tcp://127.0.0.1:80', 'unix:///tmp/app.sock'.
	 * @param ?float $timeout The seconds the whole connect may take; null uses default_socket_timeout.
	 * @param mixed $context An optional stream context.
	 * @param ?TCancellationToken $token A token whose cancellation ends the connect.
	 * @throws TInvalidDataValueException When the URI has a TLS scheme.
	 * @return TFuture Resolves with the connected socket.
	 */
	public static function connectAsync(string $uri, ?float $timeout = null, mixed $context = null, ?TCancellationToken $token = null): TFuture
	{
		if (preg_match('/^(?:ssl|tls)[^:]*:\/\//i', $uri) === 1) {
			throw new TInvalidDataValueException('socketstream_async_tls_scheme', $uri);
		}
		if ($token !== null && $token->isCancelled()) {
			return TFuture::error($token->getReason());
		}
		$errno = 0;
		$errstr = '';
		$timeout ??= (float) ini_get('default_socket_timeout');
		$ctx = $context ?? stream_context_create();
		$resource = @stream_socket_client($uri, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT, $ctx);
		if ($resource === false) {
			return TFuture::error(new TSocketException($errno, $errstr !== '' ? $errstr : ('Unable to connect to ' . $uri)));
		}
		$socket = Prado::createComponent(static::class);
		$socket->attachResource($resource, true);
		$socket->setBlocking(false);
		$connected = static function () use ($socket, $uri): bool {
			$socket->assertConnected($uri);
			$socket->onConnect($uri);
			return true;
		};
		$expired = static fn (): TSocketException => new TSocketException(self::getTimedOutErrorCode(), 'socketstream_connect_timeout', $uri, $timeout);
		return $socket->waitOnLoop($connected, $socket, false, true, $timeout, $expired, $token);
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

	// =========================================================================
	// Addresses
	// =========================================================================

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

	// =========================================================================
	// Datagram and Out-of-Band I/O
	// =========================================================================

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

	// =========================================================================
	// TLS
	// =========================================================================

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
	 * Turns encryption on or off without blocking the process on the handshake.  The socket is
	 * set non-blocking, and each {@see stream_socket_enable_crypto()} call that returns 0 waits
	 * for readability on the loop before the next.  PHP does not report whether OpenSSL waits to
	 * read or to write; a handshake message fits the socket send buffer, so the retry waits to
	 * read.  The socket stays non-blocking after the future settles.
	 *
	 * A null $method with $enable true uses the `crypto_method` option of the socket's `ssl`
	 * context, or STREAM_CRYPTO_METHOD_TLS_CLIENT when the context has none.  The other `ssl`
	 * options of the context, such as `peer_name`, `verify_peer`, `cafile`, and
	 * `alpn_protocols`, apply to the handshake.
	 *
	 * | Outcome | Future |
	 * |---|---|
	 * | the handshake completes | resolves with true |
	 * | the handshake fails | rejects with a {@see TSocketException} carrying the OpenSSL message; the socket closes |
	 * | the socket is not open | rejects with a {@see TSocketException} |
	 * | $timeout elapses first | rejects with a {@see TSocketException}; the socket closes |
	 * | $token is cancelled first | rejects with the token's {@see TCancelledException}; the socket closes |
	 * | $token is already cancelled | rejects with its reason; the socket is untouched |
	 * @param bool $enable Whether to enable (true) or disable (false) crypto.
	 * @param ?int $method A STREAM_CRYPTO_METHOD_* constant, or null for the default above.
	 * @param ?float $timeout The seconds the handshake may take; null uses default_socket_timeout.
	 * @param ?TCancellationToken $token A token whose cancellation ends the handshake.
	 * @return TFuture Resolves with true once the handshake completes.
	 */
	public function enableCryptoAsync(bool $enable = true, ?int $method = null, ?float $timeout = null, ?TCancellationToken $token = null): TFuture
	{
		if ($token !== null && $token->isCancelled()) {
			return TFuture::error($token->getReason());
		}
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return TFuture::error(new TSocketException(0, 'socketstream_not_open'));
		}
		if ($method === null && $enable && !isset(stream_context_get_options($resource)['ssl']['crypto_method'])) {
			$method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
		}
		$this->setBlocking(false);
		$timeout ??= (float) ini_get('default_socket_timeout');
		$attempt = fn (): bool => $this->attemptCrypto($enable, $method);
		$expired = static fn (): TSocketException => new TSocketException(self::getTimedOutErrorCode(), 'socketstream_crypto_timeout', $timeout);
		return $this->waitOnLoop($attempt, true, true, false, $timeout, $expired, $token);
	}

	/**
	 * Makes one non-blocking {@see stream_socket_enable_crypto()} call for {@see enableCryptoAsync()}.
	 * The PHP warning of a failed handshake becomes the exception message; without one, the last
	 * OpenSSL error is used.
	 * @param bool $enable Whether to enable (true) or disable (false) crypto.
	 * @param ?int $method A STREAM_CRYPTO_METHOD_* constant, or null for the context's.
	 * @throws TSocketException When the socket is not open or the handshake fails.
	 * @return bool Whether the handshake completed; false when it needs more I/O.
	 */
	private function attemptCrypto(bool $enable, ?int $method): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			throw new TSocketException(0, 'socketstream_not_open');
		}
		$reason = '';
		set_error_handler(static function (int $errno, string $errstr) use (&$reason): bool {
			$reason = $errstr;
			return true;
		});
		try {
			$result = $method === null
				? stream_socket_enable_crypto($resource, $enable)
				: stream_socket_enable_crypto($resource, $enable, $method);
		} finally {
			restore_error_handler();
		}
		if ($result === false) {
			if ($reason === '' && extension_loaded('openssl')) {
				while (($error = openssl_error_string()) !== false) {
					$reason = $error;
				}
			}
			throw new TSocketException(0, 'socketstream_crypto_failed', $reason !== '' ? $reason : 'no error was reported');
		}
		return $result === true;
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

	// =========================================================================
	// Loop Waits
	// =========================================================================

	/**
	 * Drives a non-blocking operation on {@see TAsync::getLoop()} until its future settles.
	 * Each readiness of the socket calls $step once, through one readiness callback that stays
	 * registered while the operation waits.  The future settles once; settling cancels the
	 * readiness callback and the timer and removes the token subscription.  A step that throws,
	 * the timer, and the token reject the future and close the socket.
	 * @param callable $step Called as `(): bool`; true completes, false waits again, a throw rejects.
	 * @param mixed $value The value the future resolves with.
	 * @param bool $immediate Whether to call $step before the first wait.
	 * @param bool $writable Whether to wait for writability (true) or readability (false).
	 * @param float $timeout The seconds until $expired rejects the future.
	 * @param callable $expired Called as `(): \Throwable` for the rejection of the timeout.
	 * @param ?TCancellationToken $token A token whose cancellation rejects the future.
	 * @return TFuture The future of the operation.
	 */
	private function waitOnLoop(callable $step, mixed $value, bool $immediate, bool $writable, float $timeout, callable $expired, ?TCancellationToken $token): TFuture
	{
		$deferred = new TDeferred();
		$loop = TAsync::getLoop();
		$ids = (object) ['wait' => null, 'timer' => null, 'subscription' => null];
		$release = static function () use ($loop, $token, $ids): void {
			if ($ids->wait !== null) {
				$loop->cancel($ids->wait);
			}
			if ($ids->timer !== null) {
				$loop->cancel($ids->timer);
			}
			if ($ids->subscription !== null) {
				$token?->unsubscribe($ids->subscription);
			}
			$ids->wait = $ids->timer = $ids->subscription = null;
		};
		$fail = function (\Throwable $error) use ($deferred, $release): void {
			if ($deferred->isComplete()) {
				return;
			}
			$release();
			$this->close();
			$deferred->reject($error);
		};
		$advance = static function () use ($step, $value, $deferred, $release, $fail): void {
			if ($deferred->isComplete()) {
				return;
			}
			try {
				$done = $step();
			} catch (\Throwable $error) {
				$fail($error);
				return;
			}
			if ($done) {
				$release();
				$deferred->resolve($value);
			}
		};
		$ids->timer = $loop->delay(max(0.0, $timeout), static fn () => $fail($expired()));
		$ids->subscription = $token?->subscribe(static fn (TCancelledException $reason) => $fail($reason));
		if ($immediate) {
			$advance();
		}
		if (!$deferred->isComplete()) {
			$ready = static fn () => $advance();
			$ids->wait = $writable ? $loop->onWritable($this, $ready) : $loop->onReadable($this, $ready);
		}
		return $deferred->getFuture();
	}

	/**
	 * Throws when a socket that turned writable after an asynchronous connect has no peer.
	 * A refused or reset connect turns writable without a peer; ext-sockets reads its errno.
	 * @param string $uri The endpoint, for the message.
	 * @throws TSocketException When the socket has no peer.
	 */
	private function assertConnected(string $uri): void
	{
		$resource = $this->getResourceDirect();
		if (is_resource($resource) && @stream_socket_get_name($resource, true) !== false) {
			return;
		}
		$errno = 0;
		if (is_resource($resource) && extension_loaded('sockets')) {
			$socket = @socket_import_stream($resource);
			if ($socket !== false) {
				$option = @socket_get_option($socket, SOL_SOCKET, SO_ERROR);
				$errno = is_int($option) ? $option : 0;
			}
		}
		if ($errno !== 0) {
			throw new TSocketException($errno, 'socketstream_connect_failed', $uri, socket_strerror($errno));
		}
		throw new TSocketException(0, 'socketstream_connect_no_peer', $uri);
	}

	/**
	 * Returns the errno of a timed-out connect or handshake.
	 * @return int SOCKET_ETIMEDOUT when ext-sockets is loaded, otherwise 0.
	 */
	private static function getTimedOutErrorCode(): int
	{
		return defined('SOCKET_ETIMEDOUT') ? (int) constant('SOCKET_ETIMEDOUT') : 0;
	}

	// =========================================================================
	// Shutdown
	// =========================================================================

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

	// =========================================================================
	// Transport Availability
	// =========================================================================

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

	// =========================================================================
	// Events
	// =========================================================================

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
