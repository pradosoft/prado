<?php

/**
 * ICacheSize interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

/**
 * ICacheSize interface
 *
 * Implemented by cache modules that enforce a byte-cap with LRU eviction via
 * {@see TCacheSizeTrait}. Defines the public size-management contract and the
 * {@see SIZE_NOT_COMPUTED} sentinel shared across implementing classes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface ICacheSize
{
	/**
	 * Sentinel returned by {@see TCacheSizeTrait::parseSizeString()} on parse failure
	 * and used as the initial value of `$_currentSize` to indicate the size has not
	 * yet been computed. {@see getCurrentSize()} returns this value when
	 * {@see getMaximumSize MaximumSize} is `0` and no write has triggered a size
	 * computation.
	 */
	public const SIZE_NOT_COMPUTED = -1;

	/**
	 * @return int the maximum total size of the cache in bytes; 0 means unlimited
	 */
	public function getMaximumSize(): int;

	/**
	 * Sets the maximum total byte size of the cache.
	 *
	 * A value of `0` disables size enforcement. Negative values are clamped to `0`.
	 * Accepts a plain integer (bytes), a plain numeric string, or a PHP-style size
	 * string with an IEC binary suffix (`K`/`KB`, `M`/`MB`, `G`/`GB`, `T`/`TB`,
	 * `P`/`PB`, case-insensitive). Unrecognized strings are treated as `0`.
	 *
	 * @param int|string $value the maximum total size; 0 means unlimited
	 */
	public function setMaximumSize($value);

	/**
	 * Returns the current total byte size of all cached entries.
	 *
	 * @return int the current total byte size; {@see SIZE_NOT_COMPUTED} when not yet computed
	 */
	public function getCurrentSize(): int;

	/**
	 * Returns whether the cache has exceeded its {@see getMaximumSize MaximumSize} limit.
	 *
	 * Always returns false when MaximumSize is `0` (unlimited).
	 *
	 * @return bool true when the total cache size exceeds MaximumSize
	 */
	public function isOverCapacity(): bool;
}
