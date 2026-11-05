<?php

/**
 * TProcessStatus class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\TComponent;

/**
 * TProcessStatus class
 *
 * Captures an immutable snapshot of a {@see TProcess}'s state from
 * {@see proc_get_status()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProcessStatus extends TComponent
{
	/** @var string The command string passed to proc_open(). */
	private string $_command;

	/** @var int The process id. */
	private int $_pid;

	/** @var bool Whether the process is still running. */
	private bool $_running;

	/** @var bool Whether the process was terminated by an uncaught signal. */
	private bool $_signaled;

	/** @var bool Whether the process was stopped by a signal. */
	private bool $_stopped;

	/** @var int The exit code (valid only once the process is no longer running). */
	private int $_exitCode;

	/** @var int The terminating signal number (when signaled). */
	private int $_termSig;

	/** @var int The stopping signal number (when stopped). */
	private int $_stopSig;

	/**
	 * Captures a process-status snapshot.
	 * @param array<string, mixed> $status A {@see proc_get_status()} array.
	 */
	public function __construct(array $status)
	{
		$this->setCommandDirect((string) ($status['command'] ?? ''));
		$this->setPidDirect((int) ($status['pid'] ?? 0));
		$this->setRunningDirect((bool) ($status['running'] ?? false));
		$this->setSignaledDirect((bool) ($status['signaled'] ?? false));
		$this->setStoppedDirect((bool) ($status['stopped'] ?? false));
		$this->setExitCodeDirect((int) ($status['exitcode'] ?? -1));
		$this->setTermSigDirect((int) ($status['termsig'] ?? 0));
		$this->setStopSigDirect((int) ($status['stopsig'] ?? 0));
		parent::__construct();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw command string.
	 * @return string The raw command string.
	 */
	protected function getCommandDirect(): string
	{
		return $this->_command;
	}

	/**
	 * Sets the raw command string.
	 * @param string $value The raw command string.
	 */
	protected function setCommandDirect(string $value): void
	{
		$this->_command = $value;
	}

	/**
	 * Returns the raw process id.
	 * @return int The raw process id.
	 */
	protected function getPidDirect(): int
	{
		return $this->_pid;
	}

	/**
	 * Sets the raw process id.
	 * @param int $value The raw process id.
	 */
	protected function setPidDirect(int $value): void
	{
		$this->_pid = $value;
	}

	/**
	 * Returns the raw running flag.
	 * @return bool The raw running flag.
	 */
	protected function getRunningDirect(): bool
	{
		return $this->_running;
	}

	/**
	 * Sets the raw running flag.
	 * @param bool $value The raw running flag.
	 */
	protected function setRunningDirect(bool $value): void
	{
		$this->_running = $value;
	}

	/**
	 * Returns the raw signaled flag.
	 * @return bool The raw signaled flag.
	 */
	protected function getSignaledDirect(): bool
	{
		return $this->_signaled;
	}

	/**
	 * Sets the raw signaled flag.
	 * @param bool $value The raw signaled flag.
	 */
	protected function setSignaledDirect(bool $value): void
	{
		$this->_signaled = $value;
	}

	/**
	 * Returns the raw stopped flag.
	 * @return bool The raw stopped flag.
	 */
	protected function getStoppedDirect(): bool
	{
		return $this->_stopped;
	}

	/**
	 * Sets the raw stopped flag.
	 * @param bool $value The raw stopped flag.
	 */
	protected function setStoppedDirect(bool $value): void
	{
		$this->_stopped = $value;
	}

	/**
	 * Returns the raw exit code.
	 * @return int The raw exit code.
	 */
	protected function getExitCodeDirect(): int
	{
		return $this->_exitCode;
	}

	/**
	 * Sets the raw exit code.
	 * @param int $value The raw exit code.
	 */
	protected function setExitCodeDirect(int $value): void
	{
		$this->_exitCode = $value;
	}

	/**
	 * Returns the raw terminating signal number.
	 * @return int The raw terminating signal number.
	 */
	protected function getTermSigDirect(): int
	{
		return $this->_termSig;
	}

	/**
	 * Sets the raw terminating signal number.
	 * @param int $value The raw terminating signal number.
	 */
	protected function setTermSigDirect(int $value): void
	{
		$this->_termSig = $value;
	}

	/**
	 * Returns the raw stopping signal number.
	 * @return int The raw stopping signal number.
	 */
	protected function getStopSigDirect(): int
	{
		return $this->_stopSig;
	}

	/**
	 * Sets the raw stopping signal number.
	 * @param int $value The raw stopping signal number.
	 */
	protected function setStopSigDirect(int $value): void
	{
		$this->_stopSig = $value;
	}

	//
	// ─── public getters ─────────────────────────────────────
	//

	/**
	 * Returns the command string.
	 * @return string The command string.
	 */
	public function getCommand(): string
	{
		return $this->getCommandDirect();
	}

	/**
	 * Returns the process id.
	 * @return int The process id.
	 */
	public function getPid(): int
	{
		return $this->getPidDirect();
	}

	/**
	 * Indicates whether the process is still running.
	 * @return bool Whether the process is still running.
	 */
	public function getRunning(): bool
	{
		return $this->getRunningDirect();
	}

	/**
	 * Indicates whether the process was terminated by an uncaught signal.
	 * @return bool Whether the process was terminated by an uncaught signal.
	 */
	public function getSignaled(): bool
	{
		return $this->getSignaledDirect();
	}

	/**
	 * Indicates whether the process was stopped by a signal.
	 * @return bool Whether the process was stopped by a signal.
	 */
	public function getStopped(): bool
	{
		return $this->getStoppedDirect();
	}

	/**
	 * Returns the exit code.
	 * @return int The exit code (valid once not running, else -1).
	 */
	public function getExitCode(): int
	{
		return $this->getExitCodeDirect();
	}

	/**
	 * Returns the terminating signal number.
	 * @return int The terminating signal number.
	 */
	public function getTermSig(): int
	{
		return $this->getTermSigDirect();
	}

	/**
	 * Returns the stopping signal number.
	 * @return int The stopping signal number.
	 */
	public function getStopSig(): int
	{
		return $this->getStopSigDirect();
	}
}
