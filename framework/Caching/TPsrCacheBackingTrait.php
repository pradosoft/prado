<?php

/**
 * TPsrCacheBackingTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Util\Log\TLogger;

/**
 * TPsrCacheBackingTrait trait.
 *
 * TPsrCacheBackingTrait gives a PSR cache adapter module its Prado {@see ICache}
 * backing and the rules PSR-6 and PSR-16 share. {@see TPsrSimpleCache} (PSR-16) and
 * {@see TPsrCacheItemPool} (PSR-6) use it.
 *
 * ## Backing cache
 *
 * {@see getBackingCache()} resolves the backing on first use:
 *
 * | Configuration | Backing |
 * |---|---|
 * | {@see setBackingCache BackingCache} set | that {@see ICache} |
 * | {@see setBackingCacheId BackingCacheId} set | the application module with that ID, which must implement {@see ICache} |
 * | neither | the application's primary cache, {@see \Prado\TApplication::getCache()} |
 *
 * {@see getModuleDependencies()} declares `BackingCacheId` as a required
 * {@see \Prado\IModuleDependency}, so the application initializes the backing module
 * first. Replacing a non-empty `BackingCacheId` logs a {@see TLogger::WARNING} and
 * drops the current backing. A missing or invalid backing throws
 * {@see TPsrCacheException}.
 *
 * ## Keys
 *
 * A key is a non-empty string without the {@see RESERVED_KEY_CHARACTERS} `{}()/\@:`
 * that both standards reserve. {@see validateKey()} throws
 * {@see TPsrCacheInvalidArgumentException} for any other key. An integer key, as PHP
 * makes of a numeric string array key, is accepted as its string form.
 * {@see setKeyPrefix KeyPrefix} is prepended to every key before it reaches the
 * backing, which separates adapters that share one backing cache. A {@see TCache}
 * backing hashes the prefixed key under its own {@see TCache::getKeyPrefix KeyPrefix},
 * so the key length is unlimited there.
 *
 * ## Values and expiry
 *
 * Every value is stored inside a one-element array envelope. The envelope keeps
 * `null`, `false`, `0`, `''`, and `[]` as cached values, which {@see TCache::set()}
 * would otherwise delete and {@see ICache::get()} would report as a miss.
 *
 * A `null` TTL stores the value for {@see setDefaultExpire DefaultExpire} seconds,
 * where `0` means no expiry. A TTL of zero or less deletes the key, as both standards
 * require. A {@see \DateInterval} is converted to seconds from the application clock.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TPsrCacheBackingTrait
{
	use TApplicationClockAwareTrait;

	/** The characters PSR-6 and PSR-16 reserve; a key must not contain any of them. */
	public const RESERVED_KEY_CHARACTERS = '{}()/\@:';

	/** @var string module ID of the backing cache; empty for the primary application cache */
	private string $_backingCacheId = '';

	/** @var ?ICache the backing cache; null until set or resolved */
	private ?ICache $_backingCache = null;

	/** @var string prefix prepended to every key before it reaches the backing cache */
	private string $_keyPrefix = '';

	/** @var int seconds a value stored without a TTL lives; 0 for no expiry */
	private int $_defaultExpire = 0;

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing cache module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this module, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing cache module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingCacheId();
		return $id === '' ? null : $id;
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing cache
	 */
	protected function getBackingCacheIdDirect(): string
	{
		return $this->_backingCacheId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingCacheIdDirect(string $value): void
	{
		$this->_backingCacheId = $value;
	}

	/**
	 * @return string the module ID of the backing cache; empty for the primary application cache
	 */
	public function getBackingCacheId(): string
	{
		return $this->getBackingCacheIdDirect();
	}

	/**
	 * Sets the module ID of the backing cache. Replacing a non-empty ID logs a
	 * {@see TLogger::WARNING} and drops the current backing so the next operation
	 * resolves the new module.
	 * @param string $value the module ID of the cache to adapt; empty for the primary application cache
	 */
	public function setBackingCacheId(string $value): void
	{
		$current = $this->getBackingCacheIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			Prado::log(
				sprintf("%s.BackingCacheId changed from '%s' to '%s'.", static::class, $current, $value),
				TLogger::WARNING,
				'prado.caching'
			);
		}
		$this->setBackingCacheIdDirect($value);
		$this->setBackingCacheDirect(null);
	}

	/**
	 * @return ?ICache the stored backing cache, or null when not yet set or resolved
	 */
	protected function getBackingCacheDirect(): ?ICache
	{
		return $this->_backingCache;
	}

	/**
	 * @param ?ICache $value the backing cache to store
	 */
	protected function setBackingCacheDirect(?ICache $value): void
	{
		$this->_backingCache = $value;
	}

	/**
	 * Returns the backing cache, resolving it through {@see resolveBackingCache()}
	 * on first call.
	 * @throws TPsrCacheException when no backing cache is configured or available
	 * @return ICache the backing cache
	 */
	public function getBackingCache(): ICache
	{
		$cache = $this->getBackingCacheDirect();
		if ($cache === null) {
			$cache = $this->resolveBackingCache();
			$this->setBackingCacheDirect($cache);
		}
		return $cache;
	}

	/**
	 * Sets the backing cache directly, replacing a resolved one. Null drops it so
	 * the next operation resolves the backing again.
	 * @param ?ICache $value the cache to adapt
	 */
	public function setBackingCache(?ICache $value): void
	{
		$this->setBackingCacheDirect($value);
	}

	/**
	 * Resolves the backing cache from {@see getBackingCacheId BackingCacheId}, or the
	 * application's primary cache when the ID is empty.
	 * @throws TPsrCacheException when the ID names no application module
	 * @throws TPsrCacheException when the module is not an {@see ICache}
	 * @throws TPsrCacheException when the ID is empty and the application has no primary cache
	 * @return ICache the backing cache
	 */
	protected function resolveBackingCache(): ICache
	{
		$application = $this->getApplication();
		$id = $this->getBackingCacheId();
		if ($id !== '') {
			$cache = $application?->getModule($id);
			if ($cache === null) {
				throw new TPsrCacheException('psrcachebacking_cache_not_found', static::class, $id);
			}
			if (!($cache instanceof ICache)) {
				throw new TPsrCacheException('psrcachebacking_invalid_cache_type', static::class, $id);
			}
			return $cache;
		}
		$cache = $application?->getCache();
		if ($cache === null) {
			throw new TPsrCacheException('psrcachebacking_cache_required', static::class);
		}
		return $cache;
	}

	/**
	 * @return string the prefix prepended to every key before it reaches the backing cache; default empty
	 */
	public function getKeyPrefix(): string
	{
		return $this->_keyPrefix;
	}

	/**
	 * @param mixed $value the prefix prepended to every key before it reaches the backing cache
	 */
	public function setKeyPrefix($value): void
	{
		$this->_keyPrefix = (string) TPropertyValue::ensureString($value);
	}

	/**
	 * @return int the seconds a value stored without a TTL lives; 0 for no expiry, the default
	 */
	public function getDefaultExpire(): int
	{
		return $this->_defaultExpire;
	}

	/**
	 * @param mixed $value the seconds a value stored without a TTL lives; 0 or less for no expiry
	 */
	public function setDefaultExpire($value): void
	{
		$this->_defaultExpire = max(0, TPropertyValue::ensureInteger($value));
	}

	// --------------------------------------------------------------- keys

	/**
	 * Validates a PSR cache key. An integer is accepted as its string form.
	 * @param mixed $key the key to validate
	 * @throws TPsrCacheInvalidArgumentException when the key is not a non-empty string or contains a reserved character
	 * @return string the key
	 */
	protected function validateKey(mixed $key): string
	{
		if (is_int($key)) {
			$key = (string) $key;
		}
		if (!is_string($key) || $key === '' || strpbrk($key, self::RESERVED_KEY_CHARACTERS) !== false) {
			throw new TPsrCacheInvalidArgumentException('psrcachebacking_key_invalid', static::class, is_string($key) ? $key : get_debug_type($key));
		}
		return $key;
	}

	/**
	 * Validates every key before any is used, as both standards require.
	 * @param iterable $keys the keys to validate
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return string[] the keys
	 */
	protected function validateKeys(iterable $keys): array
	{
		$valid = [];
		foreach ($keys as $key) {
			$valid[] = $this->validateKey($key);
		}
		return $valid;
	}

	/**
	 * @param string $key a validated key
	 * @return string the key sent to the backing cache, with {@see getKeyPrefix KeyPrefix} prepended
	 */
	protected function toBackingKey(string $key): string
	{
		return $this->getKeyPrefix() . $key;
	}

	// --------------------------------------------------------------- expiry

	/**
	 * Converts a PSR TTL to seconds. A {@see \DateInterval} is measured from the clock's now.
	 * @param \DateInterval|int $ttl the TTL
	 * @return int the seconds; zero or less means already expired
	 */
	protected function toSeconds(int|\DateInterval $ttl): int
	{
		if (is_int($ttl)) {
			return $ttl;
		}
		$now = $this->getClock()->now();
		return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
	}

	// --------------------------------------------------------------- entries

	/**
	 * Reads the envelope of a key from the backing cache.
	 * @param string $key a validated key
	 * @return ?array the one-element envelope on a hit; null on a miss
	 */
	protected function readEntry(string $key): ?array
	{
		$data = $this->getBackingCache()->get($this->toBackingKey($key));
		return is_array($data) && count($data) === 1 && array_key_exists(0, $data) ? $data : null;
	}

	/**
	 * Stores a value under a key with a PSR TTL: `null` applies {@see getDefaultExpire DefaultExpire},
	 * zero or less seconds deletes the key, and a positive TTL is the expiry in seconds.
	 * @param string $key a validated key
	 * @param mixed $value the value to store
	 * @param null|\DateInterval|int $ttl the TTL
	 * @return bool whether the backing cache stored, or deleted, the key
	 */
	protected function storeEntry(string $key, mixed $value, null|int|\DateInterval $ttl): bool
	{
		if ($ttl === null) {
			return $this->writeEntry($key, $value, $this->getDefaultExpire());
		}
		$seconds = $this->toSeconds($ttl);
		if ($seconds <= 0) {
			return $this->deleteEntry($key);
		}
		return $this->writeEntry($key, $value, $seconds);
	}

	/**
	 * Writes a value, in its envelope, to the backing cache.
	 * @param string $key a validated key
	 * @param mixed $value the value to store
	 * @param int $expire the expiry in seconds; 0 for none
	 * @return bool whether the backing cache stored the key
	 */
	protected function writeEntry(string $key, mixed $value, int $expire): bool
	{
		return (bool) $this->getBackingCache()->set($this->toBackingKey($key), [$value], $expire);
	}

	/**
	 * Deletes a key from the backing cache.
	 * @param string $key a validated key
	 * @return bool whether the backing cache deleted the key
	 */
	protected function deleteEntry(string $key): bool
	{
		return (bool) $this->getBackingCache()->delete($this->toBackingKey($key));
	}

	/**
	 * Flushes the backing cache, including entries written by its other users.
	 * @return bool whether the backing cache flushed; false when it does not support {@see ICache::flush()}
	 */
	protected function clearEntries(): bool
	{
		try {
			return $this->getBackingCache()->flush() !== false;
		} catch (TNotSupportedException) {
			return false;
		}
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the properties at their defaults from serialization, and a backing cache
	 * that {@see getBackingCache()} re-resolves from {@see getBackingCacheId BackingCacheId}.
	 * The using class calls this from its `_getZappableSleepProps()`.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _addPsrCacheBackingZappable(array &$exprops): void
	{
		if ($this->_backingCacheId === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCacheId";
		} else {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCache";
		}
		if ($this->_keyPrefix === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_keyPrefix";
		}
		if ($this->_defaultExpire === 0) {
			$exprops[] = "\0" . __CLASS__ . "\0_defaultExpire";
		}
	}
}
