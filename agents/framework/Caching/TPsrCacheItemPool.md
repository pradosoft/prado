# Caching/TPsrCacheItemPool

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCacheItemPool`**

## Class Info
**Location:** `framework/Caching/TPsrCacheItemPool.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TModule`](../TModule.md); implements `Psr\Cache\CacheItemPoolInterface`, `IModuleDependency`; uses [`TPsrCacheBackingTrait`](./TPsrCacheBackingTrait.md) (@since 4.4.0)

## Overview
PSR-6 adapter module over a Prado [`ICache`](./ICache.md), with [`TPsrCacheItem`](./TPsrCacheItem.md) as its item. A library that accepts a `Psr\Cache\CacheItemPoolInterface` reads and writes the backing Prado cache through it. [`TPsrSimpleCache`](./TPsrSimpleCache.md) is the PSR-16 adapter of the same backing; [`TPsrCache`](./TPsrCache.md) is the reverse adapter.

## Configuration

```xml
<module id="cache" class="Prado\Caching\TFileCache" />
<module id="psrpool" class="Prado\Caching\TPsrCacheItemPool" BackingCacheId="cache" KeyPrefix="lib." />
```

```php
$pool = $app->getModule('psrpool');
$item = $pool->getItem('report');
if (!$item->isHit()) {
    $item->set($report)->expiresAfter(600);
    $pool->save($item);
}
```

## Properties
`BackingCacheId`, `BackingCache`, `KeyPrefix`, and `DefaultExpire` come from [`TPsrCacheBackingTrait`](./TPsrCacheBackingTrait.md). `DeferredItems` (read-only array) lists the items queued by `saveDeferred()`, by key.

## Expiration
- `save()` stores an item for the whole seconds left until `TPsrCacheItem::getExpiration()`, rounded up; an item without an expiration lives `DefaultExpire` seconds (`0` = no expiry).
- An item whose expiration has passed is deleted instead of stored; `save()` returns the delete result.
- An item of another PSR-6 implementation is stored with `DefaultExpire`, since its expiration is not readable.
- Items are created with the pool's clock (`TApplicationClockAwareTrait`), so `expiresAfter()` and `secondsUntil()` share one "now".

## Deferred Items
- `saveDeferred()` queues an item under its key; a later item with the same key replaces it.
- `commit()` saves the queue and empties it; `true` when every item saved or the queue was empty.
- `getItem()`, `getItems()`, and `hasItem()` commit the queue first, so a read sees a deferred value.
- `init()` attaches `handleEndRequest()` to the application's `OnEndRequest`; `__destruct()` also commits and discards a failure.
- `deleteItem()` and `deleteItems()` drop a queued item before deleting its key; `clear()` empties the queue before flushing the backing.

## Behavior
- Values travel in a one-element array envelope, so `null`, `false`, `0`, `''`, and `[]` are hits.
- `clear()` flushes the whole backing cache and returns `false` when the backing throws `TNotSupportedException`.
- Every key of `getItems()` and `deleteItems()` is validated before any is used. An illegal key throws [`TPsrCacheInvalidArgumentException`](./TPsrCacheInvalidArgumentException.md); a missing backing throws [`TPsrCacheException`](./TPsrCacheException.md).
- `getModuleDependencies()` returns `BackingCacheId` so the backing initializes first.

## Prado 5
All caching in Prado 5 is PSR compliant. Code that reaches the cache through this module carries over unchanged. A new application accesses its cache through this module or `TPsrSimpleCache`, and an application that intends to upgrade moves its cache access to them.

## See Also
- [TPsrCacheItem](./TPsrCacheItem.md), [TPsrCacheBackingTrait](./TPsrCacheBackingTrait.md), [TPsrSimpleCache](./TPsrSimpleCache.md), [TPsrCache](./TPsrCache.md)
