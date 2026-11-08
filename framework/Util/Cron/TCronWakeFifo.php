<?php

/**
 * TCronWakeFifo class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\IO\Async\IEventLoop;
use Prado\Prado;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCronWakeFifo class.
 *
 * TCronWakeFifo is the POSIX {@see \Prado\Util\Cron\ICronWakeTransport} over a named pipe:
 * {@see signal()} writes a byte into {@see setFifoFile FifoFile}, which the waiting cron process
 * reads.  The pipe is created on first use with `posix_mkfifo()`, and both sides open it for
 * reading and writing so that opening never blocks and a byte written while the waiting process
 * is busy stays in the pipe for its next {@see wait()}.  Without a reader holding the pipe open
 * a byte is discarded, which is the harmless no-op of a wake with no listener.
 *
 * The transport needs the `posix` extension and reaches the processes of one host.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeFifo extends TModule implements ICronWakeTransport
{
	/** The default pipe file name in the runtime directory. */
	public const DEFAULT_FIFO_NAME = 'cronwake.fifo';

	/** @var ?string the pipe path, or null for the default under the runtime path */
	private ?string $_fifoFile = null;

	/** @var null|resource the pipe held open by the waiting side */
	private $_pipe;

	/** @var ?string the loop callback identifier of {@see watch()} */
	private ?string $_watcher = null;

	/** @var ?IEventLoop the loop of {@see watch()} */
	private ?IEventLoop $_loop = null;

	/**
	 * @param ?string $fifoFile the pipe path, or null for the default under the runtime path
	 */
	public function __construct(?string $fifoFile = null)
	{
		$this->_fifoFile = $fifoFile;
		parent::__construct();
	}

	/**
	 * @return string the pipe path; defaults to `cronwake.fifo` in the application runtime directory
	 */
	public function getFifoFile(): string
	{
		if ($this->_fifoFile === null) {
			$this->_fifoFile = Prado::getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . self::DEFAULT_FIFO_NAME;
		}
		return $this->_fifoFile;
	}

	/**
	 * @param string $value the pipe path, as a path or a namespace
	 * @return static the current object
	 */
	public function setFifoFile($value): static
	{
		$value = TPropertyValue::ensureString($value);
		$this->_fifoFile = Prado::getPathOfNamespace($value) ?? $value;
		return $this;
	}

	/**
	 * @return bool whether named pipes are available and the pipe's directory exists
	 */
	protected function getIsAvailable(): bool
	{
		return function_exists('posix_mkfifo') && is_dir(dirname($this->getFifoFile()));
	}

	/**
	 * Creates the pipe when absent and opens it for reading and writing without blocking.
	 * @return null|resource the opened pipe, or null when it cannot be created or opened
	 */
	protected function openPipe()
	{
		$file = $this->getFifoFile();
		clearstatcache(true, $file);
		if (!file_exists($file) && !@posix_mkfifo($file, 0o600)) {
			return null;
		}
		if (@filetype($file) !== 'fifo') {
			return null;
		}
		$pipe = @fopen($file, 'r+');
		if ($pipe === false) {
			return null;
		}
		stream_set_blocking($pipe, false);
		return $pipe;
	}

	/**
	 * Ensures the waiting side holds the pipe open.
	 * @return bool whether the pipe is open
	 */
	protected function ensureOpen(): bool
	{
		if ($this->_pipe === null) {
			$this->_pipe = $this->openPipe();
		}
		return $this->_pipe !== null;
	}

	/**
	 * Reads every byte queued in the pipe without blocking.
	 * @return bool whether at least one byte was read
	 */
	protected function drain(): bool
	{
		$drained = false;
		while (($data = @fread($this->_pipe, 8192)) !== false && $data !== '') {
			$drained = true;
		}
		return $drained;
	}

	/**
	 * Writes a wake byte into the pipe.
	 * @param float $now the current Unix timestamp with microseconds
	 * @return bool whether the byte was written
	 */
	public function signal(float $now): bool
	{
		if (!$this->getIsAvailable()) {
			return false;
		}
		$pipe = $this->_pipe ?? $this->openPipe();
		if ($pipe === null) {
			return false;
		}
		$written = @fwrite($pipe, '1');
		if ($pipe !== $this->_pipe) {
			fclose($pipe);
		}
		return $written === 1;
	}

	/**
	 * Waits until a byte arrives in the pipe or the timestamp passes.  Bytes queued since the last
	 * wait are consumed at once.
	 * @param float $until the Unix timestamp to wait until
	 * @param float $now the current Unix timestamp with microseconds
	 * @return ?bool true when a wake arrived, false when the wait timed out, null when unavailable
	 */
	public function wait(float $until, float $now): ?bool
	{
		if (!$this->getIsAvailable() || !$this->ensureOpen()) {
			return null;
		}
		$remaining = $until - $now;
		if ($remaining <= 0) {
			return $this->drain();
		}
		$sec = (int) $remaining;
		$usec = (int) (($remaining - $sec) * 1000000);
		$read = [$this->_pipe];
		$write = $except = [];
		if (@stream_select($read, $write, $except, $sec, $usec) > 0) {
			$this->drain();
			return true;
		}
		return false;
	}

	/**
	 * Holds the pipe open and delivers each wake byte to the callback from the loop.
	 * @param IEventLoop $loop the daemon's event loop
	 * @param callable $onWake called on each wake
	 * @return bool whether the pipe is open and watched
	 */
	public function watch(IEventLoop $loop, callable $onWake): bool
	{
		if (!$this->getIsAvailable() || !$this->ensureOpen()) {
			return false;
		}
		$this->unwatch();
		$this->_loop = $loop;
		$this->_watcher = $loop->onReadable($this->_pipe, function () use ($onWake) {
			$this->drain();
			$onWake();
		});
		return true;
	}

	/**
	 * Cancels the loop callback of {@see watch()}; the pipe stays open until {@see close()}.
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
	 * Cancels the watcher and closes the pipe.  The pipe file stays for the next process.
	 */
	public function close(): void
	{
		$this->unwatch();
		if ($this->_pipe !== null) {
			fclose($this->_pipe);
			$this->_pipe = null;
		}
	}
}
