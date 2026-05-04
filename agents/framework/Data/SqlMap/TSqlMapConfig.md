# Data/SqlMap/TSqlMapConfig

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [SqlMap](./INDEX.md) / **`TSqlMapConfig`**

## Class Info
**Location:** `framework/Data/SqlMap/TSqlMapConfig.php`
**Namespace:** `Prado\Data\SqlMap`

## Overview
`Prado\Data\SqlMap\TSqlMapConfig`

Module configuration class for SqlMap.

Inherits from [`TDataSourceConfig`](../TDataSourceConfig.md).

## Description

`TSqlMapConfig` is the module configuration class for SqlMap. It manages database connection configuration and `TSqlMapManager` instance creation with optional caching support.

## Configuration Example

```xml
<module id="sqlmap" 
    class="Prado\Data\SqlMap\TSqlMapConfig"
    ConnectionID="db"
    ConfigFile="application.config.sqlmap"
    EnableCache="true" />
```

## Key Properties

### `ConfigFile`

The path to the external SqlMap XML configuration file (namespace format, e.g., `application.config.sqlmap`). The file extension must be `.xml`.

### `EnableCache`

When `true`, the SqlMap manager instance is cached. Default is `false`.

### `Client`

Returns the `TSqlMapGateway` instance, creating it on first access.

## Key Methods

### `getSqlMapManager()`

Returns the `TSqlMapManager` instance, loading from cache if enabled.

### `clearCache()`

Deletes the configuration cache.

### `getClient()`

Returns the `TSqlMapGateway` instance.

## Caching

When `EnableCache` is true:
- The manager is stored in the application cache
- Cache dependencies are used (unless in Performance mode)
- Cache can be cleared via `clearCache()`

## See Also

- [TSqlMapManager](./TSqlMapManager.md)
- [TSqlMapGateway](./TSqlMapGateway.md)
- `Prado\Data\TDataSourceConfig`

## Category

SqlMap
