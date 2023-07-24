<?php
/**
 * TProcessHelper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Helpers;

use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\Util\Behaviors\TCaptureForkLog;
use Prado\Util\TSignalsDispatcher;

/**
 * TProcessHelper class.
 *
 * This class handles process related functions.
 *
 * {@see self::isSystemWindows()} is used to determine if the PHP system is Windows
 * or not.
 *
 * {@see self::isForkable()} can be used if the system supports forking of the current
 * process.  {@see self::fork()} is used to fork the current process, where supported.
 * When forking, `fxPrepareForFork` {@see self::FX_PREPARE_FOR_FORK} is raised before
 * forking and `fxRestoreAfterFork` {@see self::FX_RESTORE_AFTER_FORK} is raised after
 * forking.  When $captureForkLog (fork parameter) is true, a {@see \Prado\Util\Behaviors\TCaptureForkLog}
 * behavior is attached to the {@see \Prado\TApplication} object.  All forked child
 * processes ensure the {@see \Prado\Util\TSignalsDispatcher} behavior is attached
 * to the TApplication object to allow for graceful termination on exiting signals.
 *
 * When filtering commands for popen and proc_open, {@see self::filterCommand()} will
 * replace '@php' with PHP_BINARY and wrap Windows commands with double quotes.
 * Individual arguments can be properly shell escaped with {@see self::escapeShellArg()}.
 *
 * Linux Process signals can be sent with {@see self::sendSignal()} to the current
 * pid or child pid.  To kill a child pid, call {@see self::kill()}.  {@see self::isRunning}
 * can determine if a child process is still running.
 *
 * System Process priority can be retrieved and set with {@see self::getProcessPriority()}
 * and {@see self::setProcessPriority()}, respectively.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TProcessHelper
{
	/** @var string When running a Pipe or Process, this is replaced with PHP_BINARY */
	public const PHP_COMMAND = "@php";

	/** @var string The global event prior to forking a process. */
	public const FX_PREPARE_FOR_FORK = 'fxPrepareForFork';

	/** @var string The global event after forking a process. */
	public const FX_RESTORE_AFTER_FORK = 'fxRestoreAfterFork';

	/**
	 * The WINDOWS_*_PRIORITY is what the windows priority would map into the PRADO
	 * and linux priority numbering.  Windows will only have these priorities.
	 */
	public const WINDOWS_IDLE_PRIORITY = 20;
	public const WINDOWS_BELOW_NORMAL_PRIORITY = 8;
	public const WINDOWS_NORMAL_PRIORITY = 0;
	public const WINDOWS_ABOVE_NORMAL_PRIORITY = -5;
	public const WINDOWS_HIGH_PRIORITY = -10;
	public const WINDOWS_REALTIME_PRIORITY = -17;

	/**
	 * Checks if the system that PHP is run on is Windows.
	 * @return bool Is the system Windows.
	 */
	public static function isSystemWindows(): bool
	{
		static $isWindows = null;
		if ($isWindows === null) {
			$isWindows = strncasecmp(php_uname('s'), 'win', 3) === 0;
		}
		return $isWindows;
	}

	/**
	 * @return bool Can PHP fork the process.
	 */
	public static function isForkable(): bool
	{
		return function_exists('pcntl_fork');
	}

	/**
	 * This forks the current process.  When specified, it will install a {@see \Prado\Util\Behaviors\TCaptureForkLog}.
	 * Before forking, `fxPrepareForFork` is raised and after forking `fxRestoreAfterFork` is raised.
	 *
	 * `fxPrepareForFork` handlers should return null or an array of data that it will
	 * receive in `fxRestoreAfterFork`.
	 * ```php
	 *	public function fxPrepareForFork ($sender, $param) {
	 *		return ['mydata' => 'value'];
	 *	}
	 *
	 *	public function fxRestoreAfterFork ($sender, $param) {
	 *		$param['mydata'] === 'value';
	 *		$param['pid'];
	 *	}
	 * ```
	 * @param bool $captureForkLog Installs {@see \Prado\Util\Behaviors\TCaptureForkLog} behavior on the application
	 *  so the fork log is stored by the forking process.  Default false.
	 * @throws TNotSupportedException When PHP Forking `pcntl_fork` is not supporting
	 * @return int The Child Process ID.  For children processes, they receive 0.  Failure is -1.
	 */
	public static function fork(bool $captureForkLog = false): int
	{
		if (!static::isForkable()) {
			throw new TNotSupportedException('processhelper_no_forking');
		}
		$app = Prado::getApplication();
		if ($captureForkLog && !$app->asa(TCaptureForkLog::class)) {
			$app->attachBehavior(TCaptureForkLog::BEHAVIOR_NAME, TCaptureForkLog::class);
		}
		$responses = $app->raiseEvent(static::FX_PREPARE_FOR_FORK, $app, null);
		$restore = array_merge(...$responses);
		$restore['pid'] = $pid = pcntl_fork();
		$app->raiseEvent(static::FX_RESTORE_AFTER_FORK, $app, $restore);
		if ($pid > 0) {
			Prado::info("Fork child: $pid", static::class);
		} elseif ($pid === 0) {
			Prado::info("Executing child fork", static::class);
			TSignalsDispatcher::singleton();
		} elseif ($pid === -1) {
			Prado::notice("failed fork", static::class);
		}
		return $pid;
	}

	/**
	 * If the exitCode is an exit code, returns the exit Status.
	 * @param int $exitCode
	 * @return int The exit Status
	 */
	public static function exitStatus(int $exitCode): int
	{
		if (function_exists('pcntl_wifexited') && pcntl_wifexited($exitCode)) {
			$exitCode = pcntl_wexitstatus($exitCode);
		}
		return $exitCode;
	}

	/**
	 * Filters a {@see popen} or {@see proc_open} command.
	 * The string "@php" is replaced by {@see PHP_BINARY} and in Windows the string
	 * is surrounded by double quotes.
	 *
	 * @param mixed $command
	 */
	public static function filterCommand($command)
	{
		$command = str_replace(static::PHP_COMMAND, PHP_BINARY, $command);

		if (TProcessHelper::isSystemWindows()) {
			if (is_string($command)) {
				$command = '"' . $command . '"';  //Windows, better command support
			}
		}
		return $command;
	}

	/**
	 * Sends a process signal on posix or linux systems.
	 * @param int $signal The signal to be sent.
	 * @param ?int $pid The process to send the signal, default null for the current
	 *   process.
	 * @throws TNotSupportedException When running on Windows.
	 */
	public static function sendSignal(int $signal, ?int $pid = null): bool
	{
		if (static::isSystemWindows()) {
			throw new TNotSupportedException('processhelper_no_signals');
		}
		if ($pid === null) {
			$pid = getmypid();
		}
		if (function_exists("posix_kill")) {
			return posix_kill($pid, $signal);
		}
		exec("/usr/bin/kill -s $signal $pid 2>&1", $output, $return_code);
		return !$return_code;
	}

	/**
	 * Kills a process.
	 * @param int $pid The PID to kill.
	 * @return bool Was the signal successfully sent.
	 */
	public static function kill(int $pid): bool
	{
		if (static::isSystemWindows()) {
			return shell_exec("taskkill /F /PID $pid") !== null;
		}
		return static::sendSignal(SIGKILL, $pid);
	}

	/**
	 * @param int $pid The Process ID to check if it is running.
	 * @return bool Is the PID running.
	 */
	public static function isRunning(int $pid): bool
	{
		if (static::isSystemWindows()) {
			$out = [];
			exec("TASKLIST /FO LIST /FI \"PID eq $pid\"", $out);
			return count($out) > 1;
		}

		return static::sendSignal(0, $pid);
	}

	/**
	 * @param ?int $pid The process id to get the priority of, default null for current
	 *   process.
	 * @return ?int The priority of the process.
	 */
	public static function getProcessPriority(?int $pid = null): ?int
	{
		if ($pid === null) {
			$pid = getmypid();
		}
		if (static::isSystemWindows()) {
			$output = shell_exec("wmic process where ProcessId={$pid} get priority");
			preg_match('/^\s*Priority\s*\r?\n\s*(\d+)/m', $output, $matches);
			if (isset($matches[1])) {
				$priorityValues = [ // Map Windows Priority Numbers to Linux style Numbers
					TProcessWindowsPriority::Idle => static::WINDOWS_IDLE_PRIORITY,
					TProcessWindowsPriority::BelowNormal => static::WINDOWS_BELOW_NORMAL_PRIORITY,
					TProcessWindowsPriority::Normal => static::WINDOWS_NORMAL_PRIORITY,
					TProcessWindowsPriority::AboveNormal => static::WINDOWS_ABOVE_NORMAL_PRIORITY,
					TProcessWindowsPriority::HighPriority => static::WINDOWS_HIGH_PRIORITY,
					TProcessWindowsPriority::Realtime => static::WINDOWS_REALTIME_PRIORITY,
				];
				return $priorityValues[$matches[1]] ?? null;
			} else {
				return false;
			}
		} else {
			if (strlen($priority = trim(shell_exec('exec ps -o nice= -p ' . $pid)))) {
				return (int) $priority;
			}
			return null;
		}
	}

	/**
	 * In linux systems, the priority can only go up (and have less priority).
	 * @param int $priority The priority of the PID.
	 * @param ?int $pid The PID to change the priority, default null for current process.
	 * @return bool Was successful.
	 */
	public static function setProcessPriority(int $priority, ?int $pid = null): bool
	{
		if ($pid === null) {
			$pid = getmypid();
		}
		if (static::isSystemWindows()) {
			$priorityValues = [ // The priority cap to windows text priority.
				-15 => TProcessWindowsPriorityName::Realtime,
				-10 => TProcessWindowsPriorityName::HighPriority,
				-5 => TProcessWindowsPriorityName::AboveNormal,
				4 => TProcessWindowsPriorityName::Normal,
				9 => TProcessWindowsPriorityName::BelowNormal,
				PHP_INT_MAX => TProcessWindowsPriorityName::Idle,
			];
			foreach($priorityValues as $keyPriority => $priorityName) {
				if ($priority <= $keyPriority) {
					break;
				}
			}
			$command = "wmic process where ProcessId={$pid} CALL setpriority \"$priorityName\"";
			$result = shell_exec($command);
			if (strpos($result, 'successful') !== false) {
				return true;
			}
			if (!preg_match('/ReturnValue\s*=\s*(\d+);/m', $result, $matches)) {
				return false;
			}
			return $matches[1] === 0;
		} else {
			if (($pp = static::getProcessPriority($pid)) === null) {
				return false;
			}
			$priority -= $pp;
			$result = shell_exec("exec renice -n $priority -p $pid");
			if (is_string($result) && strlen($result) > 1) {
				return false;
			}
			return true;
		}
	}

	/**
	 * Escapes a string to be used as a shell argument.
	 * @param string $argument
	 * @return string
	 */
	public static function escapeShellArg(string $argument): string
	{
		// Fix for PHP bug #43784 escapeshellarg removes % from given string
		// Fix for PHP bug #49446 escapeshellarg doesn't work on Windows
		// @see https://bugs.php.net/bug.php?id=43784
		// @see https://bugs.php.net/bug.php?id=49446
		if (static::isSystemWindows()) {
			if ($argument === '') {
				return '""';
			}

			$escapedArgument = '';
			$addQuote = false;

			foreach (preg_split('/(")/', $argument, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $part) {
				if ($part === '"') {
					$escapedArgument .= '\\"';
				} elseif (static::isSurroundedBy($part, '%')) {
					// environment variables
					$escapedArgument .= '^%"' . substr($part, 1, -1) . '"^%';
				} else {
					// escape trailing backslash
					if (str_ends_with($part, '\\')) {
						$part .= '\\';
					}
					$addQuote = true;
					$escapedArgument .= $part;
				}
			}

			if ($addQuote) {
				$escapedArgument = '"' . $escapedArgument . '"';
			}

			return $escapedArgument;
		}

		return "'" . str_replace("'", "'\\''", $argument) . "'";
	}

	/**
	 * Is the string surrounded by the prefix and reversed in appendix.
	 * @param string $string
	 * @param string $prefix
	 * @return bool Is the string surrounded by the string
	 */
	public static function isSurroundedBy(string $string, string $prefix): bool
	{
		$len = strlen($prefix);
		return strlen($string) >= 2 * $len && str_starts_with($string, $prefix) && str_ends_with($string, strrev($prefix));
	}
}
