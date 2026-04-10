# Data/SqlMap/Configuration/TSqlMapCacheModel

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TSqlMapCacheModel`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TSqlMapCacheModel.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TSqlMapCacheModel`

Cache configuration for statements.

## Description

`TSqlMapCacheModel` configures caching for statements. Cache models can use different eviction strategies.

## Cache Types

| Type | Description |
|------|-------------|
| `LRU` | Least Recently Used |
| `FIFO` | First In First Out |
| `OSCache` | OSCache compatible |
| `MemoryCache` | In-memory reference cache |

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `ID` | `string` | Unique cache identifier |
| `Type` | `string` | Cache eviction type |
| `FlushInterval` | `int` | Seconds between flushes |
| `Size` | `int` | Maximum cache size |

## Key Methods

### `flush()`

Flushes all cached objects in this cache model.

## See Also

- [TSqlMapCacheTypes](./TSqlMapCacheTypes.md)
- [TSqlMapCacheKey](./TSqlMapCacheKey.md)

## Category

SqlMap Configuration
