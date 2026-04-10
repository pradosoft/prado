# Data/SqlMap/DataMapper/TSqlMapCache

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [DataMapper](./INDEX.md) / **`TSqlMapCache`**

## Class Info
**Location:** `framework/Data/SqlMap/DataMapper/TSqlMapCache.php`
**Namespace:** `Prado\Data\SqlMap\DataMapper`

## Overview
`Prado\Data\SqlMap\DataMapper\TSqlMapCache`

Abstract base for statement-level result caches.

## Description

`TSqlMapCache` is the abstract base class for SqlMap statement caching. Implementations must provide `get()`, `put()`, and `flush()` methods.

## Interface

```php
public function get($key);
public function put($key, $value);
public function flush();
```

## See Also

- [TSqlMapLruCache](./TSqlMapLruCache.md)
- [TSqlMapFifoCache](./TSqlMapFifoCache.md)
- [TSqlMapApplicationCache](./TSqlMapApplicationCache.md)

## Category

SqlMap DataMapper
