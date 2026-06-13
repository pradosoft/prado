<?php

/**
 * TSocketServer class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Exceptions\TSocketException;
use Prado\Prado;
use Prado\IO\IResource;
use Prado\IO\TResource;

/**
 * TSocketServer class.
 *
 * Listens for incoming connections on a server socket created with
 * {@see stream_socket_server()}.  The listener itself is not read or written as a
 * byte stream, so it extends
 * {@see TResource}; {@see accept()} yields a connected {@see TSocketStream}.
 *
 * {@see select()} multiplexes readiness across any mix of {@see IResource} objects
 * (sockets, process pipes, files) and raw resources, the basis for an event loop.
 *
 * The server is iterable ({@see \IteratorAggregate}): `foreach ($server as $connection)`
 * blocks on {@see accept()} and yields each connected {@see TSocketStream} until the
 * server is closed.  A non-blocking accept is `accept(0)`.
 *
 * Accepted connections are tracked in {@see getConnections()} for the server's lifetime,
 * which makes broadcast, presence, and connection counts possible.  {@see addConnection()}
 * folds an externally-created connection (an outbound dial or a server-to-server link) into the
 * same registry, so a {@see select()} loop multiplexes it alongside accepted ones.  A connection
 * is removed from the registry when it closes, so close each connection (or it lingers, since the
 * registry holds a reference that keeps it from being collected).
 *
 * Events ('on' prefix).  Each is a real method taking a single mixed $param:
 *  - onAccept: raised with the accepted {@see TSocketStream}.
 *  - onClientFinalize: raised with the client when it is about to close (still open).
 *  - onClientClose: raised with the client after it has closed (removed from the registry).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketServer extends TResource implements \IteratorAggregate
{
	/** @var array<int, TSocketStream> The active accepted connections, keyed by object id. */
	private array $_connections = [];

	// =========================================================================
	// Server Setup
	// =========================================================================

	/**
	 * Creates a listening server socket.
	 * @param string $uri The bind endpoint, e.g. 'tcp://0.0.0.0:8080', 'unix:///tmp/app.sock'.
	 * @param int $flags stream_socket_server flags. Default bind+listen.
	 * @param mixed $context An optional stream context.
	 * @throws TSocketException When the server cannot be created.
	 * @return static The listening server.
	 */
	public static function bind(string $uri, int $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, mixed $context = null): static
	{
		$errno = 0;
		$errstr = '';
		$ctx = $context ?? stream_context_create();
		$resource = @stream_socket_server($uri, $errno, $errstr, $flags, $ctx);
		if ($resource === false) {
			throw new TSocketException($errno, $errstr !== '' ? $errstr : ('Unable to listen on ' . $uri));
		}
		$server = Prado::createComponent(static::class);
		$server->attachResource($resource, true);
		return $server;
	}

	// =========================================================================
	// Accepting Connections
	// =========================================================================

	/**
	 * Accepts the next incoming connection.
	 * @param ?float $timeout Seconds to wait; null uses default_socket_timeout.
	 * @param ?string &$peerName Set to the connecting peer's address.
	 * @return ?TSocketStream The accepted connection, or null on timeout/failure.
	 */
	public function accept(?float $timeout = null, ?string &$peerName = null): ?TSocketStream
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return null;
		}
		$timeout ??= (float) ini_get('default_socket_timeout');
		$connection = @stream_socket_accept($resource, $timeout, $peerName);
		if ($connection === false) {
			return null;
		}
		$stream = Prado::createComponent(TSocketStream::class);
		$stream->attachResource($connection, true);
		$this->addConnection($stream);
		$this->onAccept($stream);
		return $stream;
	}

	/**
	 * Tracks a connection in the registry and forwards its teardown to the server events.
	 *
	 * {@see accept()} tracks every connection it accepts.  Pass an externally-created connection —
	 * an outbound dial, a server-to-server link, or any app-held {@see TSocketStream} — to fold it
	 * into the same registry, so {@see getConnections()} reports it and a {@see select()} loop
	 * multiplexes it alongside accepted connections.  The connection leaves the registry when it
	 * closes.  Tracking the same connection twice is a no-op.
	 *
	 * The teardown handlers hold a {@see \WeakReference} to the server, not a strong one, so a
	 * connection that is shared with or handed to another server (or otherwise outlives this one)
	 * does not keep this server from being collected.  A handler whose server has been collected is
	 * a no-op, which also covers the {@see TSocketStream} raising {@see onClientFinalize}/
	 * {@see onClientClose} as it closes during its own garbage collection.
	 * @param TSocketStream $stream The connection to track.
	 */
	public function addConnection(TSocketStream $stream): void
	{
		if (isset($this->getConnectionsDirect()[spl_object_id($stream)])) {
			return;
		}
		$this->addConnectionDirect($stream);

		// Static closures: they carry no bound $this, so the stream reaches the server only through
		// the weak reference and never keeps it alive.
		$self = \WeakReference::create($this);
		$stream->attachEventHandler('onFinalize', static function ($sender, $param) use ($self): void {
			$server = $self->get();
			if ($server instanceof self) {
				$server->onClientFinalize($sender);
			}
		});
		$stream->attachEventHandler('onClose', static function ($sender, $param) use ($self): void {
			$server = $self->get();
			if ($server instanceof self) {
				$server->removeConnectionDirect($sender);
				$server->onClientClose($sender);
			}
		});
	}

	// =========================================================================
	// Lifecycle and Iteration
	// =========================================================================

	/**
	 * Closes the listening socket.  Use {@see closeStream()} for the boolean result.
	 */
	public function close(): void
	{
		$this->closeStream();
	}

	/**
	 * Releases the tracked connections while the server is still valid.  Freeing them here, rather
	 * than when the engine tears the property down, keeps a still-open connection's
	 * {@see addConnection() onClose handler} from reentering and mutating the registry of a server
	 * whose destruction has already begun — a use-after-free that crashes PHP 8.1, where a
	 * {@see \WeakReference} still resolves to an object during its destruction.
	 */
	public function __destruct()
	{
		$this->_connections = [];
		parent::__destruct();
	}

	/**
	 * Indicates whether the server is open and listening for connections.
	 * @return bool Whether the server is listening.
	 */
	public function isListening(): bool
	{
		return $this->isOpen();
	}

	/**
	 * Iterates accepted connections (\IteratorAggregate).  Each step blocks on
	 * {@see accept()} (up to default_socket_timeout, retrying through quiet periods) and
	 * yields a connected {@see TSocketStream}; the loop ends when the server is closed.
	 * @return \Generator<int, TSocketStream> The accepted connections.
	 */
	public function getIterator(): \Generator
	{
		while ($this->isListening()) {
			$connection = $this->accept();
			if ($connection !== null) {
				yield $connection;
			}
		}
	}

	// =========================================================================
	// Local Address
	// =========================================================================

	/**
	 * Returns the local (bound) address.
	 * @return ?TSocketAddress The local (bound) address, or null.
	 */
	public function getLocalAddress(): ?TSocketAddress
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return null;
		}
		$name = @stream_socket_get_name($resource, false);
		return ($name === false || $name === '') ? null : TSocketAddress::parse($name);
	}

	/**
	 * Returns the local (bound) port.
	 * @return ?int The bound port, or null when unavailable.
	 */
	public function getPort(): ?int
	{
		return $this->getLocalAddress()?->getPort();
	}

	/**
	 * Returns the local (bound) host.
	 * @return ?string The bound host, or null when unavailable.
	 */
	public function getHost(): ?string
	{
		return $this->getLocalAddress()?->getHost();
	}

	// =========================================================================
	// Readiness Multiplexing
	// =========================================================================

	/**
	 * Waits for readiness across sets of streams ({@see stream_select()} wrapper).
	 * Each set may mix {@see IResource} objects and raw resources; on return, each
	 * array is rewritten to contain only the ready originals (objects preserved).
	 * @param ?array &$read Streams to watch for readability.
	 * @param ?array &$write Streams to watch for writability.
	 * @param ?array &$except Streams to watch for out-of-band data.
	 * @param ?int $seconds Timeout seconds; null blocks indefinitely.
	 * @param int $microseconds Additional timeout microseconds.
	 * @return false|int The number of ready streams, or false on error.
	 */
	public static function select(?array &$read, ?array &$write, ?array &$except, ?int $seconds = null, int $microseconds = 0): false|int
	{
		$extract = static function (?array $items): array {
			$resources = [];
			foreach (($items ?? []) as $key => $item) {
				$resource = ($item instanceof IResource) ? $item->getResource() : $item;
				if (is_resource($resource)) {
					$resources[$key] = $resource;
				}
			}
			return $resources;
		};
		$rebuild = static function (?array $original, array $ready): ?array {
			if ($original === null) {
				return null;
			}
			$result = [];
			foreach ($original as $key => $item) {
				$resource = ($item instanceof IResource) ? $item->getResource() : $item;
				if (is_resource($resource) && in_array($resource, $ready, true)) {
					$result[$key] = $item;
				}
			}
			return $result;
		};

		$r = $extract($read);
		$w = $extract($write);
		$e = $extract($except);
		$count = @stream_select($r, $w, $e, $seconds, $microseconds);
		if ($count === false) {
			return false;
		}
		$read = $rebuild($read, $r);
		$write = $rebuild($write, $w);
		$except = $rebuild($except, $e);
		return $count;
	}

	// =========================================================================
	// Connection Registry
	// =========================================================================

	/**
	 * Returns the raw connection registry, keyed by object id.
	 * @return array<int, TSocketStream> The raw connection registry.
	 */
	protected function getConnectionsDirect(): array
	{
		return $this->_connections;
	}

	/**
	 * Adds a connection to the registry, keyed by its object id.
	 * @param TSocketStream $stream The connection to register.
	 */
	protected function addConnectionDirect(TSocketStream $stream): void
	{
		$this->_connections[spl_object_id($stream)] = $stream;
	}

	/**
	 * Removes a connection from the registry by its object id.
	 * @param TSocketStream $stream The connection to remove.
	 */
	protected function removeConnectionDirect(TSocketStream $stream): void
	{
		unset($this->_connections[spl_object_id($stream)]);
	}

	/**
	 * Returns the active accepted connections.
	 * @return array<int, TSocketStream> The active connections, in acceptance order.
	 */
	public function getConnections(): array
	{
		return array_values($this->getConnectionsDirect());
	}

	/**
	 * Returns the number of active accepted connections.
	 * @return int The active connection count.
	 */
	public function getConnectionCount(): int
	{
		return count($this->getConnectionsDirect());
	}

	// =========================================================================
	// Events
	// =========================================================================

	/**
	 * Raised when a connection is accepted.
	 * @param mixed $param The accepted TSocketStream.
	 */
	public function onAccept(mixed $param): void
	{
		$this->raiseEvent('onAccept', $this, $param);
	}

	/**
	 * Raised with a tracked connection just before it closes (the socket is still open).
	 * @param mixed $param The closing {@see TSocketStream}.
	 */
	public function onClientFinalize(mixed $param): void
	{
		$this->raiseEvent('onClientFinalize', $this, $param);
	}

	/**
	 * Raised with a tracked connection after it has closed and left the registry.
	 * @param mixed $param The closed {@see TSocketStream}.
	 */
	public function onClientClose(mixed $param): void
	{
		$this->raiseEvent('onClientClose', $this, $param);
	}

	// =========================================================================
	// Serialization
	// =========================================================================

	/**
	 * Excludes the non-serializable connection registry from {@see \Prado\TComponent::__sleep()}.
	 * @param array &$exprops The properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_connections";
	}
}
