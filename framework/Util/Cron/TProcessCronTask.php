<?php

/**
 * TProcessCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\TLogger;

/**
 * TProcessCronTask class.
 *
 * TProcessCronTask keeps a background process running. Scheduled every minute, each run checks whether the
 * managed process is alive and (re)launches it when it is not — a lightweight supervisor for a long-running
 * server started from the command line:
 * ```php
 * <job name="websocket" schedule="* * * * *"
 *      task="Prado\Util\Cron\TProcessCronTask" Command="@php prado-cli websocket-server" />
 * ```
 *
 * Liveness is tracked with a **PID file**: {@see startProcess} writes the launched process id to
 * {@see getPidFile}, and each run reads it and checks {@see \Prado\Util\Helpers\TProcessHelper::isRunning}.
 * The default PID file is `<runtime>/{@see PID_DIRECTORY}/<task name>.pid`, so managed processes keep their
 * pid files together.
 * Set {@see setMatch Match} to a substring of the process command line to guard against pid reuse — a
 * running pid is accepted only when its command line contains that string (POSIX only).
 *
 * `@php` in {@see getCommand Command} is replaced with {@see \PHP_BINARY}. The process is launched detached
 * (POSIX: `nohup ... &`; Windows: a hidden `Start-Process`), with output redirected to {@see getLogFile}
 * (default `/dev/null`). {@see launchProcess} is the overridable seam for the OS-specific launch.
 *
 * The tracked pid differs by platform: on POSIX it is the command's own process; on Windows it is the
 * `cmd.exe` wrapper that runs the command, which serves as a liveness proxy (it lives and dies with the
 * command) rather than the leaf process. This is sufficient for the supervisor, which only checks
 * liveness and relaunches; it does not signal the process by pid.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProcessCronTask extends TCronTask
{
	/** the runtime subdirectory holding managed-process pid files */
	public const PID_DIRECTORY = 'bg';

	/** microseconds to wait after launch before confirming the process survived (catches a failed exec) */
	protected const LAUNCH_GRACE_USEC = 50000;

	/** @var ?string the command that runs the process; `@php` becomes PHP_BINARY */
	private ?string $_command = null;

	/** @var ?string the pid file path, or null for the default under the runtime path */
	private ?string $_pidFile = null;

	/** @var ?string a command-line substring that a running pid must contain, or null to skip the check */
	private ?string $_match = null;

	/** @var ?string the working directory to launch from, or null for the current one */
	private ?string $_directory = null;

	/** @var ?string the file to redirect process output to, or null for the platform null device */
	private ?string $_logFile = null;

	/**
	 * @param ?string $command the command that runs the process
	 */
	public function __construct($command = null)
	{
		parent::__construct();
		if ($command !== null) {
			$this->setCommand($command);
		}
	}

	/**
	 * Ensures the managed process is running, launching it when it is not.
	 * @param TCronModule $cron the module calling the task
	 * @throws TConfigurationException when no command is configured
	 * @return false|int false when the process was already running, otherwise the launched process id
	 */
	public function execute($cron)
	{
		if ($this->getCommand() === null || $this->getCommand() === '') {
			throw new TConfigurationException('processcrontask_no_command', $this->getName());
		}
		// Serialize the check-and-launch with a non-blocking advisory lock so two overlapping cron workers
		// cannot both see "not running" and spawn duplicate (and orphaned) processes.
		$lock = $this->acquireLock();
		if ($lock === null) {
			Prado::log('Process "' . $this->getName() . '" launch skipped; another worker holds the lock', TLogger::DEBUG, TProcessCronTask::class);
			return false;
		}
		try {
			if ($this->isProcessRunning()) {
				Prado::log('Process "' . $this->getName() . '" already running (pid ' . $this->getPid() . ')', TLogger::DEBUG, TProcessCronTask::class);
				return false;
			}
			try {
				$pid = $this->startProcess();
			} catch (\Throwable $e) {
				// A launch failure must not abort the cron sweep (runTask does not catch around execute);
				// surface it as an error and let the next poll retry, rather than logging a false "started".
				Prado::log('Process "' . $this->getName() . '" failed to launch: ' . $e->getMessage(), TLogger::ERROR, TProcessCronTask::class);
				return false;
			}
			Prado::log('Process "' . $this->getName() . '" started (pid ' . $pid . ')', TLogger::INFO, TProcessCronTask::class);
			return $pid;
		} finally {
			$this->releaseLock($lock);
		}
	}

	/**
	 * @return bool whether the managed process is currently running
	 */
	public function isProcessRunning(): bool
	{
		$pid = $this->getPid();
		if ($pid === null || !$this->isRunning($pid)) {
			return false;
		}
		// PID-reuse guard: the live process must carry the start time recorded at launch. Skipped when no
		// start time was recorded (e.g. a legacy pid-only file), falling back to the optional Match check.
		$recorded = $this->getRecordedStart();
		if ($recorded !== null && $recorded !== $this->processStartTime($pid)) {
			return false;
		}
		return $this->verifyProcess($pid);
	}

	/**
	 * Whether a pid is a live process. Overridable seam over {@see \Prado\Util\Helpers\TProcessHelper::isRunning}.
	 * @param int $pid the process id
	 * @return bool whether the pid is alive
	 */
	protected function isRunning(int $pid): bool
	{
		return TProcessHelper::isRunning($pid);
	}

	/**
	 * Launches the process detached and records its pid in {@see getPidFile}.
	 * @throws TConfigurationException when the process cannot be launched
	 * @return int the launched process id
	 */
	public function startProcess(): int
	{
		$pid = $this->launchProcess($this->resolveCommand());
		if ($pid === null || $pid <= 0) {
			throw new TConfigurationException('processcrontask_launch_failed', $this->getCommand());
		}
		$this->writePid($pid);
		// Detect a command that fails to exec / exits immediately, so it is not recorded as a success and
		// relaunched every minute with a misleading "started" log.
		usleep(static::LAUNCH_GRACE_USEC);
		if (!$this->isRunning($pid)) {
			throw new TConfigurationException('processcrontask_launch_failed', $this->getCommand());
		}
		return $pid;
	}

	/**
	 * Resolves `@php` to {@see \PHP_BINARY}, only as a whole space-delimited token so it is never rewritten
	 * inside an argument value.
	 * @return string the command ready to run
	 */
	protected function resolveCommand(): string
	{
		return (string) preg_replace_callback(
			'/(?<=^|\s)' . preg_quote(TProcessHelper::PHP_COMMAND, '/') . '(?=\s|$)/',
			static fn () => PHP_BINARY,
			(string) $this->getCommand()
		);
	}

	/**
	 * Launches the command as a detached background process and returns its pid. This is the single
	 * overridable seam for the OS-specific launch (tests override it to avoid spawning). The POSIX path
	 * expects a single foreground command; a pipeline or a self-daemonizing command makes `$!` (and thus
	 * the tracked pid) refer to a process that is not the long-running one.
	 * @param string $command the command to run, with `@php` already resolved
	 * @return ?int the launched process id, or null on failure
	 */
	protected function launchProcess(string $command): ?int
	{
		if (TProcessHelper::isSystemWindows()) {
			// The pid is the cmd.exe wrapper that stays attached to the managed process (a documented
			// limitation). Redirection uses plain cmd '>' (caret-escaped '^>' would pass a literal '>' to
			// the command); the working directory and log path are quoted.
			$log = $this->getLogFile() ?? 'NUL';
			$dir = $this->getDirectory();
			$cd = ($dir !== null && $dir !== '') ? 'cd /d "' . $dir . '" & ' : '';
			$inner = str_replace("'", "''", $cd . $command . ' > "' . $log . '" 2>&1');
			$out = shell_exec('powershell -NoProfile -Command "(Start-Process -FilePath cmd.exe -ArgumentList \'/c ' . $inner . '\' -WindowStyle Hidden -PassThru).Id"');
			$pid = (int) trim((string) $out);
			return $pid > 0 ? $pid : null;
		}
		$log = $this->getLogFile() ?? '/dev/null';
		$prefix = '';
		if (($dir = $this->getDirectory()) !== null && $dir !== '') {
			$prefix = 'cd ' . TProcessHelper::escapeShellArg($dir) . ' && ';
		}
		$out = shell_exec($prefix . 'nohup ' . $command . ' >> ' . TProcessHelper::escapeShellArg($log) . ' 2>&1 & echo $!');
		$pid = (int) trim((string) $out);
		return $pid > 0 ? $pid : null;
	}

	/**
	 * Confirms a running pid is the managed process by matching {@see getMatch} against its command line.
	 * Returns true when no match is configured, or on Windows where the command line is not inspected.
	 * @param int $pid the running process id to verify
	 * @return bool whether the pid is the managed process
	 */
	protected function verifyProcess(int $pid): bool
	{
		$match = $this->getMatch();
		if ($match === null || $match === '' || TProcessHelper::isSystemWindows()) {
			return true;
		}
		$args = shell_exec('ps -p ' . $pid . ' -o args= 2>/dev/null');
		return $args !== null && str_contains($args, $match);
	}

	/**
	 * Writes the pid and its start time to {@see getPidFile} atomically (temp file + rename), creating the
	 * directory when needed. The two-line file is "pid\nstartTime".
	 * @param int $pid the process id to record
	 * @throws TConfigurationException when the pid file cannot be written
	 */
	protected function writePid(int $pid): void
	{
		$file = $this->getPidFile();
		$dir = dirname($file);
		if (!is_dir($dir) && !@mkdir($dir, 0o775, true) && !is_dir($dir)) {
			throw new TConfigurationException('processcrontask_write_failed', $file);
		}
		$tmp = $file . '.' . getmypid() . '.tmp';
		$content = $pid . "\n" . ($this->processStartTime($pid) ?? '');
		if (@file_put_contents($tmp, $content) === false || !@rename($tmp, $file)) {
			@unlink($tmp);
			throw new TConfigurationException('processcrontask_write_failed', $file);
		}
	}

	/**
	 * @return ?int the recorded process id from {@see getPidFile}, or null when absent or invalid
	 */
	public function getPid(): ?int
	{
		$file = $this->getPidFile();
		if (!is_file($file)) {
			return null;
		}
		$pid = (int) trim((string) strtok((string) @file_get_contents($file), "\n"));
		return $pid > 0 ? $pid : null;
	}

	/**
	 * @return ?string the process start time recorded alongside the pid, or null when none was recorded
	 */
	protected function getRecordedStart(): ?string
	{
		$file = $this->getPidFile();
		if (!is_file($file)) {
			return null;
		}
		$parts = explode("\n", (string) @file_get_contents($file), 2);
		return (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : null;
	}

	/**
	 * Returns an opaque per-process start-time marker for a pid (POSIX: `ps -o lstart=`; Windows: the
	 * process StartTime ticks), used to detect pid reuse. Null when the pid is gone or unobtainable.
	 * @param int $pid the process id
	 * @return ?string the start-time marker, or null
	 */
	protected function processStartTime(int $pid): ?string
	{
		if (TProcessHelper::isSystemWindows()) {
			$out = trim((string) shell_exec('powershell -NoProfile -Command "try { (Get-Process -Id ' . $pid . ' -ErrorAction Stop).StartTime.Ticks } catch { \'\' }" 2>NUL'));
			return $out !== '' ? $out : null;
		}
		$out = trim((string) shell_exec('ps -o lstart= -p ' . $pid . ' 2>/dev/null'));
		return $out !== '' ? $out : null;
	}

	/**
	 * Acquires a non-blocking advisory lock next to the pid file for the check-and-launch critical section.
	 * @return null|false|resource a held lock handle; null when another worker holds the lock (skip this
	 *   run); false when no lock file could be created (proceed unlocked, best effort)
	 */
	protected function acquireLock()
	{
		$file = $this->getPidFile();
		$dir = dirname($file);
		if (!is_dir($dir)) {
			@mkdir($dir, 0o775, true);
		}
		$handle = @fopen($file . '.lock', 'c');
		if ($handle === false) {
			return false;
		}
		if (!flock($handle, LOCK_EX | LOCK_NB)) {
			fclose($handle);
			return null;
		}
		return $handle;
	}

	/**
	 * Releases a lock handle returned by {@see acquireLock}.
	 * @param mixed $handle the lock handle (a resource when a lock was held)
	 */
	protected function releaseLock($handle): void
	{
		if (is_resource($handle)) {
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}

	/**
	 * @return ?string the command that runs the process
	 */
	public function getCommand(): ?string
	{
		return $this->_command;
	}

	/**
	 * @param ?string $value the command that runs the process; `@php` becomes PHP_BINARY
	 * @return $this for method chaining.
	 */
	public function setCommand($value): static
	{
		$this->_command = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return string the pid file path; defaults to `<runtime>/{@see PID_DIRECTORY}/<task name>.pid`
	 */
	public function getPidFile(): string
	{
		if ($this->_pidFile !== null) {
			return $this->_pidFile;
		}
		$runtime = $this->getApplication()->getRuntimePath();
		return $runtime . DIRECTORY_SEPARATOR . self::PID_DIRECTORY . DIRECTORY_SEPARATOR . $this->getName() . '.pid';
	}

	/**
	 * @param ?string $value the pid file path, or null to use the default
	 * @return $this for method chaining.
	 */
	public function setPidFile($value): static
	{
		$this->_pidFile = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string a command-line substring a running pid must contain, or null to skip the check
	 */
	public function getMatch(): ?string
	{
		return $this->_match;
	}

	/**
	 * @param ?string $value a command-line substring used to guard against pid reuse
	 * @return $this for method chaining.
	 */
	public function setMatch($value): static
	{
		$this->_match = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the working directory to launch from, or null for the current one
	 */
	public function getDirectory(): ?string
	{
		return $this->_directory;
	}

	/**
	 * @param ?string $value the working directory to launch from
	 * @return $this for method chaining.
	 */
	public function setDirectory($value): static
	{
		$this->_directory = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the file process output is redirected to, or null for the platform null device
	 */
	public function getLogFile(): ?string
	{
		return $this->_logFile;
	}

	/**
	 * @param ?string $value the file to redirect process output to
	 * @return $this for method chaining.
	 */
	public function setLogFile($value): static
	{
		$this->_logFile = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Excludes default-valued properties from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);

		$prop = "\0" . __CLASS__ . "\0";
		if ($this->_command === null) {
			$exprops[] = $prop . "_command";
		}
		if ($this->_pidFile === null) {
			$exprops[] = $prop . "_pidFile";
		}
		if ($this->_match === null) {
			$exprops[] = $prop . "_match";
		}
		if ($this->_directory === null) {
			$exprops[] = $prop . "_directory";
		}
		if ($this->_logFile === null) {
			$exprops[] = $prop . "_logFile";
		}
	}
}
