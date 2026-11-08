<?php

/**
 * TCronWakeSocket class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\TComponent;

/**
 * TCronWakeSocket class.
 *
 * TCronWakeSocket is the same-host wake transport for {@see \Prado\Util\Cron\TCronModule}. A cron
 * process running in second-resolution sleeps in {@see wait()} until a task's trigger time; a web
 * request that schedules an earlier task sends a datagram with {@see signal()} to wake it immediately
 * so the new task runs on time instead of after the remaining sleep.
 *
 * The transport is a connectionless datagram socket: a unix-domain datagram (`udg`) when available,
 * otherwise a `udp` loopback socket, decided by {@see getTransport()}. When neither is available the
 * methods report "no transport" so the caller falls back to a plain sleep.
 *
 * The socket is keyed by the **parity** of the serviced minute — even minutes and odd minutes use
 * different addresses. The only realistic overlap is a task that overruns into the next minute, and
 * adjacent minutes always have opposite parity, so the overrunning process and the new minute's
 * process never contend for the same socket.
 *
 * The socket stays **bound for the whole serviced minute** (across every {@see wait()} and the task
 * executions between them), and is released by {@see close()} when the minute is done. Persistent
 * binding means a datagram sent while the process is busy — between waits, mid-task, or in the gap
 * between a re-poll and the next wait — is **buffered** in the socket's receive queue and consumed by
 * the next {@see wait()}, instead of being dropped. A task overrunning more than two minutes can leave
 * a same-parity successor unable to bind; that successor falls back to a plain sleep for that minute.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeSocket extends TComponent
{
	/** @var int the default base udp loopback port; even minutes use this, odd minutes use this + 1 */
	public const DEFAULT_WAKE_PORT = 52800;

	/** @var string the udg socket file name prefix in the runtime directory */
	public const SOCKET_PREFIX = 'cronwake-';

	/** @var string the runtime directory holding the udg socket files */
	private string $_runtimePath;

	/** @var int the base udp loopback port */
	private int $_basePort;

	/** @var null|false|string the resolved transport: false until detected, then 'udg', 'udp', or null */
	private $_transport = false;

	/** @var null|resource the persistently bound datagram server socket, or null when not bound */
	private $_server;

	/** @var ?int the serviced minute the bound socket is for */
	private ?int $_serverMinute = null;

	/** @var ?string the address the bound socket is on */
	private ?string $_serverAddress = null;

	/**
	 * @param string $runtimePath the runtime directory for udg socket files
	 * @param int $basePort the base udp loopback port (even minute uses it, odd uses it + 1)
	 */
	public function __construct(string $runtimePath, int $basePort = self::DEFAULT_WAKE_PORT)
	{
		$this->_runtimePath = rtrim($runtimePath, '/\\');
		$this->_basePort = $basePort;
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
		$suffix = ($minute % 2) ? 'odd' : 'even';
		return match ($this->getTransport()) {
			'udg' => 'udg://' . $this->_runtimePath . DIRECTORY_SEPARATOR . self::SOCKET_PREFIX . $suffix . '.sock',
			'udp' => 'udp://127.0.0.1:' . ($this->_basePort + ($minute % 2)),
			default => null,
		};
	}

	/**
	 * Waits until the given timestamp, returning early when a wake datagram arrives. The parity socket
	 * for the current minute is bound on first use and kept bound (see {@see ensureBound}), so a datagram
	 * that arrived while the process was busy is buffered and consumed here rather than dropped. On wake
	 * all queued datagrams are drained. The socket is released by {@see close()} at the end of the minute.
	 * @param float $until the Unix timestamp to wait until
	 * @param int $minute the serviced minute (floor(timestamp / 60))
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a wake datagram arrived, false when the wait timed out, null when no socket
	 * could be bound so the caller should fall back to a plain sleep
	 */
	public function wait(float $until, int $minute, float $now): ?bool
	{
		if (!$this->ensureBound($minute)) {
			return null;
		}
		$remaining = $until - $now;
		if ($remaining <= 0) {
			return $this->drain(); // a buffered kick still counts as a wake
		}
		$sec = (int) $remaining;
		$usec = (int) (($remaining - $sec) * 1000000);
		$read = [$this->_server];
		$write = $except = [];
		$count = @stream_select($read, $write, $except, $sec, $usec);
		if ($count > 0) {
			$this->drain();
			return true;
		}
		return false;
	}

	/**
	 * Ensures the parity socket for the given minute is bound, rebinding when the minute changed. Holds
	 * the socket across waits and task executions so datagrams are buffered rather than dropped.
	 * @param int $minute the serviced minute (floor(timestamp / 60))
	 * @return bool whether a socket is bound for the minute
	 */
	protected function ensureBound(int $minute): bool
	{
		if ($this->_server !== null && $this->_serverMinute === $minute) {
			return true;
		}
		$this->close();
		$address = $this->getAddress($minute);
		if ($address === null) {
			return false;
		}
		$server = $this->bind($address);
		if ($server === null) {
			return false;
		}
		$this->_server = $server;
		$this->_serverMinute = $minute;
		$this->_serverAddress = $address;
		return true;
	}

	/**
	 * Releases the persistently bound socket and removes its udg file. Called at the end of the serviced
	 * minute so nothing is held into the next minute.
	 */
	public function close(): void
	{
		if ($this->_server !== null) {
			$this->release($this->_server, $this->_serverAddress);
			$this->_server = null;
			$this->_serverMinute = null;
			$this->_serverAddress = null;
		}
	}

	/**
	 * Consumes all datagrams queued on the bound socket without blocking.
	 * @return bool whether at least one datagram was drained
	 */
	protected function drain(): bool
	{
		$drained = false;
		$write = $except = [];
		$read = [$this->_server];
		while (@stream_select($read, $write, $except, 0, 0) > 0) {
			@stream_socket_recvfrom($this->_server, 65535);
			$drained = true;
			$read = [$this->_server];
			$write = $except = [];
		}
		return $drained;
	}

	/**
	 * Sends a wake datagram to the given minute's parity socket. A missing listener (cron not currently
	 * sleeping) is a harmless no-op.
	 * @param int $minute the serviced minute (floor(timestamp / 60))
	 * @return bool whether a datagram was sent
	 */
	public function signal(int $minute): bool
	{
		$address = $this->getAddress($minute);
		if ($address === null) {
			return false;
		}
		$client = @stream_socket_client($address, $errno, $errstr, 0, STREAM_CLIENT_CONNECT);
		if ($client === false) {
			return false;
		}
		@fwrite($client, '1');
		fclose($client);
		return true;
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
		return ($server === false) ? null : $server;
	}

	/**
	 * Closes the server socket and removes its udg file so nothing is held into the next minute.
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
