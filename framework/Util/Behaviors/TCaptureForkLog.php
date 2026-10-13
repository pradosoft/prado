<?php

/**
 * TCaptureForkLog class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\{TBehavior, IBaseBehavior};
use Prado\Util\TCallChain;
use Prado\Util\Log\TLogger;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TCaptureForkLog class.
 *
 * TCaptureForkLog sends the logs of a forked child process to its parent process.
 *
 * {@see \Prado\Util\Helpers\TProcessHelper::fork()} raises `fxPrepareForFork`
 * before the fork and `fxRestoreAfterFork` after the fork.
 * - On `fxPrepareForFork`, {@see self::generateConnection()} creates a socket pair.
 * - On `fxRestoreAfterFork`, {@see self::configureForChildLogs()} gives the parent
 *   end to the parent and the child end to the child.
 *
 * The parent receives the child logs on TLogger::onCollectLogs through
 * {@see self::collectLogsFromChildren()}.  The child replaces its TLogger::onFlushLogs
 * handlers with {@see self::sendLogsToParent()}.  TLogger::onFlushLogs raises
 * onCollectLogs first, so a child sends the logs of its own children with its own.
 *
 * | Collection | Child sends | Parent receives |
 * |---|---|---|
 * | Not final | its unlogged logs | the messages already sent |
 * | Final | its last logs, then closes the connection | waits for the final message of every child |
 *
 * Capturing requires the sockets extension.  Without it, the child logs are not captured.
 *
 * Attach this behavior to the TApplication class or object.  `TProcessHelper::fork(true)`
 * attaches it to the application.
 * ```xml
 *		<behavior name="captureforklog" AttachToClass="Prado\TApplication" class="Prado\Util\Behaviors\TCaptureForkLog" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TCaptureForkLog extends \Prado\Util\TBehavior
{
	public const BEHAVIOR_NAME = 'captureforklog';

	/** @var bool Is the master receiver (of child logs) installed. */
	private bool $_receiverInstalled = false;

	/** @var ?array The parent connections to each child fork receiving logs from. */
	protected ?array $_parentConnections = [];

	/** @var mixed The child connection to the parent */
	protected mixed $_childConnection = null;

	/** @var array The received bytes of an incomplete message, by child process ID. */
	private array $_receiveBuffers = [];

	/** @var ?array The socket pair of the pending fork, [parent end, child end]. */
	private ?array $_forkConnection = null;

	/**
	 * Installs {@see self::generateConnection()} on fxPrepareForFork and
	 * {@see self::configureForChildLogs()} on fxRestoreAfterFork.
	 * @return array Event callbacks for the behavior.
	 */
	public function events()
	{
		return [TProcessHelper::FX_PREPARE_FOR_FORK => 'generateConnection',
				TProcessHelper::FX_RESTORE_AFTER_FORK => 'configureForChildLogs'];
	}

	/**
	 * Returns the priority of the behavior and its event handlers.
	 * @return ?float The priority of the behavior, default -10 and not
	 *   the normal "null".
	 */
	public function getPriority(): ?float
	{
		if (($priority = parent::getPriority()) === null) {
			$priority = -10;
		}
		return $priority;
	}

	/**
	 * This is the singleton behavior.  Only one instance of itself can be a behavior
	 * of the owner.
	 * @param string $name The name of the behavior being added to the owner.
	 * @param IBaseBehavior $behavior The behavior being added to the owner.
	 * @param TCallChain $chain The chain of event handlers.
	 */
	public function dyAttachBehavior($name, $behavior, TCallChain $chain)
	{
		$owner = $this->getOwner();
		if (count($owner->getBehaviors(self::class)) > 1) {
			$owner->detachBehavior($name);
		}
		return $chain->dyAttachBehavior($name, $behavior);
	}

	/**
	 * The behavior callback for fxPrepareForFork that creates a socket pair connection
	 * between the parent process and child process before forking.  Without the
	 * sockets extension, no connection is created and the child logs are not captured.
	 * @param mixed $sender The TApplication doing the fork.
	 * @param mixed $param The parameter of fxPrepareForFork.
	 * @return ?array Any data to be passed back to the restore function.
	 *   eg. `return ['key', 'data'];` will be passed in the restore function as
	 *   `['key' => 'data', 'pid' => ###, ...]`.
	 */
	public function generateConnection(mixed $sender, mixed $param)
	{
		$this->_forkConnection = null;
		if (!function_exists('socket_create_pair')) {
			return null;
		}
		$domain = TProcessHelper::isSystemWindows() ? AF_INET : AF_UNIX;
		if (!socket_create_pair($domain, SOCK_STREAM, 0, $pair)) {
			return null;
		}
		$this->_forkConnection = [socket_export_stream($pair[0]), socket_export_stream($pair[1])];
		return null;
	}

	/**
	 * The behavior callback for fxRestoreAfterFork that routes the child logs to
	 * the parent.
	 * - Parent: stores the child connection by pid and installs
	 *   {@see self::collectLogsFromChildren()} on TLogger::onCollectLogs.
	 * - Child: deletes the inherited logs and replaces the TLogger::onFlushLogs
	 *   handlers with {@see self::sendLogsToParent()}.
	 * - Failed fork: shuts down the socket pair.
	 * @param mixed $sender The TApplication doing the fork.
	 * @param mixed $data The fxRestoreAfterFork data, with the fork result in 'pid'.
	 */
	public function configureForChildLogs(mixed $sender, mixed $data)
	{
		if (!$this->_forkConnection) {
			return;
		}
		$connection = $this->_forkConnection;
		$this->_forkConnection = null;
		$pid = $data['pid'];
		if ($pid === -1) { //fail
			stream_socket_shutdown($connection[0], STREAM_SHUT_RDWR);
			stream_socket_shutdown($connection[1], STREAM_SHUT_RDWR);
		} elseif ($pid === 0) { // Child Process
			$this->_parentConnections = [];
			$this->_childConnection = $connection[1];
			$logger = Prado::getLogger();
			$logger->deleteLogs();
			$logs = $logger->deleteProfileLogs();
			$pid = getmypid();
			foreach (array_keys($logs) as $key) { // Reset PROFILE_BEGIN to pid.
				$logs[$key][TLogger::LOG_LEVEL] &= ~TLogger::LOGGED;
				$logs[$key][TLogger::LOG_PID] = $pid;
			}
			$logger->mergeLogs($logs); // Profiler logs with child pid
			$logger->getEventHandlers('onFlushLogs')->clear();
			$logger->attachEventHandler('onFlushLogs', [$this, 'sendLogsToParent'], $this->getPriority());
		} else { // Parent Process
			$this->_parentConnections[$pid] = $connection[0];
			if (!$this->_receiverInstalled) {
				$logger = Prado::getLogger();
				$logger->attachEventHandler('onCollectLogs', [$this, 'collectLogsFromChildren'], $this->getPriority());
				$this->_receiverInstalled = true;
			}
		}
	}

	/**
	 * The TLogger::onCollectLogs handler that receives the logs of all children.
	 * A final collection waits for every child to send its final logs; otherwise
	 * only the pending logs are received.
	 * @param mixed $sender The TLogger raising onCollectLogs.
	 * @param mixed $final Is this the final collection of the logs.
	 * @since 4.4.0
	 */
	public function collectLogsFromChildren(mixed $sender, mixed $final)
	{
		$this->receiveLogsFromChildren(null, (bool) $final);
	}

	/**
	 * Receives logs from children forked processes and merges the logs with the current
	 * application TLogger.  A signal that interrupts waiting on the connections
	 * retries the wait.
	 * @param ?int $pid The process ID to receive the logs from, default null for all.
	 * @param bool $wait Wait for results until complete, default true.  When false,
	 *   this receives the messages already sent and keeps any partial message for
	 *   the next call.
	 */
	public function receiveLogsFromChildren(?int $pid = null, bool $wait = true)
	{
		if (!$this->_parentConnections) {
			return;
		}
		if ($pid === null) {
			$connections = $this->_parentConnections;
		} elseif (isset($this->_parentConnections[$pid])) {
			$connections = [$pid => $this->_parentConnections[$pid]];
		} else {
			return;
		}

		$completeLogs = [];
		$write = $except = [];
		do {
			$read = $connections;
			// A signal interrupts stream_select with a warning and false; retry as a timeout.
			if (!@stream_select($read, $write, $except, $wait ? 1 : 0, 0)) {
				if ($wait) {
					continue;
				}
				break;
			}
			foreach ($read as $childPid => $socket) {
				$data = fread($socket, 8192);
				if ($data === false || $data === '') { // The child closed its connection.
					$final = true;
				} else {
					$this->_receiveBuffers[$childPid] = ($this->_receiveBuffers[$childPid] ?? '') . $data;
					$final = $this->extractChildLogs($childPid, $completeLogs);
				}
				if ($final) {
					stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
					unset($this->_parentConnections[$childPid], $this->_receiveBuffers[$childPid], $connections[$childPid]);
				}
			}
		} while ($connections);

		if (!$completeLogs) {
			return;
		}
		foreach ($completeLogs as $logs) {
			Prado::getLogger()->mergeLogs(unserialize($logs));
		}
	}

	/**
	 * Moves each complete message of a child's receive buffer into $completeLogs.
	 * A message is a 4 byte big-endian header and the serialized logs.  The header
	 * holds the length of the logs in the low 31 bits and the final flag in the high bit.
	 * An incomplete message stays in the buffer for the next read.
	 * @param int $childPid The process ID of the child.
	 * @param array $completeLogs The serialized logs of each complete message.
	 * @return bool Is the final message of the child received.
	 * @since 4.4.0
	 */
	protected function extractChildLogs(int $childPid, array &$completeLogs): bool
	{
		$buffer = $this->_receiveBuffers[$childPid];
		$final = false;
		while (!$final && strlen($buffer) >= 4) {
			$header = unpack('N', $buffer)[1];
			$length = $header & 0x7FFFFFFF;
			if (strlen($buffer) < 4 + $length) {
				break;
			}
			if ($length) {
				$completeLogs[] = substr($buffer, 4, $length);
			}
			$buffer = substr($buffer, 4 + $length);
			$final = ($header & 0x80000000) !== 0;
		}
		$this->_receiveBuffers[$childPid] = $buffer;
		return $final;
	}

	/**
	 * The TLogger::onFlushLogs handler of a child fork that sends its unlogged logs
	 * to the parent process.  TLogger::onFlushLogs raises onCollectLogs first, so
	 * the logs of this child's own children are already merged and sent with them.
	 * A final call shuts down the connection to the parent.  When the parent has
	 * closed its end, the logs are dropped and the connection is released.  A signal
	 * that interrupts waiting on the connection retries the wait.
	 * @param mixed $logger The TLogger that raised the onFlushLogs event.
	 * @param mixed $final Is this the last and final call.
	 */
	public function sendLogsToParent($logger, $final)
	{
		if (!$this->_childConnection) {
			return;
		}

		if (!($logger instanceof TLogger)) {
			$logger = Prado::getLogger();
		}

		$logs = $logger->getLogs();

		{ // clear logs already logged.
			$reset = false;
			foreach ($logs as $key => $log) {
				if ($log[TLogger::LOG_LEVEL] & TLogger::LOGGED) {
					unset($logs[$key]);
					$reset = true;
				}
			}
			if ($reset) {
				$logs = array_values($logs);
			}
		}

		$data = serialize($logs);

		if (!$logs) {
			$data = '';
		}

		$data = pack('N', ($final ? 0x80000000 : 0) | ($length = strlen($data))) . $data;
		$count = null;
		$read = $except = [];

		do {
			$write = [$this->_childConnection];
			if (@stream_select($read, $write, $except, 1, 0)) { // A signal interrupt retries.
				$count = @fwrite($this->_childConnection, $data); // false when the parent closed.
				if ($count > 0) {
					$data = substr($data, $count);
				}
			}
		} while ($count !== false && strlen($data) > 0);

		if ($count === false) {
			$this->_childConnection = null;
		} elseif ($final) {
			stream_socket_shutdown($this->_childConnection, STREAM_SHUT_RDWR);
			$this->_childConnection = null;
		}
	}
}
