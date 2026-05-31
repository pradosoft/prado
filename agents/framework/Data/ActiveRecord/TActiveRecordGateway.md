# Data/ActiveRecord/TActiveRecordGateway

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [ActiveRecord](./INDEX.md) / **`TActiveRecordGateway`**

## Class Info
**Location:** `framework/Data/ActiveRecord/TActiveRecordGateway.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview
`TActiveRecordGateway` executes SQL commands for ActiveRecord operations and returns data as arrays. It sits between `TActiveRecord` and the database, managing table metadata retrieval, caching, and query construction.

## Key Methods

```php
// Table metadata — cached per connection+tableName
$gateway->getTableInfo(IDataConnection $connection, string $tableName): TDbTableInfo
```

`getTableInfo()` builds a two-level cache key: for `TDbConnection` instances it uses `getConnectionString() . $tableName`; for other `IDataConnection` implementations it uses `driverName:objectId . $tableName` (stable per connection instance). On cache miss it calls `TDbMetaData::getInstance($connection)` to get the driver-specific metadata handler, then fetches and caches the `TDbTableInfo`. The serialized table info is also stored in the application cache (if configured) so it survives across requests.

## Patterns & Gotchas

- **Custom connections work** — `getTableInfo()` accepts any `IDataConnection`, so third-party driver implementations can participate in the AR metadata path by registering via `fxDataGetMetaDataClass`.
- **Cache is per-instance** — each `TActiveRecordGateway` instance has its own in-memory table-info cache. The gateway instance is shared via `TActiveRecordManager`, so the cache is effectively per-request.

## See Also

- [TActiveRecord](../TActiveRecord.md) — Base Active Record class
- [TActiveRecordManager](./TActiveRecordManager.md) — Owns the gateway instance
- [TDbMetaData](../Common/TDbMetaData.md) — Metadata factory called by `getTableInfo()`
- [IDataConnection](../IDataConnection.md) — Connection interface accepted by `getTableInfo()`
