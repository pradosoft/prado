<?php

/**
 * TServerEventCacheBroker class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\Caching\ICache;
use Prado\Caching\TCacheModuleIDTrait;
use Prado\Caching\TDbCache;
use Prado\Caching\TFileCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Util\Clock\TMonotonicClockAwareTrait;
use Prado\Util\Mutex\IMutex;
use Prado\Util\Mutex\TCacheMutex;
use Prado\Util\Mutex\TDbMutex;
use Prado\Util\Mutex\TFileMutex;
use Prado\Util\Mutex\TMutex;
use Prado\Util\Mutex\TMutexException;

/**
 * TServerEventCacheBroker class
 *
 * TServerEventCacheBroker keeps each channel's event buffer in an {@see ICache} entry, so
 * an event published by one request reaches the streams that other PHP processes serve.
 * It fits PHP-FPM, mod_php, and the built-in server. {@see receive()} polls the entry every
 * {@see getPollInterval PollInterval} seconds through the clock's `sleep()`, which yields to
 * the event loop in a fiber. Its timeout is elapsed time from the
 * {@see getMonotonicClock() monotonic clock}, so a wall-clock change does not move it; event
 * times and {@see getEventLifetime EventLifetime} stay on the wall clock, which processes share.
 *
 * The cache is the module named by {@see getCacheModuleID CacheModuleID}, or the
 * application's primary cache. Use a cache that every web process shares, such as
 * {@see \Prado\Caching\TRedisCache}, {@see \Prado\Caching\TMemCache},
 * {@see \Prado\Caching\TFileCache}, or {@see \Prado\Caching\TDbCache}. A per-process cache,
 * such as APCu under several FPM pools, does not carry events between processes.
 *
 * A publish reads, appends to, and writes the channel's entry while it holds the channel's lock
 * in an {@see IMutex}, so concurrent publishes on one channel keep every event. The mutex is
 * the module named by {@see getMutexID MutexID}, else one {@see createMutex()} matches to the cache:
 *
 * | Cache | Default mutex | Excludes |
 * |---|---|---|
 * | {@see TFileCache} | {@see TFileMutex} in the cache directory's `mutex` folder | processes of the host |
 * | {@see TDbCache} | {@see TDbMutex} on the cache's connection | connections to the database |
 * | any other {@see ICache} | {@see TCacheMutex} on the cache | clients of the cache, where `add()` is atomic |
 *
 * A publish waits up to {@see getLockTimeout LockTimeout} seconds for the lock, then throws
 * {@see \Prado\Util\Mutex\TMutexException} and stores nothing. A cache shared by several hosts
 * needs a mutex that spans them: TDbCache's default does, and a TFileCache on a network share
 * names a {@see \Prado\Util\Mutex\TRedisMutex} or {@see TDbMutex} module through MutexID.
 *
 * ```xml
 * <module id="cache" class="Prado\Caching\TRedisCache" />
 * <module id="serverevents" class="Prado\Web\Services\ServerEvents\TServerEventCacheBroker" PollInterval="0.5" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventCacheBroker extends TServerEventBroker
{
	use TCacheModuleIDTrait;
	use TMonotonicClockAwareTrait;

	/** The prefix of the cache keys. */
	public const CACHE_KEY_PREFIX = 'prado:serverevents:';

	/** The default seconds between polls. */
	public const DEFAULT_POLL_INTERVAL = 1.0;

	/** The default seconds a publish waits for the channel's lock. */
	public const DEFAULT_LOCK_TIMEOUT = 5.0;

	/** The milliseconds between attempts of a default mutex to take a lock. */
	public const MUTEX_RETRY_DELAY = 5;

	/** The folder of a TFileCache directory that holds the default mutex's lock files. */
	public const FILE_MUTEX_FOLDER = 'mutex';

	/** @var float The seconds between polls. */
	private float $_pollInterval = self::DEFAULT_POLL_INTERVAL;

	/** @var string The ID of the mutex module; empty for a default mutex. */
	private string $_mutexID = '';

	/** @var float The seconds a publish waits for the channel's lock. */
	private float $_lockTimeout = self::DEFAULT_LOCK_TIMEOUT;

	/** @var ?IMutex The resolved mutex. */
	private ?IMutex $_mutex = null;

	/**
	 * @return float The seconds between polls of {@see receive()}. Defaults to 1.
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval;
	}

	/**
	 * @param float|string $value The seconds between polls, greater than 0.
	 * @throws TInvalidDataValueException When the value is not positive.
	 */
	public function setPollInterval($value): void
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value <= 0) {
			throw new TInvalidDataValueException('servereventcachebroker_pollinterval_invalid', $value);
		}
		$this->_pollInterval = $value;
	}

	/**
	 * @return string The ID of the {@see IMutex} module that serializes publishes; empty for
	 *   the default mutex of the cache. Defaults to empty.
	 */
	public function getMutexID(): string
	{
		return $this->_mutexID;
	}

	/**
	 * @param string $value The ID of the {@see IMutex} module that serializes publishes;
	 *   empty for the default mutex of the cache.
	 */
	public function setMutexID($value): void
	{
		$this->_mutexID = TPropertyValue::ensureString($value);
		$this->_mutex = null;
	}

	/**
	 * @return float The seconds a publish waits for the channel's lock. Defaults to 5.
	 */
	public function getLockTimeout(): float
	{
		return $this->_lockTimeout;
	}

	/**
	 * @param float|string $value The seconds a publish waits for the channel's lock, at least 0.
	 * @throws TInvalidDataValueException When the value is negative.
	 */
	public function setLockTimeout($value): void
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value < 0) {
			throw new TInvalidDataValueException('servereventcachebroker_locktimeout_invalid', $value);
		}
		$this->_lockTimeout = $value;
	}

	/**
	 * Returns the mutex that serializes the publishes of a channel: the module named by
	 * {@see getMutexID MutexID}, else the default of the cache from {@see createMutex()}.
	 * @throws TConfigurationException When MutexID does not name an {@see IMutex} module.
	 * @return IMutex The mutex.
	 */
	public function getMutex(): IMutex
	{
		if ($this->_mutex !== null) {
			return $this->_mutex;
		}
		if (($id = $this->getMutexID()) !== '') {
			$mutex = $this->getApplication()?->getModule($id);
			if (!($mutex instanceof IMutex)) {
				throw new TConfigurationException('servereventcachebroker_mutexid_invalid', $id);
			}
			return $this->_mutex = $mutex;
		}
		return $this->_mutex = $this->createMutex($this->getCache());
	}

	/**
	 * Polls the channel's entry until an event follows the event ID or the timeout passes.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The ID of the last event the stream sent.
	 * @param float $timeout The most seconds to wait for an event.
	 * @return TServerEvent[] The events in order; empty when none arrived in time.
	 */
	public function receive(string $channel, string $lastEventId, float $timeout): array
	{
		$elapsed = $this->getMonotonicClock();
		$deadline = $elapsed->seconds() + $timeout;
		while (true) {
			$events = $this->eventsAfter($this->loadState($channel), $channel, $lastEventId);
			$remaining = $deadline - $elapsed->seconds();
			if ($events !== [] || $remaining <= 0) {
				return $events;
			}
			$this->getClock()->sleep(min($this->getPollInterval(), $remaining));
		}
	}

	/**
	 * Appends an event to the channel's entry while holding the channel's lock.
	 * @param string $channel The valid channel name.
	 * @param string $event The valid event name.
	 * @param mixed $data The JSON-encodable event data.
	 * @throws \Prado\Util\Mutex\TMutexException When the lock is not acquired within LockTimeout.
	 * @return TServerEvent The stored event.
	 */
	protected function storeEvent(string $channel, string $event, mixed $data): TServerEvent
	{
		$mutex = $this->getMutex();
		$key = $this->getCacheKey($channel);
		if (!$mutex->acquire($key, $this->getLockTimeout())) {
			throw new TMutexException('mutex_acquire_failed', $mutex::class, $key, $this->getLockTimeout());
		}
		try {
			[$state, $published] = $this->appendEvent($this->loadState($channel), $channel, $event, $data);
			$this->getCache()->set($key, $state, $this->getEventLifetime());
		} finally {
			$mutex->release($key);
		}
		return $published;
	}

	/**
	 * Creates the default mutex of a cache.
	 * @param ICache $cache The cache that holds the event buffers.
	 * @return IMutex A {@see TFileMutex} in the `mutex` folder of a {@see TFileCache} directory,
	 *   a {@see TDbMutex} on the connection of a {@see TDbCache}, else a {@see TCacheMutex} on the cache.
	 */
	protected function createMutex(ICache $cache): IMutex
	{
		if ($cache instanceof TFileCache) {
			$mutex = (new TFileMutex())->setMutexPath($cache->getDirectory() . DIRECTORY_SEPARATOR . static::FILE_MUTEX_FOLDER);
		} elseif ($cache instanceof TDbCache) {
			$mutex = (new TDbMutex())->setDbConnection($cache->getDbConnection());
		} else {
			$mutex = (new TCacheMutex())->setCache($cache);
		}
		return $this->configureMutex($mutex);
	}

	/**
	 * Sets the retry delay of a default mutex, so a publish that waits behind another resumes promptly.
	 * @param TMutex $mutex The default mutex.
	 * @return TMutex The mutex.
	 */
	protected function configureMutex(TMutex $mutex): TMutex
	{
		return $mutex->setRetryDelay(static::MUTEX_RETRY_DELAY);
	}

	/**
	 * Loads the channel's state from the cache.
	 * @param string $channel The channel name.
	 * @return ?array The state, or null when the cache has none.
	 */
	protected function loadState(string $channel): ?array
	{
		$state = $this->getCache()->get($this->getCacheKey($channel));
		return is_array($state) ? $state : null;
	}

	/**
	 * @return ICache The cache that holds the event buffers.
	 */
	protected function getCache(): ICache
	{
		return $this->resolveCacheModule();
	}

	/**
	 * @param string $channel The channel name.
	 * @return string The cache key of the channel's state.
	 */
	protected function getCacheKey(string $channel): string
	{
		return static::CACHE_KEY_PREFIX . $channel;
	}
}
