<?php

/**
 * TCronWakeSignal class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\Prado;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCronWakeSignal class.
 *
 * TCronWakeSignal is the POSIX {@see \Prado\Util\Cron\ICronWakeTransport} that wakes the cron
 * process with a process signal, `SIGUSR1` by default.  The waiting process records its pid in
 * {@see setPidFile PidFile}; {@see signal()} reads the pid and sends the {@see setSignal Signal}
 * with `posix_kill()`.  A per-minute {@see wait()} installs a `pcntl` handler and sleeps in short
 * slices, dispatching the pending signals between them, so a signal that arrives while the process
 * is busy is counted by the next wait.  The daemon's {@see watch()} is the loop's `onSignal()`.
 *
 * The transport needs the `pcntl` and `posix` extensions and reaches the processes of one host.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeSignal extends TModule implements ICronWakeTransport
{
	/** The default pid file name in the runtime directory. */
	public const DEFAULT_PID_FILE_NAME = 'cronwake.pid';

	/** The seconds of each sleep slice of {@see wait()} between signal dispatches. */
	protected const WAIT_SLICE = 0.05;

	/** @var ?string the pid file path, or null for the default under the runtime path */
	private ?string $_pidFile = null;

	/** @var ?int the signal number, or null for SIGUSR1 */
	private ?int $_signal = null;

	/** @var bool whether the {@see wait()} handler is installed */
	private bool $_handled = false;

	/** @var bool whether a signal arrived since the last wait consumed it */
	private bool $_pending = false;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/**
	 * @param ?string $pidFile the pid file path, or null for the default under the runtime path
	 */
	public function __construct(?string $pidFile = null)
	{
		$this->_pidFile = $pidFile;
		parent::__construct();
	}

	/**
	 * @return string the pid file path; defaults to `cronwake.pid` in the application runtime directory
	 */
	public function getPidFile(): string
	{
		if ($this->_pidFile === null) {
			$this->_pidFile = Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . self::DEFAULT_PID_FILE_NAME;
		}
		return $this->_pidFile;
	}

	/**
	 * @param string $value the pid file path, as a path or a namespace
	 * @return static the current object
	 */
	public function setPidFile($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->_pidFile = Prado::getPathOfNamespace($value) ?? $value;
		return $this;
	}

	/**
	 * @return int the signal number; defaults to SIGUSR1
	 */
	public function getSignal(): int
	{
		return $this->_signal ?? (defined('SIGUSR1') ? constant('SIGUSR1') : 10);
	}

	/**
	 * @param int|string $value the signal number
	 * @return static the current object
	 */
	public function setSignal($value): static
	{
		$this->_signal = TPropertyValue::ensureInteger($value);
		return $this;
	}

	/**
	 * @return bool whether the `pcntl` and `posix` extensions are loaded
	 */
	protected function getIsAvailable(): bool
	{
		return \extension_loaded('pcntl') && \extension_loaded('posix');
	}

	/**
	 * @return ?int the pid recorded in the pid file, or null when absent or invalid
	 */
	public function getPid(): ?int
	{
		$content = @file_get_contents($this->getPidFile());
		$pid = ($content === false) ? 0 : (int) trim($content);
		return ($pid > 0) ? $pid : null;
	}

	/**
	 * Records this process's pid in the pid file.
	 * @return bool whether the pid was written
	 */
	protected function writePid(): bool
	{
		return @file_put_contents($this->getPidFile(), (string) getmypid(), LOCK_EX) !== false;
	}

	/**
	 * Sends the signal to the process recorded in the pid file.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether a live process was signaled
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable() || ($pid = $this->getPid()) === null) {
			return false;
		}
		return @posix_kill($pid, 0) && @posix_kill($pid, $this->getSignal());
	}

	/**
	 * Records the pid, installs the handler, and sleeps in slices until the signal arrives or the
	 * timestamp passes.  A signal that arrived since the last wait is consumed at once.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when the signal arrived, false when the wait timed out, null when unavailable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable()) {
			return null;
		}
		$this->writePid();
		$this->installHandler();
		$remaining = $until - $now;
		$start = \microtime(true);
		while (true) {
			pcntl_signal_dispatch();
			if ($this->_pending) {
				$this->_pending = false;
				return true;
			}
			if ($remaining <= 0) {
				return false;
			}
			TAsync::sleep(min($remaining, static::WAIT_SLICE));
			$remaining = ($until - $now) - (\microtime(true) - $start);
		}
	}

	/**
	 * Records the pid and watches the signal on the loop.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each signal
	 * @return bool whether the signal is watched
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$this->unwatch();
		$this->removeHandler();
		try {
			$this->_watcher = $loop->onSignal($this->getSignal(), static function () use ($onWake) {
				$onWake();
			});
		} catch (TNotSupportedException $e) {
			return false;
		}
		$this->_loop = $loop;
		$this->writePid();
		return true;
	}

	/**
	 * Cancels the loop callback of {@see watch()}.
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
	 * Cancels the watcher, removes the handler, and removes the pid file when it holds this pid.
	 */
	public function close(): void
	{
		$this->unwatch();
		$this->removeHandler();
		if ($this->getIsAvailable() && $this->getPid() === getmypid()) {
			@unlink($this->getPidFile());
		}
	}

	/**
	 * Installs the `pcntl` handler that records an arriving signal for {@see wait()}.
	 */
	protected function installHandler(): void
	{
		if (!$this->_handled) {
			pcntl_signal($this->getSignal(), function () {
				$this->_pending = true;
			});
			$this->_handled = true;
		}
	}

	/**
	 * Replaces the {@see wait()} handler with `SIG_IGN`, so a late signal does not end the process.
	 */
	protected function removeHandler(): void
	{
		if ($this->_handled) {
			pcntl_signal($this->getSignal(), SIG_IGN);
			$this->_handled = false;
			$this->_pending = false;
		}
	}
}
