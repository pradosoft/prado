<?php

/**
 * TStreamWrapper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Prado\Exceptions\TIOException;
use Prado\TComponent;

/**
 * TStreamWrapper class
 *
 * Serves as the abstract base for custom PHP stream-wrapper protocols.  PHP instantiates a
 * registered subclass when a URL using its protocol (such as `myproto://…`) is opened.
 * Register a subclass with {@see register()}; subclasses implement the byte operations and may
 * override the optional directory and metadata hooks.
 *
 * {@see \Prado\IO\TStreamResourceWrapper} exposes a PSR-7 stream as a native resource.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @see https://www.php.net/manual/en/class.streamwrapper.php
 */
abstract class TStreamWrapper extends TComponent
{
	/** @var mixed The stream context, populated by PHP on the wrapper instance. */
	public $context;

	/** @var array<string, class-string> The protocols this process registered, keyed by protocol. */
	private static array $_registered = [];

	/**
	 * Returns the protocol this wrapper registers under.  Subclasses override it.
	 * @return string The protocol name (e.g. 'myproto').
	 */
	public static function getDefaultProtocol(): string
	{
		return '';
	}

	/**
	 * Returns the registration flags (e.g. STREAM_IS_URL for remote protocols).
	 * @return int The stream-wrapper flags.
	 */
	public static function getDefaultFlags(): int
	{
		return 0;
	}

	/**
	 * Registers the wrapper class for a protocol.
	 * @param ?string $protocol The protocol; null uses {@see getDefaultProtocol()}.
	 * @throws TIOException When the protocol is empty, already registered, or registration fails.
	 */
	public static function register(?string $protocol = null): void
	{
		$protocol ??= static::getDefaultProtocol();
		if ($protocol === '') {
			throw new TIOException('streamwrapper_registration_failed', '(empty)', static::class);
		}
		if (in_array($protocol, stream_get_wrappers(), true)) {
			throw new TIOException('streamwrapper_already_registered', $protocol);
		}
		if (!stream_wrapper_register($protocol, static::class, static::getDefaultFlags())) {
			throw new TIOException('streamwrapper_registration_failed', $protocol, static::class);
		}
		self::$_registered[$protocol] = static::class;
	}

	/**
	 * Registers the wrapper only if the protocol is not already registered.
	 * @param ?string $protocol The protocol; null uses {@see getDefaultProtocol()}.
	 */
	public static function registerOnce(?string $protocol = null): void
	{
		$protocol ??= static::getDefaultProtocol();
		if ($protocol !== '' && !in_array($protocol, stream_get_wrappers(), true)) {
			static::register($protocol);
		}
	}

	/**
	 * Unregisters a protocol ({@see stream_wrapper_unregister()}).
	 * @param ?string $protocol The protocol; null uses {@see getDefaultProtocol()}.
	 * @return bool Whether the protocol was unregistered.
	 */
	public static function unregister(?string $protocol = null): bool
	{
		$protocol ??= static::getDefaultProtocol();
		unset(self::$_registered[$protocol]);
		return stream_wrapper_unregister($protocol);
	}

	/**
	 * Restores a built-in protocol previously overridden ({@see stream_wrapper_restore()}).
	 * @param ?string $protocol The protocol; null uses {@see getDefaultProtocol()}.
	 * @return bool Whether the protocol was restored.
	 */
	public static function restore(?string $protocol = null): bool
	{
		return stream_wrapper_restore($protocol ?? static::getDefaultProtocol());
	}

	/**
	 * Lists the stream-wrapper protocols registered in this PHP process.
	 * @return array The registered protocol names ({@see stream_get_wrappers()}).
	 */
	public static function getRegisteredWrappers(): array
	{
		return stream_get_wrappers();
	}

	//
	// ─── Required byte operations ────────────────────────────────────────────
	//

	/**
	 * Opens the resource for a URL when the protocol is accessed ({@see fopen()} and friends).
	 * @param string $path The full URL passed to fopen.
	 * @param string $mode The fopen mode (e.g. 'rb').
	 * @param int $options A bitmask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
	 * @param ?string &$opened_path Set to the real opened path when STREAM_USE_PATH is requested.
	 * @return bool Whether the resource was opened.
	 */
	abstract public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool;

	/**
	 * Reads up to $count bytes from the current position.
	 * @param int $count The maximum number of bytes to read.
	 * @return false|string The bytes read, or false on failure.
	 */
	abstract public function stream_read(int $count): string|false;

	/**
	 * Writes data at the current position.
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	abstract public function stream_write(string $data): int;

	/**
	 * Returns the current read/write position.
	 * @return int The position in bytes.
	 */
	abstract public function stream_tell(): int;

	/**
	 * Indicates whether the read position is at the end of the resource.
	 * @return bool Whether the position is at end of file.
	 */
	abstract public function stream_eof(): bool;

	/**
	 * Moves the read/write position.
	 * @param int $offset The offset relative to $whence.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 * @return bool Whether the seek succeeded.
	 */
	abstract public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

	/**
	 * Returns the {@see stat()} array for the open resource ({@see fstat()}).
	 * @return array|false The stat array, or false when unavailable.
	 */
	abstract public function stream_stat(): array|false;

	/**
	 * Closes the wrapped resource.  Subclasses may override it.
	 */
	public function stream_close(): void
	{
	}

	//
	// ─── Optional stream operations ──────────────────────────────────────────
	//
	// PHP calls these on the wrapper for the matching operations.  They are defined with safe
	// defaults so the engine never invokes an absent method, which {@see \Prado\TComponent}
	// would otherwise route to __call and reject.  Subclasses override the ones they support.
	//

	/**
	 * Flushes buffered output ({@see fflush()} and on close).  Returns true (no-op) by default.
	 * @return bool Whether the flush succeeded.
	 */
	public function stream_flush(): bool
	{
		return true;
	}

	/**
	 * Returns the underlying resource for {@see stream_select()}.  Returns false (none) by default.
	 * @param int $castAs STREAM_CAST_FOR_SELECT or STREAM_CAST_AS_STREAM.
	 * @return mixed The underlying resource, or false when none is available.
	 */
	public function stream_cast(int $castAs): mixed
	{
		return false;
	}

	/**
	 * Applies advisory locking ({@see flock()}).  Returns false (unsupported) by default.
	 * @param int $operation A LOCK_* operation.
	 * @return bool Whether the lock operation succeeded.
	 */
	public function stream_lock(int $operation): bool
	{
		return false;
	}

	/**
	 * Sets a stream option (blocking, timeout, write buffer).  Returns false (unsupported) by default.
	 * @param int $option A STREAM_OPTION_* constant.
	 * @param int $arg1 The first option argument.
	 * @param int $arg2 The second option argument.
	 * @return bool Whether the option was applied.
	 */
	public function stream_set_option(int $option, int $arg1, int $arg2): bool
	{
		return false;
	}

	/**
	 * Truncates the resource ({@see ftruncate()}).  Returns false (unsupported) by default.
	 * @param int $newSize The new size in bytes.
	 * @return bool Whether the resource was truncated.
	 */
	public function stream_truncate(int $newSize): bool
	{
		return false;
	}

	/**
	 * Sets metadata on a URL (touch, chmod, chown).  Returns false (unsupported) by default.
	 * @param string $path The URL.
	 * @param int $option A STREAM_META_* constant.
	 * @param mixed $value The metadata value.
	 * @return bool Whether the metadata was set.
	 */
	public function stream_metadata(string $path, int $option, mixed $value): bool
	{
		return false;
	}

	//
	// ─── Optional directory / metadata hooks (override as needed) ─────────────
	//

	/**
	 * Opens a directory handle.  Returns false unless a subclass overrides it.
	 * @param string $path The directory URL.
	 * @param int $options The opendir options.
	 * @return bool Whether the directory was opened.
	 */
	public function dir_opendir(string $path, int $options): bool
	{
		return false;
	}

	/**
	 * Creates a directory.  Returns false unless a subclass overrides it.
	 * @param string $path The directory URL.
	 * @param int $mode The permission mode.
	 * @param int $options A bitmask of STREAM_MKDIR_RECURSIVE and STREAM_REPORT_ERRORS.
	 * @return bool Whether the directory was created.
	 */
	public function mkdir(string $path, int $mode, int $options): bool
	{
		return false;
	}

	/**
	 * Renames a path.  Returns false unless a subclass overrides it.
	 * @param string $pathFrom The source URL.
	 * @param string $pathTo The destination URL.
	 * @return bool Whether the rename succeeded.
	 */
	public function rename(string $pathFrom, string $pathTo): bool
	{
		return false;
	}

	/**
	 * Removes a directory.  Returns false unless a subclass overrides it.
	 * @param string $path The directory URL.
	 * @param int $options The rmdir options.
	 * @return bool Whether the directory was removed.
	 */
	public function rmdir(string $path, int $options): bool
	{
		return false;
	}

	/**
	 * Deletes a file.  Returns false unless a subclass overrides it.
	 * @param string $path The file URL.
	 * @return bool Whether the file was deleted.
	 */
	public function unlink(string $path): bool
	{
		return false;
	}

	/**
	 * Returns the {@see stat()} array for a URL without opening it.  Returns false unless a
	 * subclass overrides it.
	 * @param string $path The URL to stat.
	 * @param int $flags A bitmask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
	 * @return array|false The stat array, or false when unavailable.
	 */
	public function url_stat(string $path, int $flags): array|false
	{
		return false;
	}
}
