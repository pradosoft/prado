<?php

/**
 * TResource class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponent;

/**
 * TResource class
 *
 * TResource is the abstract base of the Prado IO layer.  It encapsulates a single
 * PHP resource handle, as produced by {@see fopen()}, {@see popen()},
 * {@see proc_open()}, and {@see stream_socket_client()}, and exposes the
 * operations that apply to any such handle, independent of reading or writing:
 * lifecycle (attach/detach/close), metadata, blocking and timeout, chunk/buffer
 * sizing, advisory locking, {@see fstat()}, and TTY/locality probes.
 *
 * Reading and writing live one layer up in {@see \Prado\IO\TStream}.  Handles that
 * are not byte streams, such as a {@see proc_open()} process, extend TResource
 * directly.
 *
 * Ownership: when this object {@see getOwnsResource() owns} its handle it closes it
 * on {@see closeStream()} and on destruction.  Borrowed handles, including the standard
 * streams STDIN/STDOUT/STDERR, set ownership to false so they are never closed
 * out from under their real owner.
 *
 * Self-encapsulation (Uniform Access Principle): the backing fields are private, so
 * all access in this class and its subclasses goes through accessors.  The protected
 * {@see getResourceDirect()}/{@see setResourceDirect()} (and the {@see getIsProcessDirect()}
 * pair) are the raw accessors; subclasses may override the public {@see getResource()}/
 * {@see getIsProcess()} without disturbing internal logic.
 *
 * Events ('on' prefix, notification only).  Each is a real method taking a single
 * mixed $param:
 *  - onOpen: after a handle is attached/opened.
 *  - onFinalize: before flush+close; the last chance to drain pending state.
 *  - onClose: after the handle is released.
 *  - onDetach: when the handle is severed from this object.
 *  - onError: when an IO operation reports an error.
 *  - onTimeout: when a stream timeout window has elapsed.
 *  - onFlush: after a buffer flush.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TResource extends TComponent implements IResource
{
	/** @var mixed The underlying PHP resource handle, or null. */
	private mixed $_resource = null;

	/** @var bool Whether this object owns (and so will close) the handle. */
	private bool $_owns = false;

	/** @var bool Whether the handle is a {@see proc_open()} process resource. */
	private bool $_isProcess = false;

	/**
	 * Wraps an open resource handle, or creates an empty (closed) instance when null.
	 * @param mixed $resource An open PHP resource handle to adopt, or null.
	 * @throws TInvalidDataTypeException When a non-resource, non-null value is given.
	 */
	public function __construct(mixed $resource = null)
	{
		if ($resource !== null) {
			$this->attachResource($resource, true);
		}
		parent::__construct();
	}

	/**
	 * Closes the handle when this object owns it.
	 */
	public function __destruct()
	{
		if ($this->getOwnsResource() && is_resource($this->getResourceDirect())) {
			$this->closeStream();
		}
		parent::__destruct();
	}

	/**
	 * Cloning yields a non-owning view of the same handle: a PHP resource cannot be
	 * duplicated, so the clone shares the original's handle but must not close it.
	 */
	public function __clone()
	{
		parent::__clone();
		$this->setOwnsResource(false);
	}

	/**
	 * Adopts an open resource handle, releasing any handle already held (the prior
	 * handle is closed when this object owns it) so a re-attach never leaks.
	 * @param mixed $resource The resource produced by fopen/popen/proc_open/etc.
	 * @param bool $owns Whether this object should close the handle. Default true.
	 * @throws TInvalidDataTypeException When $resource is not a PHP resource.
	 */
	public function attachResource(mixed $resource, bool $owns = true): void
	{
		if (!is_resource($resource)) {
			throw new TInvalidDataTypeException('resource_not_a_resource', gettype($resource));
		}
		$current = $this->getResourceDirect();
		if (is_resource($current) && $current !== $resource) {
			$this->closeStream();
		}
		$this->setResourceDirect($resource);
		$this->setOwnsResource($owns);
		$this->setIsProcessDirect(get_resource_type($resource) === TResourceType::Process);
		$this->onOpen($resource);
	}

	/**
	 * Performs the low-level close of the handle.  {@see closeStream()} calls this when
	 * the object owns the handle; subclasses override it for {@see pclose()} (pipes) or
	 * {@see proc_close()} (processes).
	 * @param mixed $resource The resource to close.
	 * @return bool Whether the close succeeded.
	 */
	protected function closeResource(mixed $resource): bool
	{
		return fclose($resource);
	}

	//
	// ─── Self-encapsulated handle accessors ──────────────────────────────────
	//

	/**
	 * Returns the raw resource handle without any subclass overrides.
	 * @return mixed The raw resource handle (protected raw accessor).
	 */
	protected function getResourceDirect(): mixed
	{
		return $this->_resource;
	}

	/**
	 * Stores the raw resource handle.
	 * @param mixed $resource The raw resource handle (protected raw accessor).
	 */
	protected function setResourceDirect(mixed $resource): void
	{
		$this->_resource = $resource;
	}

	/**
	 * Returns the raw process flag without any subclass overrides.
	 * @return bool Whether the handle is a process resource (protected raw accessor).
	 */
	protected function getIsProcessDirect(): bool
	{
		return $this->_isProcess;
	}

	/**
	 * Records whether the handle is a process resource.
	 * @param bool $value Whether the handle is a process resource (raw accessor).
	 */
	protected function setIsProcessDirect(bool $value): void
	{
		$this->_isProcess = $value;
	}

	/**
	 * Returns the underlying PHP resource handle.
	 * @return mixed The underlying PHP resource handle, or null when not open.
	 */
	public function getResource(): mixed
	{
		return $this->getResourceDirect();
	}

	/**
	 * Indicates whether a live resource handle is currently held.
	 * @return bool Whether a live resource handle is currently held.
	 */
	public function isOpen(): bool
	{
		return is_resource($this->getResourceDirect());
	}

	/**
	 * Indicates whether this object owns (and so closes) its handle.
	 * @return bool Whether this object owns (and so will close) its handle.
	 */
	public function getOwnsResource(): bool
	{
		return $this->_owns;
	}

	/**
	 * Sets whether this object owns and closes its handle.
	 * @param bool $value Whether this object should close its handle.
	 */
	public function setOwnsResource(bool $value): void
	{
		$this->_owns = $value;
	}

	/**
	 * Indicates whether the handle is a process resource.
	 * @return bool Whether the handle is a {@see proc_open()} process resource.
	 */
	public function getIsProcess(): bool
	{
		return $this->getIsProcessDirect();
	}

	/**
	 * Severs the handle from this object without closing it; ownership is dropped.
	 * @return mixed The detached resource, or null when there was none.
	 */
	public function detach(): mixed
	{
		$resource = $this->getResourceDirect();
		$this->setResourceDirect(null);
		$this->setOwnsResource(false);
		$this->setIsProcessDirect(false);
		$this->onDetach($resource);
		return $resource;
	}

	/**
	 * Releases the handle.  When this object does not own the handle (borrowed
	 * resources, STDIN/STDOUT/STDERR) the handle is left open; the close events
	 * still fire and the reference is dropped.
	 * @return ?bool The close result, or null when there was nothing open.
	 */
	public function closeStream(): ?bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource)) {
			$this->setResourceDirect(null);
			return null;
		}
		$this->onFinalize($resource);
		$result = false;
		if ($this->getOwnsResource()) {
			$result = $this->closeResource($resource);
		}
		$this->setResourceDirect(null);
		$this->onClose($resource);
		return $result;
	}

	/**
	 * Returns the type name of the handle.
	 * @return ?string The {@see get_resource_type()} of the handle, or null.
	 */
	public function getResourceType(): ?string
	{
		$resource = $this->getResourceDirect();
		return is_resource($resource) ? get_resource_type($resource) : null;
	}

	/**
	 * Returns the stream metadata, the whole array or a single key.
	 * @param ?string $key A single metadata key (see {@see TStreamMetadataKey}), or null for the whole array.
	 * @return mixed The {@see stream_get_meta_data()} array, one value, or null.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return $key === null ? [] : null;
		}
		$meta = stream_get_meta_data($resource);
		if ($key === null) {
			return $meta;
		}
		return $meta[$key] ?? null;
	}

	/**
	 * Reports whether the stream is in blocking mode.
	 * @return ?bool Whether the stream is in blocking mode, or null when unknown.
	 */
	public function getBlocking(): ?bool
	{
		$blocked = $this->getMetadata('blocked');
		return is_bool($blocked) ? $blocked : null;
	}

	/**
	 * Sets the stream's blocking mode.
	 * @param bool $enable Whether to put the stream into blocking mode.
	 * @return bool Whether the call succeeded.
	 */
	public function setBlocking(bool $enable): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return stream_set_blocking($resource, $enable);
	}

	/**
	 * Sets the read timeout for socket-based streams.
	 * @param int $seconds Whole seconds.
	 * @param int $microseconds Additional microseconds. Default 0.
	 * @return bool Whether the call succeeded.
	 */
	public function setTimeout(int $seconds, int $microseconds = 0): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return stream_set_timeout($resource, $seconds, $microseconds);
	}

	/**
	 * Reports whether the stream timed out waiting for data on the last call, and
	 * raises {@see onTimeout} when it has.
	 * @return bool Whether the stream has timed out.
	 */
	public function getTimedOut(): bool
	{
		$timedOut = (bool) $this->getMetadata('timed_out');
		if ($timedOut) {
			$this->onTimeout($this->getResourceDirect());
		}
		return $timedOut;
	}

	/**
	 * Sets the chunk size used for stream reads.
	 * @param int $size The chunk size in bytes for stream reads.
	 * @return false|int The previous chunk size, or false on failure.
	 */
	public function setChunkSize(int $size): false|int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return stream_set_chunk_size($resource, $size);
	}

	/**
	 * Sets the read buffer size.
	 * @param int $size The read buffer size in bytes (0 disables buffering).
	 * @return int 0 on success.
	 */
	public function setReadBuffer(int $size): int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return -1;
		}
		return stream_set_read_buffer($resource, $size);
	}

	/**
	 * Sets the write buffer size.
	 * @param int $size The write buffer size in bytes (0 disables buffering).
	 * @return int 0 on success.
	 */
	public function setWriteBuffer(int $size): int
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return -1;
		}
		return stream_set_write_buffer($resource, $size);
	}

	/**
	 * Applies an advisory lock with {@see flock()}.
	 * @param int $operation LOCK_SH or LOCK_EX (LOCK_UN releases; see {@see unlock()}).
	 * @param bool $nonBlocking Whether to add LOCK_NB. Default false.
	 * @param ?int &$wouldBlock Set to 1 if the lock would block (with LOCK_NB).
	 * @return ?bool Whether the lock succeeded, or null when locking is unsupported.
	 */
	public function lock(int $operation, bool $nonBlocking = false, ?int &$wouldBlock = null): ?bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess() || !stream_supports_lock($resource)) {
			return null;
		}
		return flock($resource, $operation | ($nonBlocking ? LOCK_NB : 0), $wouldBlock);
	}

	/**
	 * Releases an advisory {@see lock()}.
	 * @return ?bool Whether the unlock succeeded, or null when unsupported.
	 */
	public function unlock(): ?bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess() || !stream_supports_lock($resource)) {
			return null;
		}
		return flock($resource, LOCK_UN);
	}

	/**
	 * Returns the {@see fstat()} information for the handle.
	 * @return array|false The {@see fstat()} of the handle, or false on failure.
	 */
	public function stat(): array|false
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return fstat($resource);
	}

	/**
	 * Indicates whether the stream refers to a local resource.
	 * @return bool Whether the stream refers to a local file/resource.
	 */
	public function getIsLocal(): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return stream_is_local($resource);
	}

	/**
	 * Indicates whether the stream is an interactive terminal.
	 * @return bool Whether the stream is an interactive terminal.
	 */
	public function getIsTTY(): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		return stream_isatty($resource);
	}

	/**
	 * Forces a write of all buffered output to the underlying resource.
	 * @return bool Whether the flush succeeded.
	 */
	public function fflush(): bool
	{
		$resource = $this->getResourceDirect();
		if (!is_resource($resource) || $this->getIsProcess()) {
			return false;
		}
		$result = fflush($resource);
		$this->onFlush($resource);
		return $result;
	}

	//
	// ─── Events ──────────────────────────────────────────────────────────────
	//

	/**
	 * Raised after a handle is attached/opened.
	 * @param mixed $param The newly attached resource.
	 */
	public function onOpen(mixed $param): void
	{
		$this->raiseEvent('onOpen', $this, $param);
	}

	/**
	 * Raised before flush+close, the last chance to drain pending state.
	 * @param mixed $param The resource about to be closed.
	 */
	public function onFinalize(mixed $param): void
	{
		$this->raiseEvent('onFinalize', $this, $param);
	}

	/**
	 * Raised after the handle has been released.
	 * @param mixed $param The resource that was closed.
	 */
	public function onClose(mixed $param): void
	{
		$this->raiseEvent('onClose', $this, $param);
	}

	/**
	 * Raised when the handle is severed from this object.
	 * @param mixed $param The detached resource.
	 */
	public function onDetach(mixed $param): void
	{
		$this->raiseEvent('onDetach', $this, $param);
	}

	/**
	 * Raised when an IO operation reports an error.
	 * @param mixed $param Error context (message/errno) supplied by the caller.
	 */
	public function onError(mixed $param): void
	{
		$this->raiseEvent('onError', $this, $param);
	}

	/**
	 * Raised when a stream timeout window has elapsed.
	 * @param mixed $param The timed-out resource.
	 */
	public function onTimeout(mixed $param): void
	{
		$this->raiseEvent('onTimeout', $this, $param);
	}

	/**
	 * Raised after a buffer flush.
	 * @param mixed $param The flushed resource.
	 */
	public function onFlush(mixed $param): void
	{
		$this->raiseEvent('onFlush', $this, $param);
	}

	/**
	 * A PHP resource handle cannot be serialized; exclude it (and the ownership
	 * flags, which are meaningless without it) from {@see TComponent::__sleep()}.
	 * @param array &$exprops The properties excluded from serialization.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_resource";
		$exprops[] = "\0" . __CLASS__ . "\0_owns";
		$exprops[] = "\0" . __CLASS__ . "\0_isProcess";
	}
}
