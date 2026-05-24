# Data/TDbPropertiesTrait

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbPropertiesTrait`**

## Trait Info
**Location:** `framework/Data/TDbPropertiesTrait.php`
**Namespace:** `Prado\Data`

## Overview
`TDbPropertiesTrait` provides reusable database connection management for any class that needs to connect to a database. It supports:
- Explicit connection configuration via a [`TDataSourceConfig`](./TDataSourceConfig.md) module ID (`ConnectionID`).
- Automatic SQLite database creation in the runtime path when no connection is configured (via `getSqliteDatabaseName()`).
- Cached [`TTableGateway`](TTableGateway.md) instances per table name.

Classes using this trait can optionally override extension points to customise behaviour.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `ConnectionID` | `string` | Module ID of a [`TDataSourceConfig`](./TDataSourceConfig.md). Empty string means not set. |
| `DbConnection` | `IDataConnection` | Active connection; creates and opens one on first access. Returns `IDataConnection` so custom implementations can be injected via `getCustomDbConnection()`. |
| `HasDbConnection` | `bool` | `true` if a connection has already been established. |

## Key Methods

```php
// Get (and lazily open) the database connection
$conn = $object->getDbConnection(): IDataConnection;

// Deactivate (and optionally clear) the connection
$object->deactivateDbConnection();        // closes but keeps reference
$object->deactivateDbConnection(true);   // closes and clears reference

// Get a cached TTableGateway for a table
$gw = $object->getTableGateway('users');
$gw = $object->getTableGateway('users', false); // skip cache, get fresh instance
```

## Extension Points (override in using class)

| Method | Default | Purpose |
|--------|---------|---------|
| `getDbConnectionActivationType()` | `false` | `false` = activate on creation, `true` = activate on every access, `null` = never auto-activate. |
| `getSqliteDatabaseName()` | `null` | Return a filename to auto-create an SQLite DB in the runtime path when no `ConnectionID` is set. |
| `getCustomDbConnection()` | `null` | Return a custom `IDataConnection` when `ConnectionID` is empty. Override to inject a non-TDbConnection implementation. |
| `getConnectionInvalidExceptionKey()` | `'dbproperties_connectionid_invalid'` | Error message key for invalid module ID. |
| `getConnectionRequiredExceptionKey()` | `'dbproperties_property_required'` | Error message key when no connection is available. |

## Connection Resolution Order

When `getDbConnection()` is first called (and `$_conn` is null), `createDbConnection()` resolves the connection as follows:

1. If `ConnectionID` is non-empty — fetches the named module; expects a `TDataSourceConfig` and calls `getDbConnection()` on it.
2. If `ConnectionID` is empty and `getCustomDbConnection()` returns non-null — uses that.
3. If `ConnectionID` is empty and `getSqliteDatabaseName()` returns a filename — creates a `TDbConnection` pointing to that SQLite file in the runtime path.
4. Otherwise — throws `TConfigurationException`.

## Destructor

The trait provides `__destruct()` which calls `deactivateDbConnection(true)` and then `parent::__destruct()` if the parent has one, ensuring connections are always closed when the object is garbage-collected.

## Used By

Most database-backed modules use this trait: [`TDbModule`](../Util/TDbModule.md), [`TDbCache`](../Caching/TDbCache.md), [`TDbParameterModule`](../Util/TDbParameterModule.md), [`TDbCronManager`](../Util/Cron/TDbCronManager.md), [`TDataSourceConfig`](./TDataSourceConfig.md) (exposes its own `DbConnection` property via this trait).

## See Also

- [IDataConnection](./IDataConnection.md) — Interface returned by `getDbConnection()`
- [TDataSourceConfig](./TDataSourceConfig.md) — Application module for DB configuration
- [TDbConnection](./TDbConnection.md) — Default PDO-based connection class
- [TTableGateway](TTableGateway.md) — Stateless table gateway
