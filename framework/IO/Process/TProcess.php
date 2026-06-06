<?php

/**
 * TProcess class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Process;

use Prado\Exceptions\TIOException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\IO\IResource;
use Prado\IO\TResource;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * TProcess class
 *
 * Wraps a {@see proc_open()} child process.  A process handle is not itself
 * byte-readable, so TProcess extends {@see TResource} rather than {@see TStream}.
 * Its byte I/O happens through the child's pipe descriptors,
 * exposed as {@see TPipeStream} instances ({@see getStdin()}/{@see getStdout()}/
 * {@see getStderr()}).  The pipes are also reachable by descriptor number through
 * {@see \ArrayAccess} (`$proc[1]` is the stdout pipe), are {@see \Countable}, and
 * iterate (fd => {@see TPipeStream}). Writing `$proc[0] = $bytes` feeds the child's
 * stdin and `unset($proc[0])` closes that pipe.
 *
 * Pipes compose with other process pipes.  A descriptor passed to {@see open()}
 * may be a 'pipe'/'file' spec, a raw resource, or any {@see IResource}/PSR-7
 * {@see StreamInterface}. The stdout of one process can be wired into
 * the stdin of another:
 * ```php
 * $ls   = TProcess::open('ls');
 * $grep = TProcess::open('grep txt', [0 => $ls->getStdout(), 1 => TProcess::DEFAULT_STDOUT, 2 => TProcess::DEFAULT_STDERR]);
 * echo $grep->getStdout()->getContents();
 * ```
 *
 * Events ('on' prefix).  Each is a real method taking a single mixed $param:
 *  - onStart: after the process is opened.
 *  - onExit: once the exit code is first observed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProcess extends TResource implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/** @var int The standard input file descriptor number. */
	public const STDIN = 0;

	/** @var int The standard output file descriptor number. */
	public const STDOUT = 1;

	/** @var int The standard error file descriptor number. */
	public const STDERR = 2;

	/** @var array{0: string, 1: string} The default stdin descriptor spec — a pipe the child reads. */
	public const DEFAULT_STDIN = ['pipe', 'r'];

	/** @var array{0: string, 1: string} The default stdout descriptor spec — a pipe the child writes. */
	public const DEFAULT_STDOUT = ['pipe', 'w'];

	/** @var array{0: string, 1: string} The default stderr descriptor spec — a pipe the child writes. */
	public const DEFAULT_STDERR = ['pipe', 'w'];

	/** @var string The pipe class used to wrap the child's descriptors; a subclass may override it. */
	protected const PIPE_CLASS = TPipeStream::class;

	/** @var array<int, TPipeStream> The child's pipe descriptors, by fd number. */
	private array $_pipes = [];

	/** @var string The command string. */
	private string $_command = '';

	/** @var ?int The captured exit code, or null while running/unknown. */
	private ?int $_exitCode = null;

	/** @var ?array{signaled: bool, termsig: int, stopped: bool, stopsig: int} The termination flags captured at first observation, or null. */
	private ?array $_termInfo = null;

	/**
	 * Returns the default stdin/stdout/stderr pipe descriptors.
	 * @return array<int, array{0: string, 1: string}> The default stdin/stdout/stderr pipe descriptors.
	 */
	public static function defaultDescriptors(): array
	{
		return [
			static::STDIN => static::defaultDescriptor(static::STDIN),
			static::STDOUT => static::defaultDescriptor(static::STDOUT),
			static::STDERR => static::defaultDescriptor(static::STDERR),
		];
	}

	/**
	 * Opens a child process with {@see proc_open()}.
	 * @param array|string $command The command (array form avoids shell parsing).
	 * @param ?array $descriptors Descriptor spec by fd number; null uses {@see defaultDescriptors()}.
	 *   Each value is normalized by {@see normalizeDescriptors()}: 'pipe' or ['pipe'] becomes a
	 *   pipe with the fd's read/write mode; 'pty' becomes ['pty']; an existing file path or
	 *   ['file', path] becomes a file redirect (mode 'r' for fd 0, 'a' for fd 2, 'w' otherwise);
	 *   null restores the fd's default; false omits the fd so the child inherits the parent's.
	 *   An {@see IResource} or PSR-7 {@see StreamInterface} (e.g. {@see TPipeStream}/
	 *   {@see \Prado\IO\TFileStream}) is converted to its raw resource; a foreign PSR-7 stream is
	 *   bridged with {@see TStream::asResource()} and may lack an OS file descriptor, so prefer a
	 *   pipe/file spec or a Prado stream for it.  Standard fds 0/1/2 default when not set unless
	 *   given as false.
	 * @param ?string $cwd The working directory.
	 * @param ?array $env The environment variables.
	 * @param ?array $options proc_open options (e.g. ['bypass_shell' => true]).
	 * @throws TIOException When the process cannot be opened.
	 * @return static The running process.
	 */
	public static function open(array|string $command, ?array $descriptors = null, ?string $cwd = null, ?array $env = null, ?array $options = null): static
	{
		$descriptors = static::normalizeDescriptors($descriptors ?? static::defaultDescriptors());
		$pipes = [];
		$resource = @proc_open($command, $descriptors, $pipes, $cwd, $env, $options);
		if (!is_resource($resource)) {
			throw new TIOException('process_open_failed', is_array($command) ? implode(' ', $command) : $command);
		}
		$process = Prado::createComponent(static::class);
		$process->setCommandDirect(is_array($command) ? implode(' ', $command) : $command);
		$process->attachResource($resource, true);
		$pipeStreams = $process->getPipesDirect();
		foreach ($pipes as $fd => $pipeResource) {
			$pipe = Prado::createComponent(static::PIPE_CLASS);
			assert($pipe instanceof TPipeStream);
			$pipe->attachResource($pipeResource, true);
			$pipeStreams[$fd] = $pipe;
		}
		$process->setPipesDirect($pipeStreams);
		$process->onStart($resource);
		return $process;
	}

	/**
	 * Normalizes descriptor specs for {@see proc_open()}.  Each value is mapped through
	 * {@see normalizeDescriptor()}; standard fds (0/1/2) fall back to their defaults when
	 * not set, while a false value omits an fd so the child inherits the parent's.
	 * @param array $descriptors The descriptor specs by fd number.
	 * @throws TInvalidDataValueException When an IResource descriptor has no open handle.
	 * @return array The normalized descriptors, ordered by fd number.
	 */
	protected static function normalizeDescriptors(array $descriptors): array
	{
		$normalized = [];
		$suppressed = [];
		foreach ($descriptors as $fd => $spec) {
			$fd = (int) $fd;
			if ($spec === false) {
				$suppressed[$fd] = true;
				continue;
			}
			$normalized[$fd] = static::normalizeDescriptor($fd, $spec);
		}
		foreach ([static::STDIN, static::STDOUT, static::STDERR] as $fd) {
			if (!isset($normalized[$fd]) && !isset($suppressed[$fd])) {
				$normalized[$fd] = static::defaultDescriptor($fd);
			}
		}
		ksort($normalized);
		return $normalized;
	}

	/**
	 * Normalizes a single descriptor spec for {@see proc_open()}.
	 *  - An {@see IResource} resolves to its raw resource (throws when closed).
	 *  - A PSR-7 {@see StreamInterface} is bridged with {@see TStream::asResource()}.
	 *  - A raw resource passes through unchanged.
	 *  - null restores the fd's {@see defaultDescriptor()}.
	 *  - A string is normalized by {@see normalizeStringDescriptor()} ('pipe'/'pty'/file path).
	 *  - An array is normalized by {@see normalizeArrayDescriptor()} (fills the pipe/file mode).
	 * @param int $fd The descriptor number.
	 * @param mixed $spec The descriptor spec.
	 * @throws TInvalidDataValueException When an IResource descriptor has no open handle.
	 * @return mixed The normalized descriptor.
	 */
	protected static function normalizeDescriptor(int $fd, mixed $spec): mixed
	{
		if ($spec instanceof IResource) {
			$resource = $spec->getResource();
			if (!is_resource($resource)) {
				throw new TInvalidDataValueException('process_descriptor_unusable', $fd);
			}
			return $resource;
		}
		if ($spec instanceof StreamInterface) {
			return TStream::asResource($spec);
		}
		if (is_resource($spec)) {
			return $spec;
		}
		if ($spec === null) {
			return static::defaultDescriptor($fd);
		}
		if (is_string($spec)) {
			return static::normalizeStringDescriptor($fd, $spec);
		}
		if (is_array($spec)) {
			return static::normalizeArrayDescriptor($fd, $spec);
		}
		return $spec;
	}

	/**
	 * Normalizes a string descriptor spec.  'pipe' becomes a pipe with the fd's read/write
	 * mode, 'pty' becomes ['pty'], and any other string is a file path resolved with
	 * {@see realpath()} when it exists and opened with the fd's {@see fileMode()}.
	 * @param int $fd The descriptor number.
	 * @param string $spec The string spec.
	 * @return array The normalized descriptor array.
	 */
	protected static function normalizeStringDescriptor(int $fd, string $spec): array
	{
		if ($spec === 'pipe') {
			return ['pipe', static::pipeMode($fd)];
		}
		if ($spec === 'pty') {
			return ['pty'];
		}
		$path = realpath($spec);
		return ['file', $path === false ? $spec : $path, static::fileMode($fd)];
	}

	/**
	 * Normalizes an array descriptor spec, filling the mode when it is missing.  A bare
	 * ['pipe'] gains the fd's read/write mode; a ['file', path] gains the fd's
	 * {@see fileMode()} and resolves an existing path with {@see realpath()}.  Other arrays
	 * (e.g. ['pty'], a complete ['pipe', mode] or ['file', path, mode]) pass through.
	 * @param int $fd The descriptor number.
	 * @param array $spec The array spec.
	 * @return array The normalized descriptor array.
	 */
	protected static function normalizeArrayDescriptor(int $fd, array $spec): array
	{
		$type = $spec[0] ?? null;
		if ($type === 'pipe' && !isset($spec[1])) {
			return ['pipe', static::pipeMode($fd)];
		}
		if ($type === 'file') {
			$path = (string) ($spec[1] ?? '');
			$real = realpath($path);
			return ['file', $real === false ? $path : $real, $spec[2] ?? static::fileMode($fd)];
		}
		return $spec;
	}

	/**
	 * Returns the pipe mode for a descriptor: 'r' for stdin (fd 0), 'w' for any other fd.
	 * @param int $fd The descriptor number.
	 * @return string The pipe mode.
	 */
	protected static function pipeMode(int $fd): string
	{
		return $fd === static::STDIN ? 'r' : 'w';
	}

	/**
	 * Returns the file mode for a descriptor: 'r' for stdin (fd 0), 'a' for stderr (fd 2),
	 * 'w' for any other fd.
	 * @param int $fd The descriptor number.
	 * @return string The file mode.
	 */
	protected static function fileMode(int $fd): string
	{
		return match ($fd) {
			static::STDIN => 'r',
			static::STDERR => 'a',
			default => 'w',
		};
	}

	/**
	 * Returns the default pipe descriptor for an fd: the stdin/stdout/stderr defaults for
	 * fds 0/1/2, and a write pipe for any other fd.
	 * @param int $fd The descriptor number.
	 * @return array{0: string, 1: string} The default descriptor array.
	 */
	protected static function defaultDescriptor(int $fd): array
	{
		return match ($fd) {
			static::STDIN => static::DEFAULT_STDIN,
			static::STDOUT => static::DEFAULT_STDOUT,
			static::STDERR => static::DEFAULT_STDERR,
			default => ['pipe', static::pipeMode($fd)],
		};
	}

	/**
	 * Closes the child's pipes, captures the exit code, and {@see proc_close()}s.
	 * @param mixed $resource The process resource.
	 * @return bool Whether the close succeeded.
	 */
	protected function closeResource(mixed $resource): bool
	{
		foreach ($this->getPipesDirect() as $pipe) {
			if ($pipe->isOpen()) {
				$pipe->close();
			}
		}
		if (!is_resource($resource)) {
			return false;
		}
		$status = @proc_get_status($resource);
		if (is_array($status) && !$status['running']) {
			$this->observeTermination($status);
		}
		$ret = proc_close($resource);
		if ($this->getExitCodeDirect() === null && $ret !== -1) {
			$this->captureExit($ret);
		}
		return $ret !== -1;
	}

	/**
	 * Records the exit code the first time it is observed and raises {@see onExit}.
	 * @param int $code The exit code.
	 */
	protected function captureExit(int $code): void
	{
		if ($this->getExitCodeDirect() === null && $code !== -1) {
			$this->setExitCodeDirect($code);
			$this->onExit($code);
		}
	}

	/**
	 * Derives the effective exit code from a {@see proc_get_status()} array.  A process
	 * ended by a signal reports an exitcode of -1, so this returns 128 + the terminating
	 * signal number (the POSIX shell convention) instead.
	 * @param array<string, mixed> $status A proc_get_status() array.
	 * @return int The effective exit code, or -1 when still unknown.
	 */
	protected function effectiveExitCode(array $status): int
	{
		$code = (int) ($status['exitcode'] ?? -1);
		if ($code === -1 && !empty($status['signaled']) && (int) ($status['termsig'] ?? 0) > 0) {
			return 128 + (int) $status['termsig'];
		}
		return $code;
	}

	/**
	 * Records the exit code and termination flags the first time the process is observed
	 * stopped.  {@see proc_get_status()} reports the exit code and signal fields only on
	 * the first call after exit, so they are cached here to keep later snapshots correct.
	 * @param array<string, mixed> $status A proc_get_status() array with running === false.
	 */
	protected function observeTermination(array $status): void
	{
		if ($this->getTermInfoDirect() === null) {
			$this->setTermInfoDirect([
				'signaled' => (bool) ($status['signaled'] ?? false),
				'termsig' => (int) ($status['termsig'] ?? 0),
				'stopped' => (bool) ($status['stopped'] ?? false),
				'stopsig' => (int) ($status['stopsig'] ?? 0),
			]);
		}
		$this->captureExit($this->effectiveExitCode($status));
	}

	/**
	 * Closes the child's pipes and the process ({@see proc_close()}).  Use
	 * {@see closeStream()} for the boolean result.
	 */
	public function close(): void
	{
		$this->closeStream();
	}

	/**
	 * Returns a fresh snapshot of the process state.
	 * @return TProcessStatus A fresh snapshot of the process state.
	 */
	public function getStatus(): TProcessStatus
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return Prado::createComponent(TProcessStatus::class, [
				'command' => $this->getCommandDirect(),
				'running' => false,
				'exitcode' => $this->getExitCodeDirect() ?? -1,
			]);
		}
		$status = proc_get_status($resource);
		if (!$status['running']) {
			$this->observeTermination($status);
			// proc_get_status reports the exit code and signal fields only on the first
			// call after exit; overlay the cached values so later snapshots stay correct.
			if ($this->getExitCodeDirect() !== null) {
				$status['exitcode'] = $this->getExitCodeDirect();
			}
			$term = $this->getTermInfoDirect();
			if ($term !== null) {
				$status = array_merge($status, $term);
			}
		}
		return Prado::createComponent(TProcessStatus::class, $status);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw pipe-descriptor list, keyed by fd number.
	 * @return array<int, TPipeStream> The raw pipe-descriptor list.
	 */
	protected function getPipesDirect(): array
	{
		return $this->_pipes;
	}

	/**
	 * Sets the raw pipe-descriptor list.
	 * @param array<int, TPipeStream> $value The raw pipe-descriptor list.
	 */
	protected function setPipesDirect(array $value): void
	{
		$this->_pipes = $value;
	}

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
	 * Returns the raw captured exit code.
	 * @return ?int The raw captured exit code.
	 */
	protected function getExitCodeDirect(): ?int
	{
		return $this->_exitCode;
	}

	/**
	 * Sets the raw captured exit code.
	 * @param ?int $value The raw captured exit code.
	 */
	protected function setExitCodeDirect(?int $value): void
	{
		$this->_exitCode = $value;
	}

	/**
	 * Returns the raw cached termination flags, or null before the process is observed stopped.
	 * @return ?array{signaled: bool, termsig: int, stopped: bool, stopsig: int} The raw termination flags.
	 */
	protected function getTermInfoDirect(): ?array
	{
		return $this->_termInfo;
	}

	/**
	 * Sets the raw cached termination flags.
	 * @param ?array{signaled: bool, termsig: int, stopped: bool, stopsig: int} $value The raw termination flags.
	 */
	protected function setTermInfoDirect(?array $value): void
	{
		$this->_termInfo = $value;
	}

	/**
	 * Returns the command string.
	 * @return string The command string.
	 */
	public function getCommand(): string
	{
		return $this->getCommandDirect();
	}

	/**
	 * Returns the process id.  Reads through {@see getStatus()} so the exit code is
	 * captured if the process has meanwhile exited.
	 * @return ?int The process id, or null when closed.
	 */
	public function getPid(): ?int
	{
		if (!is_resource($this->getResourceDirect())) {
			return null;
		}
		$pid = $this->getStatus()->getPid();
		return $pid > 0 ? $pid : null;
	}

	/**
	 * Indicates whether the process is still running.
	 * @return bool Whether the process is still running.
	 */
	public function getIsRunning(): bool
	{
		return $this->getStatus()->getRunning();
	}

	/**
	 * Returns the exit code once the process has finished.  A process ended by a signal
	 * reports 128 + the signal number (POSIX convention); {@see getStatus()} exposes the
	 * raw {@see TProcessStatus::getSignaled()}/{@see TProcessStatus::getTermSig()} flags.
	 * @return ?int The exit code once the process has finished, else null.
	 */
	public function getExitCode(): ?int
	{
		if ($this->getExitCodeDirect() === null) {
			$this->getStatus(); // may capture the exit code
		}
		return $this->getExitCodeDirect();
	}

	/**
	 * Returns the pipe for a descriptor number.
	 * @param int $fd The descriptor number.
	 * @return ?TPipeStream The pipe for that descriptor, or null.
	 */
	public function getPipe(int $fd): ?TPipeStream
	{
		return $this->getPipesDirect()[$fd] ?? null;
	}

	/**
	 * Returns all pipe descriptors.
	 * @return array<int, TPipeStream> All pipe descriptors.
	 */
	public function getPipes(): array
	{
		return $this->getPipesDirect();
	}

	/**
	 * Returns the child's stdin pipe.
	 * @return ?TPipeStream The child's stdin pipe (fd 0), or null.
	 */
	public function getStdin(): ?TPipeStream
	{
		return $this->getPipe(static::STDIN);
	}

	/**
	 * Returns the child's stdout pipe.
	 * @return ?TPipeStream The child's stdout pipe (fd 1), or null.
	 */
	public function getStdout(): ?TPipeStream
	{
		return $this->getPipe(static::STDOUT);
	}

	/**
	 * Returns the child's stderr pipe.
	 * @return ?TPipeStream The child's stderr pipe (fd 2), or null.
	 */
	public function getStderr(): ?TPipeStream
	{
		return $this->getPipe(static::STDERR);
	}

	/**
	 * Sends a signal to the process.  A string command is run through a shell, so the
	 * signal reaches the shell rather than the child; use an array command (or `exec`) to
	 * signal the process directly.
	 * @param int $signal The signal number. Default SIGTERM (15).
	 * @return bool Whether the signal was sent.
	 */
	public function terminate(int $signal = 15): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			return false;
		}
		return proc_terminate($resource, $signal);
	}

	/**
	 * Blocks until the process exits or the timeout elapses.  It does not drain the
	 * pipes, so a child that can fill a stdout/stderr pipe buffer may deadlock; read (or
	 * close) those pipes while waiting on such commands.
	 * @param int $pollMicroseconds Poll interval while waiting. Default 1000 (1 ms).
	 * @param int $timeoutMicroseconds Maximum time to wait; 0 waits indefinitely. Default 0.
	 * @return int The exit code, or -1 when still running at the timeout or unknown.
	 */
	public function wait(int $pollMicroseconds = 1000, int $timeoutMicroseconds = 0): int
	{
		$waited = 0;
		while ($this->getIsRunning()) {
			if ($timeoutMicroseconds > 0 && $waited >= $timeoutMicroseconds) {
				return -1;
			}
			usleep($pollMicroseconds);
			$waited += $pollMicroseconds;
		}
		return $this->getExitCodeDirect() ?? -1;
	}

	/**
	 * Raised after the process is opened.
	 * @param mixed $param The process resource.
	 */
	public function onStart(mixed $param): void
	{
		$this->raiseEvent('onStart', $this, $param);
	}

	/**
	 * Raised once the exit code is first observed.
	 * @param mixed $param The exit code.
	 */
	public function onExit(mixed $param): void
	{
		$this->raiseEvent('onExit', $this, $param);
	}

	/**
	 * Indicates whether a pipe exists for the descriptor number (\ArrayAccess).
	 * @param mixed $offset The descriptor number.
	 * @return bool Whether a pipe is present for that descriptor.
	 */
	public function offsetExists(mixed $offset): bool
	{
		return is_numeric($offset) && isset($this->getPipesDirect()[(int) $offset]);
	}

	/**
	 * Returns the pipe for a descriptor number (\ArrayAccess).
	 * @param mixed $offset The descriptor number.
	 * @throws TInvalidDataValueException When the offset is not numeric.
	 * @return ?TPipeStream The pipe for that descriptor, or null when absent.
	 */
	public function offsetGet(mixed $offset): mixed
	{
		if (!is_numeric($offset)) {
			throw new TInvalidDataValueException('process_invalid_pipe_offset', $offset);
		}
		return $this->getPipe((int) $offset);
	}

	/**
	 * Writes bytes to the pipe for a descriptor number (\ArrayAccess).
	 * @param mixed $offset The descriptor number.
	 * @param mixed $value The bytes to write.
	 * @throws TInvalidDataValueException When no pipe exists for the offset.
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (!is_numeric($offset) || !isset($this->getPipesDirect()[(int) $offset])) {
			throw new TInvalidDataValueException('process_invalid_pipe_offset', $offset);
		}
		$this->getPipesDirect()[(int) $offset]->write((string) $value);
	}

	/**
	 * Closes and removes the pipe for a descriptor number (\ArrayAccess).
	 * @param mixed $offset The descriptor number.
	 * @throws TInvalidDataValueException When no pipe exists for the offset.
	 */
	public function offsetUnset(mixed $offset): void
	{
		if (!is_numeric($offset) || !isset($this->getPipesDirect()[(int) $offset])) {
			throw new TInvalidDataValueException('process_invalid_pipe_offset', $offset);
		}
		$fd = (int) $offset;
		$pipes = $this->getPipesDirect();
		if ($pipes[$fd]->isOpen()) {
			$pipes[$fd]->close();
		}
		unset($pipes[$fd]);
		$this->setPipesDirect($pipes);
	}

	/**
	 * Returns an iterator over the pipes, keyed by descriptor number (\IteratorAggregate).
	 * @return \Iterator An iterator yielding fd => {@see TPipeStream}.
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->getPipesDirect());
	}

	/**
	 * Returns the number of pipe descriptors (\Countable).  A descriptor closed with
	 * {@see TPipeStream::close()} remains counted until it is removed with `unset($proc[$fd])`.
	 * @return int The number of pipe descriptors.
	 */
	public function count(): int
	{
		return count($this->getPipesDirect());
	}

	/**
	 * Cloning yields a non-owning process handle with no pipes, so closing the clone never
	 * touches the original's pipe handles (see {@see TResource::__clone()}).
	 */
	public function __clone()
	{
		parent::__clone();
		$this->setPipesDirect([]);
	}

	/**
	 * Excludes the non-serializable pipe handles from {@see \Prado\TComponent::__sleep()}.
	 * The captured exit code is a plain int and is kept so it survives serialization.
	 * @param array &$exprops The properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_pipes";
	}
}
