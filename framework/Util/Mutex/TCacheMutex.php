<?php

/**
 * TCacheMutex class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Mutex;

use Prado\Caching\ICache;
use Prado\Caching\TCacheModuleIDTrait;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TCacheMutex class.
 *
 * TCacheMutex locks a name with {@see \Prado\Caching\ICache::add()} of a random
 * token in the cache that {@see getCacheModuleID CacheModuleID} names, or the
 * application's primary cache.  It excludes the clients of the cache.
 *
 * | Cache | Exclusion |
 * |---|---|
 * | {@see \Prado\Caching\TAPCCache} | the processes of one host |
 * | {@see \Prado\Caching\TMemCache}, {@see \Prado\Caching\TRedisCache}, {@see \Prado\Caching\TEtcdCache} | the clients of the server |
 * | {@see \Prado\Caching\TDbCache}, {@see \Prado\Caching\TFileCache} | best effort; `add()` leaves a race window |
 *
 * The lock is a lease of {@see getExpire Expire} seconds.  A holder that crashes
 * keeps it until it expires, and a holder whose work outlasts it loses it.  A
 * release deletes the entry only when it holds this mutex's token.  The check and
 * the delete are two calls, so a lease that expires between them may remove the
 * next holder's entry; {@see TRedisMutex} releases atomically.
 *
 * ```xml
 * <module id="mutex" class="Prado\Util\Mutex\TCacheMutex" CacheModuleID="cache" Expire="60" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCacheMutex extends TMutex
{
	use TCacheModuleIDTrait;

	/** The default seconds a lock lasts. */
	public const DEFAULT_EXPIRE = 30;

	/** @var ?ICache the cache that holds the locks; null resolves CacheModuleID. */
	private ?ICache $_cache = null;

	/** @var int the seconds a lock lasts. */
	private int $_expire = self::DEFAULT_EXPIRE;

	/** @var array<string, string> the token of each held name. */
	private array $_tokens = [];

	/**
	 * @throws \Prado\Exceptions\TConfigurationException when CacheModuleID names no
	 *   cache, or no CacheModuleID is set and the application has no primary cache.
	 * @return ICache the cache that holds the locks.
	 */
	public function getCache(): ICache
	{
		if ($this->_cache === null) {
			$this->_cache = $this->resolveCacheModule();
		}
		return $this->_cache;
	}

	/**
	 * @param ?ICache $value the cache that holds the locks; null resolves {@see getCacheModuleID CacheModuleID}.
	 * @return static the current object.
	 */
	public function setCache(?ICache $value): static
	{
		$this->_cache = $value;
		return $this;
	}

	/**
	 * @return int the seconds a lock lasts. Default 30.
	 */
	public function getExpire(): int
	{
		return $this->_expire;
	}

	/**
	 * @param int|string $value the seconds a lock lasts.
	 * @throws TInvalidDataValueException when the value is less than 1.
	 * @return static the current object.
	 */
	public function setExpire($value): static
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('mutex_expire_invalid', static::class, $value);
		}
		$this->_expire = $value;
		return $this;
	}

	/**
	 * @param string $name the lock name.
	 * @return string the cache key of the name.
	 */
	public function getCacheKey(string $name): string
	{
		return 'mutex:' . $this->getLockKey($name);
	}

	/**
	 * Adds the token of the name to the cache when the key is absent.
	 * @param string $name the lock name.
	 * @return bool whether the lock is acquired.
	 */
	protected function acquireLock(string $name): bool
	{
		$token = bin2hex(random_bytes(16));
		if (!$this->getCache()->add($this->getCacheKey($name), $token, $this->getExpire())) {
			return false;
		}
		$this->_tokens[$name] = $token;
		return true;
	}

	/**
	 * Deletes the cache entry of the name when it holds this mutex's token.
	 * @param string $name the lock name.
	 * @return bool whether the lock is released; false when the lease expired.
	 */
	protected function releaseLock(string $name): bool
	{
		$token = $this->_tokens[$name] ?? null;
		unset($this->_tokens[$name]);
		$cache = $this->getCache();
		$key = $this->getCacheKey($name);
		if ($token === null || $cache->get($key) !== $token) {
			return false;
		}
		return $cache->delete($key);
	}

	/**
	 * Drops the token in a forked child, leaving the parent's entry.
	 * @param string $name the lock name.
	 */
	protected function forgetLock(string $name): void
	{
		unset($this->_tokens[$name]);
	}
}
