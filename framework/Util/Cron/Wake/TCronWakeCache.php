<?php

/**
 * TCronWakeCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron\Wake;

use Prado\Caching\ICache;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TCronWakeCache class.
 *
 * TCronWakeCache is the {@see \Prado\Util\Cron\Wake\ICronWakeTransport} that signals through a
 * cache: a wake stores the current microtime under {@see setCacheKey CacheKey}, and the waiting
 * cron process polls the key every {@see setPollInterval PollInterval}.  A cache that several
 * hosts share, such as memcached or Redis, carries the wake between hosts.
 *
 * The cache is the {@see setCacheID CacheID} module, or the application cache when none is set.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCronWakeCache extends TCronWakePoll
{
	/** The default cache key of the wake stamp. */
	public const DEFAULT_CACHE_KEY = 'prado:cron:wake';

	/** @var ?ICache the cache holding the stamp */
	private ?ICache $_cache = null;

	/** @var ?string the ID of the cache module, or null for the application cache */
	private ?string $_cacheID = null;

	/** @var string the cache key of the stamp */
	private string $_cacheKey = self::DEFAULT_CACHE_KEY;

	/**
	 * @param ?ICache $cache the cache holding the stamp, or null to resolve from {@see getCacheID CacheID}
	 */
	public function __construct(?ICache $cache = null)
	{
		$this->_cache = $cache;
		parent::__construct();
	}

	/**
	 * @throws TConfigurationException when CacheID is not an ICache module
	 * @return ?ICache the cache holding the stamp: the given cache, the {@see getCacheID CacheID} module,
	 * or the application cache; null when the application has no cache
	 */
	public function getCache(): ?ICache
	{
		if ($this->_cache === null) {
			$app = Prado::getApplication();
			if ($this->_cacheID !== null) {
				$cache = $app->getModule($this->_cacheID);
				if (!($cache instanceof ICache)) {
					throw new TConfigurationException('cronwakecache_cacheid_invalid', $this->_cacheID);
				}
				$this->_cache = $cache;
			} else {
				$this->_cache = $app->getCache();
			}
		}
		return $this->_cache;
	}

	/**
	 * @param ?ICache $value the cache holding the stamp
	 * @return static the current object
	 */
	public function setCache(?ICache $value): static
	{
		$this->_cache = $value;
		return $this;
	}

	/**
	 * @return ?string the ID of the cache module, or null for the application cache
	 */
	public function getCacheID(): ?string
	{
		return $this->_cacheID;
	}

	/**
	 * @param ?string $value the ID of the cache module, or null for the application cache
	 * @return static the current object
	 */
	public function setCacheID($value): static
	{
		$this->_cacheID = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
		$this->_cache = null;
		return $this;
	}

	/**
	 * @return string the cache key of the stamp
	 */
	public function getCacheKey(): string
	{
		return $this->_cacheKey;
	}

	/**
	 * @param string $value the cache key of the stamp
	 * @return static the current object
	 */
	public function setCacheKey($value): static
	{
		$this->_cacheKey = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * @return bool whether a cache is available
	 */
	protected function getIsAvailable(): bool
	{
		return $this->getCache() !== null;
	}

	/**
	 * @return mixed the cached stamp, or null when absent
	 */
	protected function readStamp(): mixed
	{
		$value = $this->getCache()->get($this->_cacheKey);
		return ($value === false) ? null : $value;
	}

	/**
	 * Stores the current microtime as the stamp.
	 * @return bool whether the stamp was stored
	 */
	protected function writeStamp(): bool
	{
		return (bool) $this->getCache()->set($this->_cacheKey, sprintf('%.6F', \microtime(true)));
	}
}
