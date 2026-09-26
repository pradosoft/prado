# Data/TDataSourceConfigProxy

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDataSourceConfigProxy`**

## Class Info
**Location:** `framework/Data/TDataSourceConfigProxy.php`
**Namespace:** `Prado\Data`
**Extends:** [`TDataSourceConfig`](./TDataSourceConfig.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent data source module whose `getDbConnection()` (and so `getDatabase()`) returns the connection of the `TDataSourceConfig` module named by `BackingDataSourceId`. The proxy never creates a connection of its own.

## Configuration

```xml
<module id="db" class="Prado\Data\TDataSourceConfigProxy" BackingDataSourceId="realDb" />
<module id="realDb" class="Prado\Data\TDataSourceConfig">
    <database ConnectionString="mysql:host=localhost;dbname=test" username="dbuser" password="dbpass" />
</module>
```

## Properties

- `BackingDataSourceId` (string) — required by `init()` (`datasourceproxy_backing_data_source_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.data`) and drops the resolved backing.
- `DataSource` (read-only `TDataSourceConfig`) — resolves the backing on first call; throws `datasourceproxy_data_source_not_found` or `datasourceproxy_invalid_data_source_type`, then calls `attachProxy()`.

## Behavior

- `getModuleDependencies()` returns the backing ID so the backing initializes first.
- A `<database>` element on the proxy is applied by `TDataSourceConfig::init()` to the backing's connection, since `getDbConnection()` is the backing's.

## See Also
- [TDataSourceConfig](./TDataSourceConfig.md), [TModuleProxy](../TModuleProxy.md)
