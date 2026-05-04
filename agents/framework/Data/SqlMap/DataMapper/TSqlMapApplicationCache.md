# Data/SqlMap/DataMapper/TSqlMapApplicationCache

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [DataMapper](./INDEX.md) / **`TSqlMapApplicationCache`**

## Class Info
**Location:** `framework/Data/SqlMap/DataMapper/TSqlMapApplicationCache.php`
**Namespace:** `Prado\Data\SqlMap\DataMapper`

## Overview
`Prado\Data\SqlMap\DataMapper\TSqlMapApplicationCache`

Delegates to the Prado application cache.

## Description

`TSqlMapApplicationCache` delegates caching to the Prado application cache (`ICache`). It wraps `TCache::get()`, `set()`, and `delete()`.

## Key Methods

### `get($key)`

Retrieves a cached value from application cache.

### `put($key, $value)`

Stores a value in the application cache.

### `flush()`

Clears all cached entries.

## See Also

- [TSqlMapLruCache](./TSqlMapLruCache.md)
- [TSqlMapFifoCache](./TSqlMapFifoCache.md)
- [TSqlMapCache](./TSqlMapCache.md)

## Category

SqlMap DataMapper
