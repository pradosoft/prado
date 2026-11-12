<?php

/**
 * TCommandCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TIOException;
use Prado\IO\Process\TProcess;
use Prado\TPropertyValue;
use Prado\Util\Helpers\TProcessHelper;

/**
 * TCommandCronTask class.
 *
 * TCommandCronTask runs a shell command to completion on each trigger:
 * ```xml
 * <job name="backup" schedule="0 2 * * *" task="Prado\Util\Cron\TCommandCronTask"
 *      Command="@php bin/backup.php --full" LogFile="/var/log/app/backup.log" Timeout="3600" />
 * ```
 *
 * `@php` in {@see getCommand Command}, as a whole word, is replaced with the quoted {@see \PHP_BINARY}.  The command
 * runs through the shell (`sh -c` on POSIX, `cmd /c` on Windows) in {@see getDirectory Directory}, with no
 * standard input; its output and errors go to {@see getLogFile LogFile}, appended with
 * {@see getAppendLog AppendLog}, or are discarded.  A nonzero exit code throws a {@see TIOException},
 * so the run's result is an error.  When the run's {@see getCancellationToken CancellationToken} is
 * cancelled at its Timeout, the command is terminated (SIGTERM, then SIGKILL after
 * {@see TERMINATE_GRACE} seconds); a signal reaches the shell, so `exec` before the command signals the
 * command itself.
 *
 * {@see TProcessCronTask} keeps a background process alive instead; a task in the Process
 * {@see \Prado\Util\Cron\TCronTaskMode Mode} runs in a child process of its own.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCommandCronTask extends TCronTask
{
	/** The seconds a terminated command has to exit before it is killed. */
	public const TERMINATE_GRACE = 5;

	/** The microseconds between the exit polls of the command. */
	protected const POLL_INTERVAL = 50000;

	/** The microseconds between the checks of the run's cancellation. */
	protected const CANCEL_INTERVAL = 250000;

	/** @var ?string the command to run */
	private ?string $_command = null;

	/** @var ?string the working directory, or null for the current one */
	private ?string $_directory = null;

	/** @var ?string the file of the command's output, or null to discard it */
	private ?string $_logFile = null;

	/** @var bool whether the output is appended to the LogFile */
	private bool $_appendLog = true;

	/** @var ?int the exit code of the last run */
	private ?int $_exitCode = null;

	/**
	 * Runs the command and waits for it to exit.
	 * @param TCronModule $cronModule the module running the task
	 * @throws TConfigurationException when the task has no Command
	 * @throws TIOException when the LogFile cannot be opened, or the command exits with a nonzero code
	 * @throws \Prado\Exceptions\TCancelledException when the run is cancelled at its Timeout
	 */
	public function execute($cronModule)
	{
		$command = $this->resolveCommand();
		if ($command === '') {
			throw new TConfigurationException('commandcrontask_no_command', $this->getName());
		}
		$null = TProcessHelper::isSystemWindows() ? 'NUL' : '/dev/null';
		$log = $this->getLogFile();
		// One handle serves both streams: two append handles overwrite each other on Windows.
		if (($output = @fopen($log ?? $null, ($log !== null && !$this->getAppendLog()) ? 'wb' : 'ab')) === false) {
			throw new TIOException('commandcrontask_log_failed', $this->getName(), $log);
		}
		$this->_exitCode = null;
		try {
			$process = TProcess::open($command, [0 => ['file', $null, 'r'], 1 => $output, 2 => $output], $this->getDirectory());
		} finally {
			fclose($output);
		}
		try {
			$this->_exitCode = $this->waitForProcess($process);
		} finally {
			$process->close();
		}
		if ($this->_exitCode !== 0) {
			throw new TIOException('commandcrontask_failed', $this->getName(), $this->_exitCode);
		}
	}

	/**
	 * Waits for the command to exit on the event loop, and terminates it when the run is cancelled.
	 * @param TProcess $process the running command
	 * @throws \Prado\Exceptions\TCancelledException when the run is cancelled
	 * @return int the exit code
	 */
	protected function waitForProcess(TProcess $process): int
	{
		$token = $this->getCancellationToken();
		while (true) {
			$code = $process->wait(static::POLL_INTERVAL, static::CANCEL_INTERVAL);
			if (!$process->getIsRunning()) {
				return $process->getExitCode() ?? $code;
			}
			if ($token !== null && $token->isCancelled()) {
				$process->terminate();
				if ($process->wait(static::POLL_INTERVAL, (int) (static::TERMINATE_GRACE * 1_000_000)) === -1) {
					$process->terminate(9); // SIGKILL
				}
				$token->throwIfCancelled();
			}
		}
	}

	/**
	 * Resolves `@php` to the quoted {@see \PHP_BINARY}, only as a whole space-delimited token so it is
	 * never rewritten inside an argument value.
	 * @return string the command ready to run
	 */
	protected function resolveCommand(): string
	{
		return trim((string) preg_replace_callback(
			'/(?<=^|\s)' . preg_quote(TProcessHelper::PHP_COMMAND, '/') . '(?=\s|$)/',
			static fn () => TProcessHelper::escapeShellArg(PHP_BINARY),
			(string) $this->getCommand()
		));
	}

	/**
	 * @return ?string the command to run
	 */
	public function getCommand(): ?string
	{
		return $this->_command;
	}

	/**
	 * @param ?string $value the command to run; `@php` is the PHP binary
	 * @return static the current object
	 */
	public function setCommand($value): static
	{
		$this->_command = ($value === null) ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the working directory, or null for the current one
	 */
	public function getDirectory(): ?string
	{
		return $this->_directory;
	}

	/**
	 * @param ?string $value the working directory, or null for the current one
	 * @return static the current object
	 */
	public function setDirectory($value): static
	{
		$this->_directory = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return ?string the file of the command's output and errors, or null to discard them
	 */
	public function getLogFile(): ?string
	{
		return $this->_logFile;
	}

	/**
	 * @param ?string $value the file of the command's output and errors, or null to discard them
	 * @return static the current object
	 */
	public function setLogFile($value): static
	{
		$this->_logFile = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return bool whether each run appends to the LogFile; false starts it over; default true
	 */
	public function getAppendLog(): bool
	{
		return $this->_appendLog;
	}

	/**
	 * @param bool|string $value whether each run appends to the LogFile
	 * @return static the current object
	 */
	public function setAppendLog($value): static
	{
		$this->_appendLog = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * @return ?int the exit code of the last run in this process, or null before a run or while it runs
	 */
	public function getExitCode(): ?int
	{
		return $this->_exitCode;
	}

	/**
	 * Excludes the default-valued properties and the last exit code from serialization.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0Prado\Util\Cron\TCommandCronTask\0_exitCode";
		if ($this->_directory === null) {
			$exprops[] = "\0Prado\Util\Cron\TCommandCronTask\0_directory";
		}
		if ($this->_logFile === null) {
			$exprops[] = "\0Prado\Util\Cron\TCommandCronTask\0_logFile";
		}
		if ($this->_appendLog) {
			$exprops[] = "\0Prado\Util\Cron\TCommandCronTask\0_appendLog";
		}
	}
}
