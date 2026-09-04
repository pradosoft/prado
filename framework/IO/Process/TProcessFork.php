<?php

/**
 * TProcessFork class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\Exceptions\TIOException;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Socket\TSocketReactor;
use Prado\IO\Socket\TSocketStream;
use Prado\Prado;
use Prado\TComponent;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\Log\TLogger;
use Prado\Util\TSignalsDispatcher;

/**
 * TProcessFork class.
 *
 * An object handle for a child created with {@see TProcessHelper::fork() pcntl_fork}.  Where
 * {@see TProcess} wraps an external command opened with `proc_open` (a resource handle, so it
 * extends {@see \Prado\IO\TResource}), a fork duplicates the running PHP process and yields a PID,
 * so this handle extends {@see TComponent} and owns no stream resource of its own.
 *
 * A fork is run in one of two ways:
 *
 *  - {@see fork()} — worker: the child runs {@see run()} (a subclass override or the given body)
 *    inside an isolation wrapper and exits with its return code; the parent receives the handle.
 *  - {@see start()} — container: the call returns the handle in both processes; the child checks
 *    {@see getIsChild()}, does its work, and calls {@see exit()}.
 *
 * The parent side reaps the child synchronously with {@see wait()} (blocking) or {@see poll()}
 * (non-blocking), or asynchronously by enabling {@see setAsync() Async} (a subclass defaults it
 * through {@see DEFAULT_ASYNC}), which registers the child with the {@see TSignalsDispatcher} so a
 * `SIGCHLD` reap raises {@see onExit} without an explicit wait.  {@see terminate()} and
 * {@see kill()} signal the child.  An optional {@see getChannel() Channel} is one end of a socket
 * pair for parent-child messaging; a channel-backed fork drains it through a {@see TSocketReactor},
 * either inside {@see wait()} or folded into an existing event loop with {@see register()}, so the
 * drained data and {@see onExit} arrive as the loop runs.
 *
 * ```php
 * $worker = TProcessFork::fork(function (TProcessFork $self) {
 *     $self->getChannel()?->write('done');
 *     return 0;
 * }, channel: true);
 * echo $worker->getChannel()->read(16);   // 'done'
 * $worker->wait();                         // reaps the child, raises onExit
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method bool dyRun()
 */
class TProcessFork extends TComponent
{
	/** The child is running. */
	public const STATE_RUNNING = 'running';

	/** The child has exited and been reaped. */
	public const STATE_EXITED = 'exited';

	/** The exit code used when the child body throws an uncaught exception (sysexits EX_SOFTWARE). */
	public const EXIT_EXCEPTION = 70;

	/** The bytes read from the channel per readable event. */
	public const CHUNK_SIZE = 65536;

	/** Whether a subclass always opens a parent-child channel (the result/live forks need it). */
	protected const REQUIRES_CHANNEL = false;

	/** Whether the parent reaps asynchronously by default; a subclass overrides it to opt in. */
	protected const DEFAULT_ASYNC = false;

	/** @var int The child PID on the parent side, or the child's own PID on the child side. */
	private int $_pid = 0;

	/** @var bool Whether this handle is held by the child process. */
	private bool $_isChild = false;

	/** @var string The lifecycle state, {@see STATE_RUNNING} or {@see STATE_EXITED}. */
	private string $_state = self::STATE_RUNNING;

	/** @var ?int The exit code, recorded once the child is reaped. */
	private ?int $_exitCode = null;

	/** @var ?TSocketStream The owned end of the parent-child channel, or null when none was created. */
	private ?TSocketStream $_channel = null;

	/** @var ?callable The child body run by {@see run()} in worker mode. */
	protected $_body;

	/** @var bool Whether the child is reaped asynchronously through the signals dispatcher. */
	private bool $_async = false;

	/** @var ?TSocketReactor The reactor draining the channel while the parent waits, when channel-backed. */
	private ?TSocketReactor $_reactor = null;

	/**
	 * Forks the process into a worker.  The child runs {@see run()} inside an isolation wrapper and
	 * exits with its return code; the parent receives the handle.
	 * @param ?callable $body The child body, called as `fn(TProcessFork $self): int`.  Omit it to
	 *   override {@see run()} in a subclass instead.
	 * @param bool $channel Whether to open a socket-pair channel between parent and child.
	 * @param bool $captureForkLog Whether the child's log is captured back to the parent.
	 * @throws TNotSupportedException When the platform cannot fork.
	 * @throws TIOException When the fork fails.
	 * @return static The parent-side handle.
	 */
	public static function fork(?callable $body = null, bool $channel = false, bool $captureForkLog = false): static
	{
		return static::forkProcess($body, $channel, $captureForkLog, true);
	}

	/**
	 * Forks the process and returns the handle in both processes.  No body runs automatically: the
	 * child tests {@see getIsChild()}, does its work, and calls {@see exit()}.
	 * @param bool $channel Whether to open a socket-pair channel between parent and child.
	 * @param bool $captureForkLog Whether the child's log is captured back to the parent.
	 * @throws TNotSupportedException When the platform cannot fork.
	 * @throws TIOException When the fork fails.
	 * @return static The handle, in both the parent and the child.
	 */
	public static function start(bool $channel = false, bool $captureForkLog = false): static
	{
		return static::forkProcess(null, $channel, $captureForkLog, false);
	}

	/**
	 * Forks the process, splits the optional channel, and dispatches the child to its body in worker
	 * mode.  The child never returns from a worker fork; it exits through {@see exit()}.
	 * @param ?callable $body The child body for worker mode.
	 * @param bool $channel Whether to open a parent-child channel.
	 * @param bool $captureForkLog Whether the child's log is captured back to the parent.
	 * @param bool $runBody Whether the child runs {@see run()} and exits (worker), or returns (container).
	 * @throws TNotSupportedException When the platform cannot fork.
	 * @throws TIOException When the fork fails.
	 * @return static The handle.
	 */
	protected static function forkProcess(?callable $body, bool $channel, bool $captureForkLog, bool $runBody): static
	{
		if (!TProcessHelper::isForkable()) {
			throw new TNotSupportedException('processhelper_no_forking');
		}
		$fork = Prado::createComponent(static::class);
		$fork->_body = $body;
		[$parentEnd, $childEnd] = ($channel || static::REQUIRES_CHANNEL) ? TSocketStream::pair() : [null, null];

		$pid = TProcessHelper::fork($captureForkLog);
		if ($pid === -1) {
			$parentEnd?->close();
			$childEnd?->close();
			throw new TIOException('processfork_failed');
		}
		if ($pid === 0) {
			$fork->_pid = (int) getmypid();
			$fork->_isChild = true;
			$parentEnd?->close();
			$fork->_channel = $childEnd;
			$fork->onFork($fork);
			if ($runBody) {
				$fork->execute();   // runs the body and exits; never returns
			}
			return $fork;
		}
		$fork->_pid = $pid;
		$childEnd?->close();
		$fork->_channel = $parentEnd;
		$fork->onFork($fork);
		if (static::DEFAULT_ASYNC) {
			$fork->setAsync(true);
		}
		return $fork;
	}

	/**
	 * The child body run in worker mode.  The default runs the callable passed to {@see fork()}; a
	 * subclass overrides this to define the worker without a callable.
	 * @return int The exit code (0 on success).
	 */
	protected function run(): int
	{
		if ($this->_body !== null) {
			$result = ($this->_body)($this);
			return is_int($result) ? $result : 0;
		}
		return 0;
	}

	/**
	 * Runs {@see run()} in the child inside a try/finally so an exception becomes a non-zero exit
	 * rather than unwinding into the parent's control flow.  This never returns.
	 */
	protected function execute(): void
	{
		$code = self::EXIT_EXCEPTION;
		try {
			$code = $this->run();
		} catch (\Throwable $e) {
			Prado::log((string) $e, TLogger::ERROR, static::class);
		} finally {
			$this->exit($code);
		}
	}

	/**
	 * Closes the channel and exits the child process.  A no-op in the parent.
	 * @param int $code The exit code.
	 */
	public function exit(int $code = 0): void
	{
		if (!$this->getIsChild()) {
			return;
		}
		$this->_channel?->close();
		exit($code);
	}

	/**
	 * Blocks until the child exits, then records its exit code and raises {@see onExit}.  A
	 * channel-backed fork drains the channel through a {@see TSocketReactor} as it waits, so the
	 * child never blocks writing a large result into a full pipe; a channel-less fork waits on a
	 * plain blocking {@see https://www.php.net/pcntl_waitpid waitpid}.  A fork already
	 * {@see register() registered} is driven through that reactor, and the parameter is ignored.
	 * @param ?TSocketReactor $reactor The reactor to drive, or null to drive a private one.
	 * @return ?int The exit code, or null when there is no child to wait on.
	 */
	public function wait(?TSocketReactor $reactor = null): ?int
	{
		if ($this->getIsChild() || $this->_pid <= 0 || $this->_state === self::STATE_EXITED) {
			return $this->_exitCode;
		}
		if ($this->_channel === null) {
			return $this->reap(0);
		}
		if ($this->_reactor === null) {
			$this->register($reactor ?? Prado::createComponent(TSocketReactor::class));
		}
		while ($this->_state !== self::STATE_EXITED) {
			$this->_reactor->tick(null);
		}
		return $this->_exitCode;
	}

	/**
	 * Registers the channel with a reactor so the parent drains it (and detects the child's exit by
	 * the channel's end of stream) without blocking, folding the fork into an existing event loop.
	 * A child handle, a channel-less fork, or an already-registered fork is a no-op.
	 * @param TSocketReactor $reactor The reactor to drain the channel through.
	 */
	public function register(TSocketReactor $reactor): void
	{
		if ($this->getIsChild() || $this->_channel === null || $this->_reactor !== null) {
			return;
		}
		$this->_reactor = $reactor;
		$this->_channel->setBlocking(false);
		$reactor->register($this->_channel, onReadable: fn () => $this->drainChannel());
	}

	/**
	 * Reads what the channel has ready.  Bytes are handed to {@see consume()}; an end of stream means
	 * the child closed its end, so the channel is finished, unregistered, and closed, and the child is
	 * reaped.  A child normally closes as it exits, but a container child may close early and keep
	 * working, so the reap polls (and follows up on a reactor timer) rather than blocking a shared
	 * event loop in a waitpid.
	 */
	protected function drainChannel(): void
	{
		$bytes = false;
		try {
			$bytes = $this->_channel?->read(static::CHUNK_SIZE);
		} catch (\Throwable $e) {
			$bytes = '';   // a read failure is treated as the child closing
		}
		if ($bytes !== '' && $bytes !== false) {
			$this->consume($bytes);
			return;
		}
		$this->finishChannel();
		if ($this->_channel !== null) {
			$this->_reactor?->unregister($this->_channel);
			$this->_channel->close();
		}
		if ($this->poll() === null && $this->_reactor !== null) {
			$timerId = 0;
			$timerId = $this->_reactor->every(0.01, function () use (&$timerId) {
				if ($this->poll() !== null) {
					$this->_reactor?->cancelTimer($timerId);
				}
			});
		}
	}

	/**
	 * Consumes bytes drained from the channel.  The base fork keeps none; a subclass collects a
	 * result or applies live updates.
	 * @param string $bytes The bytes read from the channel.
	 */
	protected function consume(string $bytes): void
	{
	}

	/**
	 * Completes channel processing at the child's end of stream, before the child is reaped.  A
	 * subclass decodes whatever it buffered.
	 */
	protected function finishChannel(): void
	{
	}

	/**
	 * Writes all of the bytes to the channel from the child, looping until the whole buffer is sent so
	 * a short write cannot truncate a large payload.  A no-op without a channel.
	 * @param string $data The bytes to write.
	 */
	protected function writeChannel(string $data): void
	{
		if ($this->_channel === null) {
			return;
		}
		$offset = 0;
		$length = strlen($data);
		while ($offset < $length) {
			$written = $this->_channel->write($offset === 0 ? $data : substr($data, $offset));
			if ($written <= 0) {
				break;
			}
			$offset += $written;
		}
	}

	/**
	 * Reaps the child without blocking, recording the exit code and raising {@see onExit} when it has
	 * exited.
	 * @return ?int The exit code, or null when the child is still running or absent.
	 */
	public function poll(): ?int
	{
		return $this->reap(WNOHANG);
	}

	/**
	 * Waits on the child with the given options, capturing its exit on termination.
	 * @param int $options The pcntl_waitpid options (0 to block, WNOHANG to poll).
	 * @return ?int The recorded exit code, or null when nothing was reaped.
	 */
	private function reap(int $options): ?int
	{
		if ($this->getIsChild() || $this->_pid <= 0 || $this->_state === self::STATE_EXITED) {
			return $this->_exitCode;
		}
		$status = 0;
		if (pcntl_waitpid($this->_pid, $status, $options) === $this->_pid) {
			$this->captureExit(TProcessHelper::exitStatus($status));
		}
		return $this->_exitCode;
	}

	/**
	 * Records the exit code the first time the child is reaped and raises {@see onExit}.
	 * @param int $code The exit code.
	 */
	protected function captureExit(int $code): void
	{
		if ($this->_state !== self::STATE_EXITED) {
			$this->_exitCode = $code;
			$this->_state = self::STATE_EXITED;
			if ($this->_async) {
				// Detach the child's signal handler however it was reaped, so a synchronous reap
				// while async is on does not leave a registration the dispatcher never clears.
				TSignalsDispatcher::singleton(false)?->detachPidHandler($this->_pid, [$this, 'reapFromSignal']);
				$this->_async = false;
			}
			$this->onExit($code);
		}
	}

	/**
	 * Sends a signal to the child, asking it to stop.
	 * @param int $signal The signal to send. Default SIGTERM.
	 * @return bool Whether the signal was sent.
	 */
	public function terminate(int $signal = SIGTERM): bool
	{
		if ($this->getIsChild() || $this->_pid <= 0 || $this->_state === self::STATE_EXITED) {
			return false;
		}
		return TProcessHelper::sendSignal($signal, $this->_pid);
	}

	/**
	 * Forcibly kills the child.
	 * @return bool Whether the child was killed.
	 */
	public function kill(): bool
	{
		if ($this->getIsChild() || $this->_pid <= 0 || $this->_state === self::STATE_EXITED) {
			return false;
		}
		return TProcessHelper::kill($this->_pid);
	}

	/**
	 * The PID-handler callback for {@see setAsync() Async} reaping: the dispatcher reaped the child,
	 * so record its exit and raise {@see onExit}.
	 * @param object $sender The signals dispatcher.
	 * @param mixed $param The signal parameter carrying the child status.
	 */
	public function reapFromSignal($sender, $param): void
	{
		$info = ($param !== null && method_exists($param, 'getParameter')) ? $param->getParameter() : null;
		$status = (is_array($info) && isset($info['status'])) ? (int) $info['status'] : 0;
		$this->captureExit(TProcessHelper::exitStatus($status));
	}

	/**
	 * Returns the child PID on the parent side, or this process's PID on the child side.
	 * @return int The process ID.
	 */
	public function getProcessId(): int
	{
		return $this->_pid;
	}

	/**
	 * Returns whether this handle is held by the child process.
	 * @return bool Whether this is the child.
	 */
	public function getIsChild(): bool
	{
		return $this->_isChild;
	}

	/**
	 * Returns whether this handle is held by the parent of a forked child.
	 * @return bool Whether this is the parent.
	 */
	public function getIsParent(): bool
	{
		return !$this->getIsChild() && $this->_pid > 0;
	}

	/**
	 * Returns the owned end of the parent-child channel.
	 * @return ?TSocketStream The channel, or null when none was opened.
	 */
	public function getChannel(): ?TSocketStream
	{
		return $this->_channel;
	}

	/**
	 * Returns the lifecycle state.
	 * @return string {@see STATE_RUNNING} or {@see STATE_EXITED}.
	 */
	public function getState(): string
	{
		return $this->_state;
	}

	/**
	 * Returns whether the child is still running, reaping it first when it has already exited.
	 * @return bool Whether the child runs.
	 */
	public function getIsRunning(): bool
	{
		if ($this->getIsChild()) {
			return true;
		}
		if ($this->_state === self::STATE_EXITED || $this->_pid <= 0) {
			return false;
		}
		$this->poll();
		return $this->_state !== self::STATE_EXITED;
	}

	/**
	 * Returns the exit code, available once the child is reaped.
	 * @return ?int The exit code, or null when the child has not been reaped.
	 */
	public function getExitCode(): ?int
	{
		return $this->_exitCode;
	}

	/**
	 * Returns whether the child is reaped asynchronously through the signals dispatcher.
	 * @return bool Whether async reaping is on.
	 */
	public function getAsync(): bool
	{
		return $this->_async;
	}

	/**
	 * Enables or disables asynchronous reaping.  When on, the child is registered with the
	 * {@see TSignalsDispatcher} so a `SIGCHLD` raises {@see onExit} without an explicit {@see wait()}.
	 * @param bool $value Whether to reap asynchronously.
	 * @return static The current handle.
	 */
	public function setAsync(bool $value): static
	{
		if ($value !== $this->getAsync() && !$this->getIsChild() && $this->_pid > 0) {
			$dispatcher = TSignalsDispatcher::singleton($value);
			if ($value) {
				$dispatcher?->attachPidHandler($this->_pid, [$this, 'reapFromSignal']);
			} else {
				$dispatcher?->detachPidHandler($this->_pid, [$this, 'reapFromSignal']);
			}
		}
		$this->_async = $value;
		return $this;
	}

	/**
	 * Raised after the fork returns, in both the parent and the child.
	 * @param mixed $param This {@see TProcessFork} handle.
	 */
	public function onFork(mixed $param): void
	{
		$this->raiseEvent('onFork', $this, $param);
	}

	/**
	 * Raised when the child's exit code is first observed.
	 * @param mixed $param The exit code.
	 */
	public function onExit(mixed $param): void
	{
		$this->raiseEvent('onExit', $this, $param);
	}

	/**
	 * Excludes the channel and body from serialization, as neither survives a sleep.
	 * @param array $exprops The properties excluded from __sleep.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_channel";
		$exprops[] = "\0" . __CLASS__ . "\0_body";
		$exprops[] = "\0" . __CLASS__ . "\0_reactor";
	}
}
