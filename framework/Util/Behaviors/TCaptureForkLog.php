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
 * This captures the log of a child fork and sends it back to the parent thread.
 *
 * When {@see \Prado\Util\TProcessHelper::fork()} is called, `fxPrepareForFork`
 * is raised before the fore and `fxRestoreAfterFork` after the fork.
 * On `fxPrepareForFork`, this class creates a socket pair.  On `fxRestoreAfterFork`,
 * The parent stores all child connections, and the child sends all log data to
 * the parent.  The parent receives logs on TLogger::onCollectLogs through
 * {@see self::collectLogsFromChildren()}.
 *
 * Before sending logs, a child will receive any logs for its own child processes.
 * When sending the final logs from the child, the socket is closed and the parent
 * is flagged to close the connection.
 *
 * When not the final processing of the logs, only the pending logs will be sent
 * and received before returning.  When Final, the parent will wait until all children
 * processes have returned their logs.
 *
 * Due to this class adding logs during the flushing of the logs
 *
 * Attach this behavior to the TApplication class or object.
 * ```xml
 *		<behavior name="appSignals" AttachToClass="Prado\TApplication" class="Prado\Util\Behaviors\TApplicationSignals" PriorHandlerPriority="5" />
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
	 *
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
	 * @param string $name The name of teh behavior being added to the owner.
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
			$this->_forkConnection = null;
			return null;
		}
		$this->_forkConnection = [socket_export_stream($pair[0]), socket_export_stream($pair[1])];
		return null;
	}

	/**
	 * The behavior call back for fxRestoreAfterFork that cleans the log and resets
	 * the logger to send the log to the parent process.  The Parent process stores
	 * the child stream and pid and installs {@see self::collectLogsFromChildren()}
	 * on TLogger::onCollectLogs to receive the logs from the children forks.
	 * @param mixed $sender
	 * @param array $data the
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
	 * application TLogger.
	 * @param ?int $pid The process ID to receive the logs from, default null for all.
	 * @param bool $wait Wait for results until complete, default true.  When false,
	 *   this will process the pending logs and not wait for further logs.
	 */
	public function receiveLogsFromChildren(?int $pid = null, bool $wait = true)
	{
		if (!$this->_parentConnections) {
			return;
		}
		if ($pid && !isset($this->_parentConnections[$pid])) {
			return;
		}

		$completeLogs = [];
		$write = $except = [];
		$connections = $this->_parentConnections;
		$childLogs = [];
		do {
			$read = $connections;
			if (stream_select($read, $write, $except, ($wait || count($childLogs)) ? 1 : 0, 0)) {
				foreach ($read as $pid => $socket) {
					$data = fread($socket, 8192);
					do {
						$iterate = false;
						if ($data !== false) {
							if (array_key_exists($pid, $childLogs)) {
								$childLogs[$pid][0] .= $data;
							} else {
								$length = substr($data, 0, 4);
								if (strlen($length) >= 4) {
									$length = unpack('N', $length)[1];
									$final = ($length & 0x80000000) != 0;
									$length &= 0x7FFFFFFF;
									if ($length) {
										$data = substr($data, 4);
										$childLogs[$pid] = [$data, abs($length), $final];
									} else {
										$childLogs[$pid] = ['', 0, $final];
									}
								} else {
									$childLogs[$pid] = ['', 0, true];
								}
							}
						} else {
							$childLogs[$pid] = ['', 0, true];
						}
						if (strlen($childLogs[$pid][0]) >= $childLogs[$pid][1]) {
							if ($childLogs[$pid][2]) {
								stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
								unset($this->_parentConnections[$pid]);
								unset($connections[$pid]);
							}
							if ($childLogs[$pid][1]) {
								$completeLogs[$pid][] = substr($childLogs[$pid][0], 0, $childLogs[$pid][1]);
							}
							$data = substr($childLogs[$pid][0], $childLogs[$pid][1]);
							unset($childLogs[$pid]);
							if (!strlen($data)) {
								$data = false;
							} else {
								$iterate = true;
							}
						}

					} while ($iterate);
				}
			}
		} while (count($childLogs) || $wait && ($pid && isset($connections[$pid]) || $pid === null && $connections));

		if (!$completeLogs) {
			return;
		}
		foreach (array_merge(...$completeLogs) as $pid => $logs) {
			Prado::getLogger()->mergeLogs(unserialize($logs));
		}
	}

	/**
	 * The TLogger::onFlushLogs handler of a child fork that sends its unlogged logs
	 * to the parent process.  TLogger::onFlushLogs raises onCollectLogs first, so
	 * the logs of this child's own children are already merged and sent with them.
	 * A final call shuts down the connection to the parent.
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
			if (stream_select($read, $write, $except, 1, 0)) {
				$count = fwrite($this->_childConnection, $data);
				if ($count > 0) {
					$data = substr($data, $count);
				}
			}
		} while ($count !== false && strlen($data) > 0);

		if ($final) {
			stream_socket_shutdown($this->_childConnection, STREAM_SHUT_RDWR);
			$this->_childConnection = null;
		}
	}
}
