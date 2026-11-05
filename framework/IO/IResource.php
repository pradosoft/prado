<?php

/**
 * IResource interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

/**
 * IResource interface.
 *
 * IResource is the common contract for objects that own a PHP resource handle:
 * streams (file, memory, temp, pipe, socket) via {@see \Prado\IO\TResource} and
 * {@see \Prado\IO\TStream}, processes via TProcess, and listening sockets via
 * TSocketServer.  It covers the handle lifecycle and metadata that apply
 * regardless of whether the resource can be read from or written to.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IResource
{
	/**
	 * Returns the underlying PHP resource handle.
	 * @return mixed The underlying PHP resource handle, or null when not open.
	 */
	public function getResource(): mixed;

	/**
	 * Severs the resource from this object without closing it.  Ownership is
	 * given up, so the destructor will not close it.
	 * @return mixed The detached resource handle, or null when there was none.
	 */
	public function detach(): mixed;

	/**
	 * Releases the resource.  Non-owned handles (e.g. STDIN/STDOUT/STDERR or a
	 * borrowed resource) are not actually closed.
	 * @return ?bool True/false on close result, null when there was nothing to close.
	 */
	public function closeStream(): ?bool;

	/**
	 * Indicates whether a live resource handle is currently held.
	 * @return bool Whether a live resource handle is currently held.
	 */
	public function isOpen(): bool;

	/**
	 * Returns the stream metadata, either the whole array or a single key.
	 * @param ?string $key A single metadata key, or null for the whole array.
	 * @return mixed The {@see stream_get_meta_data()} array, a single value, or null.
	 */
	public function getMetadata(?string $key = null): mixed;
}
