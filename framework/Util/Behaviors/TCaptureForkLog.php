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

	/** @var ?string The spool file path of the pending fork. */
	private ?string $_forkSpoolFile = null;

	/** @var array The spool file path of each child, by child process ID. */
	private array $_spoolFiles = [];

	/** @var ?string The spool file path of this child. */
	private ?string $_spoolFile = null;

	/** @var mixed The open spool file of this child, once its connection is full. */
	private mixed $_spoolHandle = null;

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
	 * between the parent process and child process before forking, and names the
	 * child's spool file.  Without the sockets extension, no connection is created
	 * and the child logs are not captured.
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
		$this->_forkSpoolFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-forklog-' . bin2hex(random_bytes(8)) . '.bin';
		return null;
	}

	/**
	 * The behavior callback for fxRestoreAfterFork that routes the child logs to
	 * the parent.
	 * - Parent: stores the child connection and spool file by pid and installs
	 *   {@see self::collectLogsFromChildren()} on TLogger::onCollectLogs.
	 * - Child: deletes the inherited logs, keeps the connection and spool file to
	 *   the parent, and replaces the TLogger::onFlushLogs handlers with
	 *   {@see self::sendLogsToParent()}.
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
		$spoolFile = $this->_forkSpoolFile;
		$this->_forkConnection = $this->_forkSpoolFile = null;
		$pid = $data['pid'];
		if ($pid === -1) { //fail
			stream_socket_shutdown($connection[0], STREAM_SHUT_RDWR);
			stream_socket_shutdown($connection[1], STREAM_SHUT_RDWR);
		} elseif ($pid === 0) { // Child Process
			$this->_parentConnections = $this->_spoolFiles = $this->_receiveBuffers = [];
			$this->_childConnection = $connection[1];
			$this->_spoolFile = $spoolFile;
			$this->_spoolHandle = null;
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
			$this->_spoolFiles[$pid] = $spoolFile;
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
					$this->closeChildConnection($childPid, $completeLogs);
					unset($connections[$childPid]);
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
	 * Closes the connection to a child and receives the bytes the child spooled
	 * after its connection was full.  The spool file continues the child's byte
	 * stream, and is deleted after reading.
	 * @param int $childPid The process ID of the child.
	 * @param array $completeLogs The serialized logs of each complete message.
	 * @since 4.4.0
	 */
	protected function closeChildConnection(int $childPid, array &$completeLogs): void
	{
		stream_socket_shutdown($this->_parentConnections[$childPid], STREAM_SHUT_RDWR);
		$spoolFile = $this->_spoolFiles[$childPid] ?? null;
		if ($spoolFile !== null && is_file($spoolFile)) {
			$spooled = file_get_contents($spoolFile);
			@unlink($spoolFile);
			if ($spooled) {
				$this->_receiveBuffers[$childPid] = ($this->_receiveBuffers[$childPid] ?? '') . $spooled;
				$this->extractChildLogs($childPid, $completeLogs);
			}
		}
		unset($this->_parentConnections[$childPid], $this->_spoolFiles[$childPid], $this->_receiveBuffers[$childPid]);
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
	 * closed its end, the logs are dropped and the connection is released.
	 *
	 * Sending never waits on the parent, so a parent blocked in `pcntl_waitpid()`
	 * cannot deadlock with this child.  The connection takes what fits in its buffer;
	 * the rest of the byte stream, and every later message, goes to the spool file.
	 * The parent reads the spool file when the connection closes.
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

		$data = pack('N', ($final ? 0x80000000 : 0) | strlen($data)) . $data;

		if ($this->_spoolHandle === null) {
			stream_set_blocking($this->_childConnection, false);
			$count = 0;
			while ($data !== '' && ($count = @fwrite($this->_childConnection, $data)) > 0) {
				$data = substr($data, $count);
			}
			if ($count === false) { // The parent closed its connection.
				$this->_childConnection = null;
				return;
			}
		}
		// Without a spool file, wait on the parent.  A spool file already holds the
		// stream's earlier bytes, so a failed spool write is dropped.
		if ($data !== '' && !$this->spoolLogs($data) && $this->_spoolHandle === null) {
			$this->writeToParent($data);
		}

		if ($final) {
			if ($this->_spoolHandle !== null) {
				fclose($this->_spoolHandle);
				$this->_spoolHandle = null;
			}
			if ($this->_childConnection) {
				stream_socket_shutdown($this->_childConnection, STREAM_SHUT_RDWR);
				$this->_childConnection = null;
			}
		}
	}

	/**
	 * Appends bytes of the child's stream to its spool file.  The spool file is
	 * created on the first call, exclusively and readable only by the user.
	 * @param string $data The bytes the connection did not take.
	 * @return bool Are the bytes spooled.
	 * @since 4.4.0
	 */
	protected function spoolLogs(string $data): bool
	{
		if ($this->_spoolHandle === null) {
			if ($this->_spoolFile === null || !($handle = @fopen($this->_spoolFile, 'xb'))) {
				return false;
			}
			@chmod($this->_spoolFile, 0o600);
			$this->_spoolHandle = $handle;
		}
		return @fwrite($this->_spoolHandle, $data) === strlen($data);
	}

	/**
	 * Writes the bytes to the parent and waits until the parent reads them.  This
	 * is the fallback when the spool file cannot be written.
	 * @param string $data The bytes to write.
	 * @since 4.4.0
	 */
	protected function writeToParent(string $data): void
	{
		$read = $except = [];
		while ($data !== '' && $this->_childConnection) {
			$write = [$this->_childConnection];
			if (!@stream_select($read, $write, $except, 1, 0)) { // A signal interrupt retries.
				continue;
			}
			$count = @fwrite($this->_childConnection, $data);
			if ($count === false) { // The parent closed its connection.
				$this->_childConnection = null;
			} else {
				$data = substr($data, $count);
			}
		}
	}
}
