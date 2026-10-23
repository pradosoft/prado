<?php

/**
 * TPsrCacheItemPool class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\IModuleDependency;
use Prado\TModule;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * TPsrCacheItemPool class.
 *
 * TPsrCacheItemPool adapts a Prado {@see ICache} to the PSR-6 {@see CacheItemPoolInterface}.
 * A library that accepts a PSR-6 pool reads and writes a Prado cache module through this
 * module, with {@see TPsrCacheItem} as its item. {@see TPsrSimpleCache} is the PSR-16
 * adapter of the same backing.
 *
 * {@see TPsrCacheBackingTrait} resolves the backing: {@see setBackingCache BackingCache}
 * when set, otherwise the module named by {@see setBackingCacheId BackingCacheId},
 * otherwise the application's primary cache. The trait also documents the key rules,
 * {@see setKeyPrefix KeyPrefix}, {@see setDefaultExpire DefaultExpire}, and the value
 * envelope that keeps empty values.
 *
 * ## Expiration
 *
 * {@see save()} stores an item for the seconds left until its
 * {@see TPsrCacheItem::getExpiration()}, rounded up. An item without an expiration lives
 * {@see setDefaultExpire DefaultExpire} seconds. An item whose expiration has passed is
 * deleted instead of stored. An item of another PSR-6 implementation is stored with the
 * default expiration, since its expiration is not readable.
 *
 * ## Deferred items
 *
 * {@see saveDeferred()} queues an item under its key; a later item with the same key
 * replaces it. {@see commit()} saves the queue. The queue is also committed before
 * {@see getItem()}, {@see getItems()}, and {@see hasItem()} read, so a read sees a
 * deferred value, at the application's `OnEndRequest` event, and when the pool is
 * destroyed. {@see deleteItem()} and {@see deleteItems()} drop a queued item before
 * deleting its key, and {@see clear()} empties the queue before flushing the backing cache.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="cache" class="Prado\Caching\TFileCache" />
 * <module id="psrpool" class="Prado\Caching\TPsrCacheItemPool" BackingCacheId="cache" KeyPrefix="lib." />
 * ```
 *
 * ```php
 * $pool = $app->getModule('psrpool');
 * $item = $pool->getItem('report');
 * if (!$item->isHit()) {
 *     $item->set($report)->expiresAfter(600);
 *     $pool->save($item);
 * }
 * ```
 *
 * {@see clear()} flushes the whole backing cache, including entries written by its
 * other users, and returns false when the backing does not support {@see ICache::flush()}.
 *
 * ## Prado 5
 *
 * All caching in Prado 5 is PSR compliant. Code that reaches the cache through this
 * module carries over unchanged. A new application accesses its cache through this
 * module or {@see TPsrSimpleCache}, and an application that intends to upgrade moves
 * its cache access to them.
 *
 * {@see TPsrCache} is the reverse adapter. It is a {@see TCache} stored in a PSR-16 or
 * PSR-6 cache.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrCacheItemPool extends TModule implements CacheItemPoolInterface, IModuleDependency
{
	use TPsrCacheBackingTrait;

	/** @var array<string, CacheItemInterface> the items queued by saveDeferred(), by key */
	private array $_deferred = [];

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Initializes the module and commits the deferred items at the application's
	 * `OnEndRequest` event.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		parent::init($config);
		$this->getApplication()?->attachEventHandler('OnEndRequest', [$this, 'handleEndRequest']);
	}

	/**
	 * Commits the deferred items when the application ends the request.
	 * @param mixed $sender the application
	 * @param mixed $param the event parameter
	 */
	public function handleEndRequest($sender, $param): void
	{
		$this->commit();
	}

	/**
	 * Commits the deferred items before the pool is destroyed. A failure is discarded
	 * because a backing cache that is gone during shutdown cannot take the items.
	 */
	public function __destruct()
	{
		if ($this->getDeferredItemsDirect() !== []) {
			try {
				$this->commit();
			} catch (\Throwable) {
				// the backing cache is unavailable during shutdown
			}
		}
		parent::__destruct();
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return array<string, CacheItemInterface> the stored deferred items, by key
	 */
	protected function getDeferredItemsDirect(): array
	{
		return $this->_deferred;
	}

	/**
	 * @param array<string, CacheItemInterface> $items the deferred items to store, by key
	 */
	protected function setDeferredItemsDirect(array $items): void
	{
		$this->_deferred = $items;
	}

	/**
	 * @return array<string, CacheItemInterface> the items queued by {@see saveDeferred()}, by key
	 */
	public function getDeferredItems(): array
	{
		return $this->getDeferredItemsDirect();
	}

	// ----------------------------------------------------------------- CacheItemPoolInterface

	/**
	 * Returns the item of a key after committing the deferred items.
	 * @param string $key the key
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return TPsrCacheItem the item; a miss when the key is not cached
	 */
	public function getItem(string $key): CacheItemInterface
	{
		$key = $this->validateKey($key);
		$this->commit();
		return $this->createItem($key, $this->readEntry($key));
	}

	/**
	 * Returns the items of several keys after committing the deferred items. Every key
	 * is validated before any is read.
	 * @param string[] $keys the keys
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return array<string, TPsrCacheItem> the items by key; a miss for each key that is not cached
	 */
	public function getItems(array $keys = []): iterable
	{
		$keys = $this->validateKeys($keys);
		$this->commit();
		$items = [];
		foreach ($keys as $key) {
			$items[$key] = $this->createItem($key, $this->readEntry($key));
		}
		return $items;
	}

	/**
	 * Returns whether the backing cache holds a key, after committing the deferred items.
	 * @param string $key the key
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return bool whether the key is cached
	 */
	public function hasItem(string $key): bool
	{
		$key = $this->validateKey($key);
		$this->commit();
		return $this->readEntry($key) !== null;
	}

	/**
	 * Empties the deferred items and flushes the backing cache, including entries
	 * written by its other users.
	 * @return bool whether the backing cache flushed; false when it does not support {@see ICache::flush()}
	 */
	public function clear(): bool
	{
		$this->setDeferredItemsDirect([]);
		return $this->clearEntries();
	}

	/**
	 * Drops a deferred item of the key and deletes the key from the backing cache.
	 * @param string $key the key
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return bool whether the backing cache deleted the key
	 */
	public function deleteItem(string $key): bool
	{
		$key = $this->validateKey($key);
		$this->dropDeferredItem($key);
		return $this->deleteEntry($key);
	}

	/**
	 * Deletes several keys, as {@see deleteItem()} does each. Every key is validated
	 * before any is deleted.
	 * @param string[] $keys the keys
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return bool whether the backing cache deleted every key
	 */
	public function deleteItems(array $keys): bool
	{
		$ok = true;
		foreach ($this->validateKeys($keys) as $key) {
			$this->dropDeferredItem($key);
			$ok = $this->deleteEntry($key) && $ok;
		}
		return $ok;
	}

	/**
	 * Stores an item for the seconds until its expiration; an expired item is deleted.
	 * An item of another PSR-6 implementation is stored with {@see getDefaultExpire DefaultExpire}.
	 * @param CacheItemInterface $item the item
	 * @throws TPsrCacheInvalidArgumentException when the item's key is illegal
	 * @return bool whether the backing cache stored, or deleted, the key
	 */
	public function save(CacheItemInterface $item): bool
	{
		$key = $this->validateKey($item->getKey());
		$expiration = $item instanceof TPsrCacheItem ? $item->getExpiration() : null;
		return $this->storeEntry($key, $item->get(), $expiration === null ? null : $this->secondsUntil($expiration));
	}

	/**
	 * Queues an item for {@see commit()}; a queued item with the same key is replaced.
	 * @param CacheItemInterface $item the item
	 * @throws TPsrCacheInvalidArgumentException when the item's key is illegal
	 * @return bool true
	 */
	public function saveDeferred(CacheItemInterface $item): bool
	{
		$items = $this->getDeferredItemsDirect();
		$items[$this->validateKey($item->getKey())] = $item;
		$this->setDeferredItemsDirect($items);
		return true;
	}

	/**
	 * Saves the queued items and empties the queue.
	 * @return bool whether every queued item was saved; true when the queue was empty
	 */
	public function commit(): bool
	{
		$items = $this->getDeferredItemsDirect();
		if ($items === []) {
			return true;
		}
		$this->setDeferredItemsDirect([]);
		$ok = true;
		foreach ($items as $item) {
			$ok = $this->save($item) && $ok;
		}
		return $ok;
	}

	// --------------------------------------------------------------- internals

	/**
	 * Creates the item of a key with the pool's clock.
	 * @param string $key a validated key
	 * @param ?array $entry the envelope from {@see readEntry()}; null for a miss
	 * @return TPsrCacheItem the item
	 */
	protected function createItem(string $key, ?array $entry): TPsrCacheItem
	{
		$item = new TPsrCacheItem($key, $entry === null ? null : $entry[0], $entry !== null);
		$item->setClock($this->getClock());
		return $item;
	}

	/**
	 * Removes a key from the deferred items.
	 * @param string $key a validated key
	 */
	protected function dropDeferredItem(string $key): void
	{
		$items = $this->getDeferredItemsDirect();
		if (array_key_exists($key, $items)) {
			unset($items[$key]);
			$this->setDeferredItemsDirect($items);
		}
	}

	/**
	 * @param \DateTimeInterface $expiration the expiration
	 * @return int the whole seconds until the expiration, rounded up; zero or less when it has passed
	 */
	protected function secondsUntil(\DateTimeInterface $expiration): int
	{
		return (int) ceil((float) $expiration->format('U.u') - $this->getClock()->microtime());
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the backing properties at their defaults, a backing cache resolved from
	 * {@see getBackingCacheId BackingCacheId}, and an empty deferred queue from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addPsrCacheBackingZappable($exprops);
		if ($this->_deferred === []) {
			$exprops[] = "\0" . __CLASS__ . "\0_deferred";
		}
	}
}
