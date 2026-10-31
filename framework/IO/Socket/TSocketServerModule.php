<?php

/**
 * TSocketServerModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Collections\TMap;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TRevoltEventLoop;
use Prado\IO\TUriScheme;
use Prado\Prado;
use Prado\Security\Permissions\IPermissions;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Util\TSignalParameter;
use Prado\Util\TSignalsDispatcher;

/**
 * TSocketServerModule class.
 *
 * Runs a long-lived {@see TSocketServer} as an application daemon, started from the command line
 * through its paired {@see TSocketServerAction} (`php prado-cli.php socket/serve`).  A web SAPI
 * (PHP-FPM, mod_php) cannot host a socket server, so the server runs in its own CLI process.
 *
 * The module binds the listening endpoint from {@see getEndpoint() Endpoint} (composed from
 * {@see getScheme() Scheme}, {@see getAddress() Address}, and {@see getPort() Port} when not set
 * explicitly), then serves it on an {@see IEventLoop}: the loop accepts connections and dispatches
 * readable ones through {@see onClientData}, while {@see onClientConnect} and {@see onClientDisconnect}
 * bracket each connection's lifetime.  {@see getEventLoop() EventLoop} is the loop; the default is a
 * {@see getEventLoopClass() EventLoopClass} instance, a {@see TRevoltEventLoop}.  While the server
 * serves, the loop is also the {@see TAsync::getLoop() TAsync loop}, so a fiber that a handler starts
 * with {@see TAsync::run()} waits on the loop that runs.  {@see serve()} restores the prior TAsync
 * loop on exit.
 *
 * The server is connection-oriented: it accepts streams.  Configure a stream scheme (`tcp`, `tls`,
 * `ssl`, or `unix`); a connectionless datagram scheme (`udp`, `udg`) is rejected, as a datagram
 * socket has no accept step.  {@see TSocketStream::bind()} serves datagrams.
 *
 * Three seams let subclasses specialize the server without rewriting the lifecycle:
 *  - {@see createServer()} builds and configures the {@see TSocketServer} (override to bind a
 *    subclass, attach a handler, or set socket options).
 *  - {@see createEventLoop()} builds the default {@see IEventLoop} (override to register other
 *    callbacks or timers before serving).
 *  - {@see serveLoop()} runs the loop (override when the server owns its own loop).
 *
 * XML configuration:
 * ```xml
 * <module id="socketserver" class="Prado\IO\Socket\TSocketServerModule" Address="0.0.0.0" Port="8080" />
 * ```
 *
 * Running the server is gated by the {@see PERM_SOCKET_SERVER} permission (on the
 * {@see dyServe} dynamic event); without it the shell action is not registered.
 *
 * Events ('on' prefix), raised over a connection's lifetime:
 *  - onClientConnect: with the accepted {@see TSocketStream}.
 *  - onClientData: with a connection that has bytes ready to read.
 *  - onClientDisconnect: with a connection at end of stream, before it is closed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method bool dyServe(bool $value)
 */
class TSocketServerModule extends TModule implements IPermissions
{
	/** The permission to run a socket server from the shell. */
	public const PERM_SOCKET_SERVER = 'socket_server';

	/** The connectionless (datagram) schemes a connection-accepting server cannot bind to. */
	public const DATAGRAM_SCHEMES = [TUriScheme::UDP, TUriScheme::UDG];

	/** The default URI scheme of the listening endpoint. */
	public const DEFAULT_SCHEME = TUriScheme::TCP;

	/** The default address the server binds to. */
	public const DEFAULT_ADDRESS = '0.0.0.0';

	/** The default port the server binds to. */
	public const DEFAULT_PORT = 0;

	/** The default TSocketServer class the server is created from. */
	public const DEFAULT_SERVER_CLASS = TSocketServer::class;

	/** The default shell action class registered for command line control. */
	public const DEFAULT_SHELL_CLASS = TSocketServerAction::class;

	/** The default IEventLoop class the server is served on. */
	public const DEFAULT_EVENT_LOOP_CLASS = TRevoltEventLoop::class;

	/** The seconds between the signal deliveries and stop checks of {@see serveLoop()}. */
	public const SERVE_PUMP_INTERVAL = 1.0;

	/** @var string The shell action class registered for command line control. */
	private string $_shellClass;

	/** @var string The TSocketServer class the server is created from. */
	private string $_serverClass;

	/** @var string The IEventLoop class the default loop is created from. */
	private string $_eventLoopClass;

	/** @var ?IEventLoop The loop the server is served on. */
	private ?IEventLoop $_eventLoop = null;

	/** @var ?string The loop callback watching the listening server, while it serves. */
	private ?string $_acceptCallbackId = null;

	/** @var array<int, string> The loop callbacks watching the accepted connections, keyed by connection object id. */
	private array $_connectionCallbackIds = [];

	/** @var string The URI scheme of the listening endpoint. */
	private string $_scheme;

	/** @var string The address the server binds to. */
	private string $_address;

	/** @var int The port the server binds to. */
	private int $_port;

	/** @var ?string The explicit listening endpoint URI, overriding scheme/address/port. */
	private ?string $_endpoint = null;

	/** @var ?TMap The stream context options for the listening socket. */
	private ?TMap $_socketOptions = null;

	/** @var ?TSocketServer The running server, while it serves. */
	private ?TSocketServer $_server = null;

	/** @var bool Whether the serve loop has been asked to stop. */
	private bool $_stopping = false;

	/**
	 * Initializes the configurable defaults from the class constants, so a subclass tunes them by
	 * overriding the constants.
	 */
	public function __construct()
	{
		$this->_shellClass = static::DEFAULT_SHELL_CLASS;
		$this->_serverClass = static::DEFAULT_SERVER_CLASS;
		$this->_eventLoopClass = static::DEFAULT_EVENT_LOOP_CLASS;
		$this->_scheme = static::DEFAULT_SCHEME;
		$this->_address = static::DEFAULT_ADDRESS;
		$this->_port = static::DEFAULT_PORT;
		parent::__construct();
	}

	/**
	 * Initializes the module and registers the shell action on the command line application.
	 * @param null|array|\Prado\Xml\TXmlElement $config The module configuration.
	 */
	public function init($config)
	{
		$this->getApplication()->attachEventHandler('onAuthenticationComplete', [$this, 'registerShellAction']);
		parent::init($config);
	}

	/**
	 * The permissions registered for the module.
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager The permissions manager.
	 * @return \Prado\Security\Permissions\TPermissionEvent[] The module permissions.
	 */
	public function getPermissions($manager)
	{
		return [
			new TPermissionEvent(static::PERM_SOCKET_SERVER, 'Allows the user to run a socket server.', 'dyServe'),
		];
	}

	/**
	 * Registers the shell action with the command line application when permitted.
	 * @param object $sender The event sender.
	 * @param null|mixed $param The event parameter.
	 */
	public function registerShellAction($sender, $param)
	{
		if ($this->dyServe(false) !== true && ($app = $this->getApplication()) instanceof \Prado\Shell\TShellApplication) {
			$app->addShellActionClass(['class' => $this->_shellClass, 'SocketServerModule' => $this]);
		}
	}

	/**
	 * Builds and configures the listening server.  Subclasses override this to bind a
	 * {@see TSocketServer} subclass, attach a handler, or apply socket options.
	 * @throws TConfigurationException When the endpoint is unconfigured or names a datagram scheme.
	 * @return TSocketServer The configured, listening server.
	 */
	protected function createServer(): TSocketServer
	{
		$endpoint = $this->getEndpoint();
		$this->assertStreamScheme((string) parse_url($endpoint, PHP_URL_SCHEME));
		$class = $this->getServerClass();
		$context = $this->getSocketOptions()->getCount() ? stream_context_create($this->getSocketOptions()->toArray()) : null;
		return $class::bind($endpoint, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
	}

	/**
	 * Builds the default loop from {@see getEventLoopClass() EventLoopClass}.  Subclasses override
	 * this to register other callbacks or timers before {@see serveLoop()} runs it.
	 * @return IEventLoop The loop.
	 */
	protected function createEventLoop(): IEventLoop
	{
		return Prado::createComponent($this->getEventLoopClass());
	}

	/**
	 * Runs the loop until the server stops or {@see stop()} is requested.  The loop watches the
	 * server for acceptable connections ({@see acceptClient()}) and each accepted connection for
	 * readable data ({@see dispatchClient()}).  A repeating timer delivers the signals caught in
	 * sync mode and stops the loop once the server no longer listens.  Subclasses override this when
	 * the server owns its own loop; such an override makes that loop the
	 * {@see TAsync::setLoop() TAsync loop}, so fibers its handlers start resume.
	 * @param TSocketServer $server The listening server.
	 */
	protected function serveLoop(TSocketServer $server): void
	{
		$loop = $this->getEventLoop();
		$this->_acceptCallbackId = $loop->onReadable($server, fn () => $this->acceptClient($server, $loop));
		$pumpId = $loop->repeat(static::SERVE_PUMP_INTERVAL, function () use ($server, $loop): void {
			// Deliver any signal caught during the wait when the dispatcher runs in sync mode; a
			// no-op in async mode and without pcntl, so the loop is correct under either mode.
			TSignalsDispatcher::syncDispatch();
			if ($this->_stopping || !$server->isListening()) {
				$loop->stop();
			}
		});
		try {
			$loop->run();
		} finally {
			$loop->cancel($pumpId);
			$this->cancelAcceptCallback();
			foreach ($this->_connectionCallbackIds as $id) {
				$loop->cancel($id);
			}
			$this->_connectionCallbackIds = [];
		}
	}

	/**
	 * Accepts one pending connection, sets it non-blocking, and watches it on the loop for readable
	 * data.  Raises {@see onClientConnect}.
	 * @param TSocketServer $server The listening server.
	 * @param IEventLoop $loop The loop watching the server.
	 */
	protected function acceptClient(TSocketServer $server, IEventLoop $loop): void
	{
		if (($connection = $server->accept(0.0)) === null) {
			return;
		}
		$connection->setBlocking(false);
		$this->onClientConnect($connection);
		$this->_connectionCallbackIds[spl_object_id($connection)] = $loop->onReadable($connection, fn () => $this->dispatchClient($connection, $loop));
	}

	/**
	 * Stops watching a connection on the loop.
	 * @param TSocketStream $connection The connection.
	 * @param IEventLoop $loop The loop watching the connection.
	 */
	protected function forgetConnection(TSocketStream $connection, IEventLoop $loop): void
	{
		$key = spl_object_id($connection);
		if (isset($this->_connectionCallbackIds[$key])) {
			$loop->cancel($this->_connectionCallbackIds[$key]);
			unset($this->_connectionCallbackIds[$key]);
		}
	}

	/**
	 * Stops watching the listening server on the loop.
	 */
	private function cancelAcceptCallback(): void
	{
		if ($this->_acceptCallbackId !== null) {
			$this->_eventLoop?->cancel($this->_acceptCallbackId);
			$this->_acceptCallbackId = null;
		}
	}

	/**
	 * Dispatches a readable connection.  A one-byte peek distinguishes pending data from a closed
	 * connection: a peek of `''` is an orderly end of stream (the peer sent FIN) and a peek of `false`
	 * is a broken or reset connection.  Either one stops watching the connection, raises it through
	 * {@see onClientDisconnect}, and closes it.  A peek that returns data hands the connection to
	 * {@see onClientData}; a handler that closes the connection ends the watch too.  The peek reads
	 * the raw transport, so a TLS subclass that buffers decrypted bytes overrides this.
	 * @param TSocketStream $connection The readable connection.
	 * @param IEventLoop $loop The loop watching the connection.
	 */
	protected function dispatchClient(TSocketStream $connection, IEventLoop $loop): void
	{
		$peek = $connection->recvFrom(1, STREAM_PEEK);
		if ($peek === '' || $peek === false) {
			$this->forgetConnection($connection, $loop);
			$this->onClientDisconnect($connection);
			$connection->close();
			return;
		}
		$this->onClientData($connection);
		if (!$connection->isOpen()) {
			$this->forgetConnection($connection, $loop);
		}
	}

	/**
	 * Runs the socket server: binds the endpoint, subscribes to the signals dispatcher, and serves
	 * until stopped by a SIGTERM/SIGINT signal or the server closing.  The
	 * {@see getEventLoop() EventLoop} is the {@see TAsync::getLoop() TAsync loop} while the server
	 * serves.  Open connections are closed and the prior TAsync loop is restored on exit.
	 * @throws TConfigurationException When the endpoint is unconfigured or names a datagram scheme.
	 * @return bool Whether the server ran (false when the permission is denied).
	 */
	public function serve(): bool
	{
		if ($this->dyServe(false) === true) {
			return false;
		}
		$this->_stopping = false;
		@set_time_limit(0);
		$this->_server = $this->createServer();
		$listened = false;
		if (TSignalsDispatcher::hasSignals()) {
			TSignalsDispatcher::singleton();
			if (!$this->getListeningToGlobalEvents()) {
				$this->listen();
				$listened = true;
			}
		}
		$priorLoop = TAsync::getLoop();
		TAsync::setLoop($this->getEventLoop());
		try {
			$this->serveLoop($this->_server);
		} finally {
			TAsync::setLoop($priorLoop);
			foreach ($this->_server->getConnections() as $connection) {
				$connection->close();
			}
			$this->_server->close();
			$this->_server = null;
			if ($listened) {
				$this->unlisten();
			}
		}
		return true;
	}

	/**
	 * Asks the serve loop to stop and closes the listening server.  Invoked by the SIGTERM/SIGINT
	 * signal events ({@see fxSignalTerminate}/{@see fxSignalInterrupt}) and callable directly.
	 */
	public function stop(): void
	{
		$this->_stopping = true;
		$this->cancelAcceptCallback();
		if ($this->_server !== null) {
			$this->_server->close();
			$this->_eventLoop?->stop();
		}
	}

	/**
	 * Stops the server on SIGTERM and clears the exit so {@see serve()} unwinds and closes cleanly.
	 * @param object $sender The signals dispatcher.
	 * @param TSignalParameter $param The signal parameter.
	 */
	public function fxSignalTerminate($sender, TSignalParameter $param): void
	{
		$param->setIsExiting(false);
		$this->stop();
	}

	/**
	 * Stops the server on SIGINT (CTRL-C) and clears the exit so {@see serve()} unwinds and closes
	 * cleanly.
	 * @param object $sender The signals dispatcher.
	 * @param TSignalParameter $param The signal parameter.
	 */
	public function fxSignalInterrupt($sender, TSignalParameter $param): void
	{
		$param->setIsExiting(false);
		$this->stop();
	}

	/**
	 * Returns the running server while it serves.
	 * @return ?TSocketServer The running server, or null when not serving.
	 */
	public function getServer(): ?TSocketServer
	{
		return $this->_server;
	}

	/**
	 * Returns the TSocketServer class the server is created from.
	 * @return string The TSocketServer class name.
	 */
	public function getServerClass(): string
	{
		return $this->_serverClass;
	}

	/**
	 * Sets the TSocketServer class the server is created from.
	 * @param string $value A TSocketServer class name.
	 * @throws TConfigurationException When the class is not a TSocketServer.
	 * @return static The current module.
	 */
	public function setServerClass($value): static
	{
		$value = TPropertyValue::ensureString($value);
		if (!is_a($value, TSocketServer::class, true)) {
			throw new TConfigurationException('socketservermodule_serverclass_invalid', $value);
		}
		$this->_serverClass = $value;
		return $this;
	}

	/**
	 * Returns the loop the server is served on, creating the default from
	 * {@see getEventLoopClass() EventLoopClass} on first use.
	 * @return IEventLoop The loop.
	 */
	public function getEventLoop(): IEventLoop
	{
		if ($this->_eventLoop === null) {
			$this->_eventLoop = $this->createEventLoop();
		}
		return $this->_eventLoop;
	}

	/**
	 * Sets the loop the server is served on.
	 * @param ?IEventLoop $value The loop, or null to create the default on the next {@see getEventLoop()}.
	 * @return static The current module.
	 */
	public function setEventLoop(?IEventLoop $value): static
	{
		$this->_eventLoop = $value;
		return $this;
	}

	/**
	 * Returns the IEventLoop class the default loop is created from.
	 * @return string The IEventLoop class name, default {@see DEFAULT_EVENT_LOOP_CLASS}.
	 */
	public function getEventLoopClass(): string
	{
		return $this->_eventLoopClass;
	}

	/**
	 * Sets the IEventLoop class the default loop is created from.
	 * @param string $value An IEventLoop class name, such as {@see \Prado\IO\Socket\TSocketReactor}.
	 * @throws TConfigurationException When the class is not an IEventLoop.
	 * @return static The current module.
	 */
	public function setEventLoopClass($value): static
	{
		$value = TPropertyValue::ensureString($value);
		if (!is_a($value, IEventLoop::class, true)) {
			throw new TConfigurationException('socketservermodule_eventloopclass_invalid', $value);
		}
		$this->_eventLoopClass = $value;
		return $this;
	}

	/**
	 * Returns the URI scheme of the listening endpoint.
	 * @return string The URI scheme, default {@see DEFAULT_SCHEME}.
	 */
	public function getScheme(): string
	{
		return $this->_scheme;
	}

	/**
	 * Sets the URI scheme of the listening endpoint.  A stream scheme is required, as the server
	 * accepts connections.
	 * @param string $value The URI scheme, e.g. {@see TUriScheme::TCP}, {@see TUriScheme::TLS},
	 *   {@see TUriScheme::SSL}, or {@see TUriScheme::UNIX}.
	 * @throws TConfigurationException When the scheme is connectionless (datagram).
	 * @return static The current module.
	 */
	public function setScheme($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->assertStreamScheme($value);
		$this->_scheme = $value;
		return $this;
	}

	/**
	 * Asserts a scheme is connection-oriented, so the server can accept on it.
	 * @param string $scheme The URI scheme to test.
	 * @throws TConfigurationException When the scheme is connectionless (datagram).
	 */
	protected function assertStreamScheme(string $scheme): void
	{
		if (in_array(strtolower($scheme), self::DATAGRAM_SCHEMES, true)) {
			throw new TConfigurationException('socketservermodule_scheme_not_stream', $scheme);
		}
	}

	/**
	 * Returns the address the server binds to.
	 * @return string The bind address, default {@see DEFAULT_ADDRESS}.
	 */
	public function getAddress(): string
	{
		return $this->_address;
	}

	/**
	 * Sets the address the server binds to.  An explicit {@see setEndpoint() Endpoint} takes
	 * precedence over the composed scheme/address/port.
	 * @param string $value The bind address.
	 * @return static The current module.
	 */
	public function setAddress($value): static
	{
		$this->_address = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Returns the port the server binds to.
	 * @return int The bind port, default {@see DEFAULT_PORT}.
	 */
	public function getPort(): int
	{
		return $this->_port;
	}

	/**
	 * Sets the port the server binds to.  An explicit {@see setEndpoint() Endpoint} takes precedence
	 * over the composed scheme/address/port.
	 * @param int|string $value The bind port.
	 * @return static The current module.
	 */
	public function setPort($value): static
	{
		$this->_port = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * Returns the listening endpoint URI.  When not set explicitly, it is composed from
	 * {@see getScheme() Scheme}, {@see getAddress() Address}, and {@see getPort() Port}.
	 * @throws TConfigurationException When no explicit endpoint is set and the port is not positive.
	 * @return string The listening endpoint URI.
	 */
	public function getEndpoint(): string
	{
		if ($this->_endpoint !== null) {
			return $this->_endpoint;
		}
		if ($this->_port <= 0) {
			throw new TConfigurationException('socketservermodule_endpoint_required');
		}
		return $this->_scheme . '://' . $this->_address . ':' . $this->_port;
	}

	/**
	 * Sets the listening endpoint URI explicitly, overriding scheme/address/port.
	 * @param ?string $value The endpoint URI, e.g. 'tcp://0.0.0.0:8080', 'unix:///tmp/app.sock'.
	 * @return static The current module.
	 */
	public function setEndpoint($value): static
	{
		$this->_endpoint = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Returns the stream context options for the listening socket.
	 * @return TMap The stream context options.
	 */
	public function getSocketOptions(): TMap
	{
		if ($this->_socketOptions === null) {
			$this->_socketOptions = new TMap();
		}
		return $this->_socketOptions;
	}

	/**
	 * Raised when a connection is accepted.
	 * @param mixed $param The accepted {@see TSocketStream}.
	 */
	public function onClientConnect(mixed $param): void
	{
		$this->raiseEvent('onClientConnect', $this, $param);
	}

	/**
	 * Raised when an accepted connection has data ready to read.
	 * @param mixed $param The readable {@see TSocketStream}.
	 */
	public function onClientData(mixed $param): void
	{
		$this->raiseEvent('onClientData', $this, $param);
	}

	/**
	 * Raised when an accepted connection reaches end of stream, before it is closed.
	 * @param mixed $param The {@see TSocketStream} at end of stream.
	 */
	public function onClientDisconnect(mixed $param): void
	{
		$this->raiseEvent('onClientDisconnect', $this, $param);
	}
}
