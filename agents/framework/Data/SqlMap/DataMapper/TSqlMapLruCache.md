# TSqlMapLruCache

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [DataMapper](./INDEX.md) > [TSqlMapLruCache](./TSqlMapLruCache.md)

`Prado\Data\SqlMap\DataMapper\TSqlMapLruCache`

Least Recently Used (LRU) cache implementation.

## Description

`TSqlMapLruCache` implements an LRU eviction cache with a configurable size (default 100 entries).

## Key Methods

### `get($key)`

Retrieves a cached value.

### `put($key, $value)`

Stores a value in the cache.

### `flush()`

Clears all cached entries.

## See Also

- [TSqlMapFifoCache](./TSqlMapFifoCache.md)
- [TSqlMapApplicationCache](./TSqlMapApplicationCache.md)
- [TSqlMapCache](./TSqlMapCache.md)

## Category

SqlMap DataMapper
