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
use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TServerEventCacheBroker class
 *
 * TServerEventCacheBroker keeps each channel's event buffer in an {@see ICache} entry, so
 * an event published by one request reaches the streams that other PHP processes serve.
 * It fits PHP-FPM, mod_php, and the built-in server. {@see receive()} polls the entry every
 * {@see getPollInterval PollInterval} seconds through the clock's `sleep()`, which yields to
 * the event loop in a fiber.
 *
 * The cache is the module named by {@see getCacheModuleID CacheModuleID}, or the
 * application's primary cache. Use a cache that every web process shares, such as
 * {@see \Prado\Caching\TRedisCache}, {@see \Prado\Caching\TMemCache},
 * {@see \Prado\Caching\TFileCache}, or {@see \Prado\Caching\TDbCache}. A per-process cache,
 * such as APCu under several FPM pools, does not carry events between processes.
 *
 * A publish reads, appends to, and writes the entry under a lock claimed with
 * {@see ICache::add()}. The claim is atomic on {@see \Prado\Caching\TAPCCache},
 * {@see \Prado\Caching\TMemCache}, {@see \Prado\Caching\TRedisCache}, and
 * {@see \Prado\Caching\TEtcdCache}. On {@see \Prado\Caching\TFileCache} and
 * {@see \Prado\Caching\TDbCache} two concurrent publishes on one channel can lose an event.
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

	/** The prefix of the cache keys. */
	public const CACHE_KEY_PREFIX = 'prado:serverevents:';

	/** The default seconds between polls. */
	public const DEFAULT_POLL_INTERVAL = 1.0;

	/** The seconds a publish lock lasts when its holder does not release it. */
	public const LOCK_TTL = 2;

	/** The attempts to claim the publish lock before publishing without it. */
	public const LOCK_ATTEMPTS = 50;

	/** The seconds between attempts to claim the publish lock. */
	public const LOCK_RETRY_DELAY = 0.01;

	/** @var float The seconds between polls. */
	private float $_pollInterval = self::DEFAULT_POLL_INTERVAL;

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
	 * Polls the channel's entry until an event follows the event ID or the timeout passes.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The ID of the last event the stream sent.
	 * @param float $timeout The most seconds to wait for an event.
	 * @return TServerEvent[] The events in order; empty when none arrived in time.
	 */
	public function receive(string $channel, string $lastEventId, float $timeout): array
	{
		$clock = $this->getClock();
		$deadline = $clock->microtime() + $timeout;
		while (true) {
			$events = $this->eventsAfter($this->loadState($channel), $channel, $lastEventId);
			$remaining = $deadline - $clock->microtime();
			if ($events !== [] || $remaining <= 0) {
				return $events;
			}
			$clock->sleep(min($this->getPollInterval(), $remaining));
		}
	}

	/**
	 * Appends an event to the channel's entry under the publish lock.
	 * @param string $channel The valid channel name.
	 * @param string $event The valid event name.
	 * @param mixed $data The JSON-encodable event data.
	 * @return TServerEvent The stored event.
	 */
	protected function storeEvent(string $channel, string $event, mixed $data): TServerEvent
	{
		$cache = $this->getCache();
		$locked = $this->lockChannel($cache, $channel);
		try {
			[$state, $published] = $this->appendEvent($this->loadState($channel), $channel, $event, $data);
			$cache->set($this->getCacheKey($channel), $state, $this->getEventLifetime());
		} finally {
			if ($locked) {
				$cache->delete($this->getCacheKey($channel) . ':lock');
			}
		}
		return $published;
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
	 * Claims the publish lock of a channel, retrying up to {@see LOCK_ATTEMPTS} times.
	 * @param ICache $cache The cache.
	 * @param string $channel The channel name.
	 * @return bool Whether the lock was claimed.
	 */
	protected function lockChannel(ICache $cache, string $channel): bool
	{
		$key = $this->getCacheKey($channel) . ':lock';
		for ($attempt = 0; $attempt < static::LOCK_ATTEMPTS; $attempt++) {
			if ($this->claimCacheKey($cache, $key, static::LOCK_TTL)) {
				return true;
			}
			$this->getClock()->sleep(static::LOCK_RETRY_DELAY);
		}
		return false;
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
