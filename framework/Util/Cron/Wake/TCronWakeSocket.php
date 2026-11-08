<?php

/**
 * TCronWakeSocket class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\IO\Async\IEventLoop;
use Prado\Prado;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCronWakeSocket class.
 *
 * TCronWakeSocket is the same-host {@see \Prado\Util\Cron\Wake\ICronWakeTransport} of
 * {@see \Prado\Util\Cron\TCronModule}. A cron process waiting for a task's trigger time wakes at
 * once when a web request that schedules an earlier task sends a datagram with {@see signal()}.
 *
 * The transport is a connectionless datagram socket: a unix-domain datagram (`udg`) when available,
 * otherwise a `udp` loopback socket, decided by {@see getTransport()}. When neither is available the
 * methods report "no transport" so the caller falls back to a plain sleep.
 *
 * A cron daemon ({@see watch()}) listens on one address, {@see getDaemonAddress()}. A per-minute cron
 * process ({@see wait()}) listens on an address keyed by the **parity** of the serviced minute: even
 * minutes and odd minutes use different addresses, so a process overrunning into the next minute
 * never contends with the next minute's process. {@see signal()} sends to the daemon address and the
 * current minute's parity address, so either kind of listener wakes.
 *
 * A bound socket stays bound across waits, so a datagram sent while the process is busy is buffered
 * in the receive queue and consumed by the next wait instead of being dropped. {@see close()} releases
 * it; a per-minute process closes at the end of its minute so nothing is held into the next.
 *
 * Any process of the host can send a datagram to a loopback or `udg` address, so a wake is a signed
 * message ({@see createToken}): a fresh nonce and the time, authenticated with an HMAC of the
 * {@see getKey Key}, the application's validation key by default. The receiver ({@see verifyToken})
 * accepts a message once, within {@see TOKEN_WINDOW} seconds of its time, so a captured datagram
 * cannot be replayed and a datagram without a valid signature is read and ignored. A `udg` socket
 * file is also owner-only.
 *
 * The socket is the default transport of {@see \Prado\Util\Cron\TCronModule}; as a module it
 * configures {@see setRuntimePath RuntimePath} and {@see setBasePort BasePort}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeSocket extends TModule implements ICronWakeTransport
{
	/** @var int the default base udp loopback port; even minutes use this, odd minutes this + 1, the daemon this + 2 */
	public const DEFAULT_WAKE_PORT = 52800;

	/** @var string the udg socket file name prefix in the runtime directory */
	public const SOCKET_PREFIX = 'cronwake-';

	/** @var int the seconds a wake message stays valid after its time; a nonce is remembered for as long */
	public const TOKEN_WINDOW = 30;

	/** @var int the bytes of the random nonce in a wake message */
	protected const NONCE_BYTES = 8;

	/** @var ?string the runtime directory holding the udg socket files, or null for the application runtime path */
	private ?string $_runtimePath;

	/** @var ?int the base udp loopback port; null for {@see DEFAULT_WAKE_PORT} */
	private ?int $_basePort;

	/** @var ?string the signing key of the wake messages, or null to derive it from the application's validation key */
	private ?string $_key = null;

	/** @var array<string, int> the nonces of the accepted messages, by their expiry time */
	private array $_seenNonces = [];

	/** @var null|false|string the resolved transport: false until detected, then 'udg', 'udp', or null */
	private $_transport = false;

	/** @var null|resource the persistently bound datagram server socket, or null when not bound */
	private $_server;

	/** @var ?string the address the bound socket is on */
	private ?string $_serverAddress = null;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/**
	 * @param ?string $runtimePath the runtime directory for udg socket files, or null for the application runtime path
	 * @param ?int $basePort the base udp loopback port (even minute uses it, odd uses it + 1, the daemon + 2), or null for {@see DEFAULT_WAKE_PORT}
	 */
	public function __construct(?string $runtimePath = null, ?int $basePort = null)
	{
		$this->_runtimePath = ($runtimePath === null) ? null : rtrim($runtimePath, '/\\');
		$this->_basePort = $basePort;
		parent::__construct();
	}

	/**
	 * Initializes the module; an unset {@see getBasePort BasePort} takes {@see DEFAULT_WAKE_PORT} through `static::`,
	 * so a subclass overrides the default by redefining the constant.
	 * @param null|array|\Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		$this->_basePort ??= (int) static::DEFAULT_WAKE_PORT;
		parent::init($config);
	}

	/**
	 * @return string the runtime directory holding the udg socket files; defaults to the application runtime path
	 */
	public function getRuntimePath(): string
	{
		if ($this->_runtimePath === null) {
			$this->_runtimePath = rtrim(Prado::getApplication()->getRuntimePath(), '/\\');
		}
		return $this->_runtimePath;
	}

	/**
	 * @param string $value the runtime directory holding the udg socket files, as a path or a namespace
	 * @return static the current object
	 */
	public function setRuntimePath($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->_runtimePath = rtrim(Prado::getPathOfNamespace($value) ?? $value, '/\\');
		return $this;
	}

	/**
	 * @return int the base udp loopback port, default {@see DEFAULT_WAKE_PORT}; even minutes use it, odd minutes the next, the daemon the one after
	 */
	public function getBasePort(): int
	{
		return $this->_basePort ?? (int) static::DEFAULT_WAKE_PORT;
	}

	/**
	 * @param int|string $value the base udp loopback port
	 * @return static the current object
	 */
	public function setBasePort($value): static
	{
		$this->_basePort = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * Returns the key that signs the wake messages.  Without a configured key it is the SHA-256 HMAC
	 * of `prado:cron:wake` with the application's `TSecurityManager` validation key, which every
	 * process of the application shares; without an application the key is the runtime path.
	 * @return string the signing key
	 */
	public function getKey(): string
	{
		if ($this->_key === null) {
			$key = null;
			try {
				$key = Prado::getApplication()?->getSecurityManager()?->getValidationKey();
			} catch (\Throwable $e) {
				// no security manager; fall back to the runtime path
			}
			$this->_key = hash_hmac('sha256', 'prado:cron:wake', (string) ($key ?: $this->getRuntimePath()));
		}
		return $this->_key;
	}

	/**
	 * @param ?string $value the key that signs the wake messages, or null or '' to derive it from the application's validation key
	 * @return static the current object
	 */
	public function setKey($value): static
	{
		$this->_key = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Creates a wake message: the current time, a random nonce, and their SHA-256 HMAC with the
	 * {@see getKey Key}, as `time:nonce:signature`.  Every message is different, so none replays.
	 * @return string the signed wake message
	 */
	public function createToken(): string
	{
		$payload = time() . ':' . bin2hex(random_bytes(static::NONCE_BYTES));
		return $payload . ':' . hash_hmac('sha256', $payload, $this->getKey());
	}

	/**
	 * Verifies a wake message: its signature with the {@see getKey Key}, its time within
	 * {@see TOKEN_WINDOW} seconds of now, and a nonce not accepted before.  An accepted nonce is
	 * remembered until the message would have expired.
	 * @param string $data the received datagram
	 * @return bool whether the message is a fresh, authentic wake
	 */
	public function verifyToken(string $data): bool
	{
		$parts = explode(':', $data);
		if (count($parts) !== 3) {
			return false;
		}
		[$time, $nonce, $signature] = $parts;
		if (!hash_equals(hash_hmac('sha256', $time . ':' . $nonce, $this->getKey()), $signature)) {
			return false;
		}
		$now = time();
		foreach ($this->_seenNonces as $seen => $expiry) {
			if ($expiry <= $now) {
				unset($this->_seenNonces[$seen]);
			}
		}
		if (!ctype_digit($time) || abs($now - (int) $time) > static::TOKEN_WINDOW || isset($this->_seenNonces[$nonce])) {
			return false;
		}
		$this->_seenNonces[$nonce] = $now + static::TOKEN_WINDOW;
		return true;
	}

	/**
	 * Resolves the datagram transport once: `udg` when supported, otherwise `udp`, otherwise null.
	 * @return ?string 'udg', 'udp', or null when neither datagram transport is available
	 */
	public function getTransport(): ?string
	{
		if ($this->_transport === false) {
			$transports = @stream_get_transports() ?: [];
			$this->_transport = in_array('udg', $transports, true) ? 'udg' : (in_array('udp', $transports, true) ? 'udp' : null);
		}
		return $this->_transport;
	}

	/**
	 * Returns the datagram address for the given minute's parity, or null when no transport is available.
	 * @param int $minute the serviced minute (floor(timestamp / 60))
	 * @return ?string the stream socket address (udg path or udp host:port), or null
	 */
	public function getAddress(int $minute): ?string
	{
		return $this->addressFor(($minute % 2) ? 'odd' : 'even', $this->getBasePort() + ($minute % 2));
	}

	/**
	 * Returns the datagram address a cron daemon listens on, or null when no transport is available.
	 * @return ?string the stream socket address (udg path or udp host:port), or null
	 */
	public function getDaemonAddress(): ?string
	{
		return $this->addressFor('daemon', $this->getBasePort() + 2);
	}

	/**
	 * Builds the stream socket address of a listener.
	 * @param string $suffix the udg socket file suffix
	 * @param int $port the udp loopback port
	 * @return ?string the address, or null when no transport is available
	 */
	protected function addressFor(string $suffix, int $port): ?string
	{
		return match ($this->getTransport()) {
			'udg' => 'udg://' . $this->getRuntimePath() . DIRECTORY_SEPARATOR . static::SOCKET_PREFIX . $suffix . '.sock',
			'udp' => 'udp://127.0.0.1:' . $port,
			default => null,
		};
	}

	/**
	 * Waits until the given timestamp on the current minute's parity socket, returning early when a
	 * wake datagram arrives. The socket is bound on first use and kept bound (see {@see ensureBound}),
	 * so a datagram that arrived while the process was busy is consumed here rather than dropped. On
	 * wake all queued datagrams are drained.
	 * A datagram that is not a fresh signed wake ({@see verifyToken}) is ignored and the wait goes on.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a wake datagram arrived, false when the wait timed out, null when no socket
	 * could be bound so the caller should fall back to a plain sleep
	 */
	public function wait(float $until, float $now): ?bool
	{
		$address = $this->getAddress((int) floor($now / 60));
		if ($address === null || !$this->ensureBound($address)) {
			return null;
		}
		$start = \microtime(true);
		$remaining = $until - $now;
		do {
			if ($remaining > 0) {
				$sec = (int) $remaining;
				$usec = (int) (($remaining - $sec) * 1000000);
				$read = [$this->_server];
				$write = $except = [];
				if (@stream_select($read, $write, $except, $sec, $usec) < 1) {
					return false;
				}
			}
			if ($this->drain()) {
				return true; // a buffered wake counts as well
			}
			$remaining = ($until - $now) - (\microtime(true) - $start);
		} while ($remaining > 0);
		return false;
	}

	/**
	 * Binds the daemon address and delivers each wake datagram to the callback from the loop.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each wake
	 * @return bool whether the socket is bound and watched
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		$address = $this->getDaemonAddress();
		if ($address === null || !$this->ensureBound($address)) {
			return false;
		}
		$this->unwatch();
		$this->_loop = $loop;
		$this->_watcher = $loop->onReadable($this->_server, function () use ($onWake) {
			if ($this->drain()) {
				$onWake();
			}
		});
		return true;
	}

	/**
	 * Cancels the loop callback of {@see watch()}; the socket stays bound until {@see close()}.
	 */
	public function unwatch(): void
	{
		if ($this->_watcher !== null && $this->_loop !== null) {
			$this->_loop->cancel($this->_watcher);
		}
		$this->_watcher = null;
		$this->_loop = null;
	}

	/**
	 * Ensures the server socket is bound on the given address, rebinding when the address changed.
	 * Holds the socket across waits and task executions so datagrams are buffered rather than dropped.
	 * @param string $address the stream socket address
	 * @return bool whether a socket is bound on the address
	 */
	protected function ensureBound(string $address): bool
	{
		if ($this->_server !== null && $this->_serverAddress === $address) {
			return true;
		}
		$this->close();
		$server = $this->bind($address);
		if ($server === null) {
			return false;
		}
		$this->_server = $server;
		$this->_serverAddress = $address;
		return true;
	}

	/**
	 * Cancels the watcher and releases the bound socket, removing its udg file. A per-minute process
	 * calls this at the end of the serviced minute so nothing is held into the next minute.
	 */
	public function close(): void
	{
		$this->unwatch();
		if ($this->_server !== null) {
			$this->release($this->_server, $this->_serverAddress);
			$this->_server = null;
			$this->_serverAddress = null;
		}
	}

	/**
	 * Consumes all datagrams queued on the bound socket without blocking.
	 * @return bool whether at least one of them was a fresh signed wake ({@see verifyToken})
	 */
	protected function drain(): bool
	{
		$valid = false;
		$write = $except = [];
		$read = [$this->_server];
		while (@stream_select($read, $write, $except, 0, 0) > 0) {
			$data = @stream_socket_recvfrom($this->_server, 65535);
			if (is_string($data) && $this->verifyToken($data)) {
				$valid = true;
			}
			$read = [$this->_server];
			$write = $except = [];
		}
		return $valid;
	}

	/**
	 * Sends a signed wake message ({@see createToken}) to the daemon address and to the current minute's
	 * parity address. A missing listener (no cron waiting) is a harmless no-op.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether a datagram was sent
	 */
	public function signal(float $now): bool
	{
		$sent = false;
		foreach ([$this->getDaemonAddress(), $this->getAddress((int) floor($now / 60))] as $address) {
			if ($address !== null && $this->send($address)) {
				$sent = true;
			}
		}
		return $sent;
	}

	/**
	 * Sends one wake datagram to an address.
	 * @param string $address the stream socket address
	 * @return bool whether the datagram was sent
	 */
	protected function send(string $address): bool
	{
		$client = @stream_socket_client($address, $errno, $errstr, 0, STREAM_CLIENT_CONNECT);
		if ($client === false) {
			return false;
		}
		$token = $this->createToken();
		$sent = @fwrite($client, $token) === strlen($token);
		fclose($client);
		return $sent;
	}

	/**
	 * Binds the datagram server socket, clearing a crashed predecessor's stale udg file first.
	 * @param string $address the stream socket address to bind
	 * @return null|resource the bound server socket, or null on failure
	 */
	protected function bind(string $address)
	{
		if (str_starts_with($address, 'udg://')) {
			@unlink(substr($address, 6));
		}
		$server = @stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND);
		if ($server !== false && str_starts_with($address, 'udg://')) {
			@chmod(substr($address, 6), 0o600);
		}
		return ($server === false) ? null : $server;
	}

	/**
	 * Closes the server socket and removes its udg file.
	 * @param resource $server the bound server socket
	 * @param string $address the bound stream socket address
	 */
	protected function release($server, string $address): void
	{
		if (is_resource($server)) {
			fclose($server);
		}
		if (str_starts_with($address, 'udg://')) {
			@unlink(substr($address, 6));
		}
	}
}
