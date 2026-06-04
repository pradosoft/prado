<?php

/**
 * TCacheSizeTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\TPropertyValue;

/**
 * TCacheSizeTrait trait
 *
 * TCacheSizeTrait provides a byte-cap with size-driven eviction for cache modules. The
 * eviction order is chosen by the implementing class in {@see evictToFitMaximumSize()}
 * (for example least-recently-used or soonest-to-expire).
 *
 * Implementing classes must also implement {@see ICacheSize}, which defines the public
 * size-management contract and the {@see ICacheSize::SIZE_NOT_COMPUTED} sentinel constant.
 *
 * When {@see getMaximumSize MaximumSize} is greater than `0`, the trait enforces a
 * total size limit (in bytes) on the cache. After every write the implementing class
 * calls {@see enforceMaximumSize()}, which validates the running size total and evicts
 * the least recently used entries one at a time until the cache fits within the limit.
 * This mirrors the algorithm used by Apple Foundation's `NSCache`.
 *
 * ## Contract for implementing classes
 *
 * Each class using this trait must implement three abstract methods:
 *
 * - {@see computeSizeFingerprint()} — returns a short string that changes whenever
 *   the set of cached keys changes. Used to detect external modifications (e.g. a
 *   backing store reload) so that {@see computeCurrentSize()} is called only when
 *   necessary.
 *
 * - {@see computeCurrentSize()} — performs a full recompute of the total byte size of
 *   all currently cached entries. Called only on fingerprint mismatch.
 *
 * - {@see evictToFitMaximumSize()} — removes entries in least-recently-used order
 *   until the total is at or below {@see getMaximumSizeDirect()}, then updates
 *   `$_currentSize` and `$_sizeFingerprint` to reflect the post-eviction state.
 *
 * ## Incremental bookkeeping
 *
 * Implementing classes are expected to maintain `$_currentSize` incrementally
 * on every write and deletion so that {@see enforceMaximumSize()} avoids the cost of
 * a full recompute on the hot path. After each incremental update the class must also
 * call {@see setSizeFingerprintDirect()} with the current fingerprint so that
 * {@see validateSizeCache()} does not trigger a redundant recompute.
 *
 * The fingerprint mechanism provides a self-healing safety net: if the cache is
 * modified through a path that bypasses the incremental updates (e.g. a backing store
 * reload in {@see \Prado\Caching\TMemoryCache}), the next call to
 * {@see validateSizeCache()} detects the mismatch and triggers a full recompute.
 *
 * ## Size semantics
 *
 * "Size" is defined by the implementing class:
 * - {@see \Prado\Caching\TMemoryCache} measures the `strlen()` of each serialized
 *   payload as stored in the in-memory array.
 * - {@see \Prado\Caching\TFileCache} measures the on-disk byte size of each `.cache`
 *   file as reported by `filesize()`.
 *
 * A MaximumSize of `0` (the default) disables size enforcement entirely. Calls to
 * {@see enforceMaximumSize()} are no-ops and the size fields are not updated.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCacheSizeTrait
{
	/**
	 * @var int Maximum total size of the cache in bytes; 0 means unlimited.
	 */
	private int $_maximumSize = 0;

	/**
	 * @var int Running total byte size of all cached entries; `SIZE_NOT_COMPUTED` (-1)
	 *   when not yet computed. Updated incrementally by the implementing class and
	 *   recomputed in full by {@see validateSizeCache()} on fingerprint mismatch.
	 */
	private int $_currentSize = self::SIZE_NOT_COMPUTED;

	/**
	 * @var string Short hash representing the current set of cached keys.
	 *   {@see validateSizeCache()} recomputes `$_currentSize` when this value
	 *   differs from {@see computeSizeFingerprint()}.
	 */
	private string $_sizeFingerprint = '';

	// ----------------------------------------------------------------- direct accessors

	/**
	 * @return int the raw MaximumSize field value
	 */
	protected function getMaximumSizeDirect(): int
	{
		return $this->_maximumSize;
	}

	/**
	 * @param int $value the MaximumSize value to store directly
	 */
	protected function setMaximumSizeDirect(int $value): void
	{
		$this->_maximumSize = $value;
	}

	/**
	 * @return int the raw CurrentSize field value; `SIZE_NOT_COMPUTED` (-1) when not yet computed
	 */
	protected function getCurrentSizeDirect(): int
	{
		return $this->_currentSize;
	}

	/**
	 * @param int $value the CurrentSize value to store directly
	 */
	protected function setCurrentSizeDirect(int $value): void
	{
		$this->_currentSize = $value;
	}

	/**
	 * @return string the raw SizeFingerprint field value; empty string when stale
	 */
	protected function getSizeFingerprintDirect(): string
	{
		return $this->_sizeFingerprint;
	}

	/**
	 * @param string $value the SizeFingerprint value to store directly
	 */
	protected function setSizeFingerprintDirect(string $value): void
	{
		$this->_sizeFingerprint = $value;
	}

	// ----------------------------------------------------------------- abstract contract

	/**
	 * Computes a fingerprint of the current cached key set.
	 *
	 * The fingerprint must change whenever the set of cached keys changes. It is
	 * compared against the stored fingerprint by {@see validateSizeCache()} to decide
	 * whether a full size recompute is needed. A short hash (e.g. `md5`) of the
	 * sorted key list is sufficient.
	 *
	 * @return string a short string that is stable when the key set is unchanged and
	 *   different when any key has been added or removed
	 */
	abstract protected function computeSizeFingerprint(): string;

	/**
	 * Performs a full recompute of the total byte size of all currently cached entries.
	 *
	 * Called by {@see validateSizeCache()} when the fingerprint has changed. The result
	 * is stored in `$_currentSize`. Implementations should also resynchronize any
	 * per-key size bookkeeping arrays at this point.
	 *
	 * @return int the total byte size of the cache
	 */
	abstract protected function computeCurrentSize(): int;

	/**
	 * Evicts the least recently used entries until the cache is within
	 * {@see getMaximumSizeDirect()}.
	 *
	 * Called by {@see enforceMaximumSize()} when {@see getCurrentSizeDirect()} exceeds
	 * {@see getMaximumSizeDirect()}. After all evictions, the implementation must update
	 * `$_currentSize` and `$_sizeFingerprint` to reflect the new state.
	 */
	abstract protected function evictToFitMaximumSize(): void;

	// ----------------------------------------------------------------- size management

	/**
	 * Throws {@see \Prado\Exceptions\TInvalidDataValueException} when
	 * {@see getMaximumSize MaximumSize} is active and `$itemSize` exceeds it.
	 *
	 * Implementing classes must call this method after computing the byte size of a
	 * serialized entry but **before** writing it to the backing store. This prevents
	 * an oversized item from silently evicting the entire cache only to be evicted
	 * itself on the next write. When MaximumSize is `0` (unlimited) the method is a
	 * no-op.
	 *
	 * @param int $itemSize byte size of the serialized entry about to be written
	 * @throws \Prado\Exceptions\TInvalidDataValueException when the item is larger
	 *   than {@see getMaximumSize MaximumSize}
	 */
	protected function assertItemFitsMaximumSize(int $itemSize): void
	{
		$max = $this->getMaximumSizeDirect();
		if ($max > 0 && $itemSize > $max) {
			throw new \Prado\Exceptions\TInvalidDataValueException(
				'cachesize_item_exceeds_maximum_size',
				$itemSize,
				$max
			);
		}
	}

	/**
	 * Validates the running size total against the current key fingerprint, recomputing
	 * from scratch when the fingerprint has changed.
	 *
	 * This method is called by both {@see enforceMaximumSize()} and
	 * {@see getCurrentSize()} to ensure `$_currentSize` is in sync with the
	 * actual cache contents before any decision is made on it.
	 */
	protected function validateSizeCache(): void
	{
		$fingerprint = $this->computeSizeFingerprint();
		if ($fingerprint !== $this->getSizeFingerprintDirect()) {
			$this->setCurrentSizeDirect($this->computeCurrentSize());
			$this->setSizeFingerprintDirect($fingerprint);
		}
	}

	/**
	 * Returns whether the cache has exceeded its {@see getMaximumSize MaximumSize} limit.
	 *
	 * Always returns false when MaximumSize is `0` (unlimited). Triggers a size
	 * validation before evaluating the result.
	 *
	 * @return bool true when the total cache size exceeds MaximumSize
	 */
	public function isOverCapacity(): bool
	{
		$max = $this->getMaximumSizeDirect();
		if ($max <= 0) {
			return false;
		}
		$this->validateSizeCache();
		return $this->getCurrentSizeDirect() > $max;
	}

	/**
	 * Validates the current size and evicts LRU entries if the cache is over capacity.
	 *
	 * This method is a no-op when {@see getMaximumSize MaximumSize} is `0`. It is
	 * called automatically by implementing classes after every write.
	 */
	protected function enforceMaximumSize(): void
	{
		if ($this->getMaximumSizeDirect() <= 0) {
			return;
		}
		$this->validateSizeCache();
		if ($this->getCurrentSizeDirect() > $this->getMaximumSizeDirect()) {
			$this->evictToFitMaximumSize();
		}
	}

	// ----------------------------------------------------------------- public accessors

	/**
	 * @return int the maximum total size of the cache in bytes; 0 means unlimited
	 */
	public function getMaximumSize(): int
	{
		return $this->getMaximumSizeDirect();
	}

	/**
	 * Sets the maximum total byte size of the cache. A value of `0` disables size
	 * enforcement. Negative values are clamped to `0`. When the new limit is smaller
	 * than the current total, {@see enforceMaximumSize()} is called immediately to
	 * evict excess entries.
	 *
	 * Accepts a plain integer (bytes), a plain numeric string, or a PHP-style size
	 * string with an IEC binary suffix — the same format PHP uses for `memory_limit`:
	 *
	 * | Suffix     | Multiplier                | Example     |
	 * |------------|---------------------------|-------------|
	 * | *(none)*   | 1                         | `4194304`   |
	 * | `K` / `KB` | 1,024                     | `256K`      |
	 * | `M` / `MB` | 1,048,576                 | `512M`      |
	 * | `G` / `GB` | 1,073,741,824             | `1G`        |
	 * | `T` / `TB` | 1,099,511,627,776         | `2T`        |
	 * | `P` / `PB` | 1,125,899,906,842,624     | `1P`        |
	 *
	 * Suffixes are case-insensitive. Unrecognized strings are treated as `0`
	 * (size enforcement disabled).
	 *
	 * @param int|string $value the maximum total size; 0 means unlimited
	 */
	public function setMaximumSize($value)
	{
		$value = is_int($value) ? $value : TPropertyValue::ensureString($value);
		$this->setMaximumSizeDirect(max(0, static::parseSizeString($value)));
		$this->enforceMaximumSize();
	}

	/**
	 * Parses a PHP-style size string into a byte count.
	 *
	 * Accepts a plain integer, a plain numeric string, or a value with an optional
	 * IEC binary suffix (`K`/`KB`, `M`/`MB`, `G`/`GB`, `T`/`TB`, `P`/`PB`,
	 * case-insensitive), matching the convention PHP uses for `memory_limit`.
	 * A plain number with no suffix is interpreted as bytes. Returns `-1` when the
	 * value does not match any recognized pattern.
	 *
	 * The regex-based implementation is used for all PHP versions. Although PHP 8.2
	 * introduced `ini_parse_quantity()`, that function recognises only single-letter
	 * suffixes (`K`, `M`, `G`) and emits `E_WARNING` for two-letter forms such as
	 * `KB` or `MB` and for unrecognized strings — behavior that is inconsistent with
	 * the broader set of suffixes this method advertises.
	 *
	 * @param int|string $value the size value to parse
	 * @return int the size in bytes; `SIZE_NOT_COMPUTED` (-1) on parse failure
	 */
	protected static function parseSizeString(int|string $value): int
	{
		if (is_numeric($value)) {
			return (int) $value;
		}
		$value = trim($value);
		if (!preg_match('/^(\d+)\s*([KMGTP]?)B?$/i', $value, $m)) {
			return self::SIZE_NOT_COMPUTED;
		}
		$n = (int) $m[1];
		return match (strtoupper($m[2])) {
			'K' => $n * 1_024,
			'M' => $n * 1_048_576,
			'G' => $n * 1_073_741_824,
			'T' => $n * 1_099_511_627_776,
			'P' => $n * 1_125_899_906_842_624,
			default => $n,
		};
	}

	/**
	 * Returns the current total byte size of all cached entries.
	 *
	 * Triggers a {@see validateSizeCache() size validation} before returning.
	 * Callers that want to avoid the overhead when no limit is configured should
	 * check {@see getMaximumSize()} first.
	 *
	 * @return int the current total byte size; `SIZE_NOT_COMPUTED` (-1) when not yet computed
	 */
	public function getCurrentSize(): int
	{
		$this->validateSizeCache();
		return $this->getCurrentSizeDirect();
	}
}
