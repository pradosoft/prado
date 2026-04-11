# Data/SqlMap/DataMapper/TSqlMapFifoCache

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [DataMapper](./INDEX.md) / **`TSqlMapFifoCache`**

## Class Info
**Location:** `framework/Data/SqlMap/DataMapper/TSqlMapFifoCache.php`
**Namespace:** `Prado\Data\SqlMap\DataMapper`

## Overview
`Prado\Data\SqlMap\DataMapper\TSqlMapFifoCache`

First In, First Out (FIFO) cache implementation.

## Description

`TSqlMapFifoCache` implements a FIFO eviction cache using a fixed-size circular buffer.

## Key Methods

### `get($key)`

Retrieves a cached value.

### `put($key, $value)`

Stores a value in the cache.

### `flush()`

Clears all cached entries.

## See Also

- [TSqlMapLruCache](./TSqlMapLruCache.md)
- [TSqlMapApplicationCache](./TSqlMapApplicationCache.md)
- [TSqlMapCache](./TSqlMapCache.md)

## Category

SqlMap DataMapper
