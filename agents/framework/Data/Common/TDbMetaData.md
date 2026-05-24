# Data/Common/TDbMetaData

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`TDbMetaData`**

## Class Info
**Location:** `framework/Data/Common/TDbMetaData.php`
**Namespace:** `Prado\Data\Common`
**Extends:** `TComponent`
**Implements:** [`IDataMetaData`](./IDataMetaData.md)

## Overview

`TDbMetaData` is the abstract base class for retrieving schema metadata (tables, columns, keys) from a database connection. It caches `TDbTableInfo` instances per table name to avoid redundant queries.

The static factory `TDbMetaData::getInstance($conn)` selects the right driver-specific subclass and returns an `IDataMetaData`. For unsupported drivers it raises the `fxDataGetMetaDataClass` global event so third-party code can supply a class name.

## Constructor

```php
new TDbMetaData(IDataConnection $conn)
```

The connection is stored and returned by `getDbConnection()`. All built-in driver subclasses call this via `parent::__construct($conn)`.

## Static Factory

```php
TDbMetaData::getInstance(IDataConnection $conn): IDataMetaData
```

1. Calls `$conn->setActive(true)` to ensure the connection is open.
2. Reads `$conn->getDriverName()` and switches on the driver string.
3. Returns a new instance of the matching driver subclass.
4. **Unknown driver fallback:** raises `fxDataGetMetaDataClass` on `$conn` with the driver name string as the parameter. Handlers return a fully-qualified class name implementing `IDataMetaData`; the **last returned value** wins (`array_pop`). The class is instantiated with `new $class($conn)`. Throws `TDbException('dbmetadata_invalid_database_driver')` if no handler responds, or `TDbException('dbmetadata_not_meta_data')` if the returned class does not implement `IDataMetaData`.

## Key Methods

```php
$meta->getDbConnection(): IDataConnection       // connection this instance was created for
$meta->getTableInfo(?string $tableName): TDbTableInfo  // cached per-table schema
$meta->createCommandBuilder(?string $tableName): TDbCommandBuilder
$meta->findTableNames(string $schema = ''): array
$meta->quoteTableName(string $name, string $lft = '', string $rgt = ''): string
$meta->quoteColumnName(string $name, string $lft = '', string $rgt = ''): string
$meta->quoteColumnAlias(string $name, string $lft = '', string $rgt = ''): string
```

## Subclasses

| Driver string(s) | Class |
|---|---|
| `mysql`, `mysqli` | [`TMysqlMetaData`](./Mysql/TMysqlMetaData.md) |
| `pgsql` | [`TPgsqlMetaData`](./Pgsql/TPgsqlMetaData.md) |
| `sqlite`, `sqlite2` | [`TSqliteMetaData`](./Sqlite/TSqliteMetaData.md) |
| `mssql`, `sqlsrv`, `dblib` | [`TMssqlMetaData`](./Mssql/TMssqlMetaData.md) |
| `oci` | [`TOracleMetaData`](./Oracle/TOracleMetaData.md) |
| `ibm` | [`TIbmMetaData`](./Ibm/TIbmMetaData.md) |
| `firebird`, `interbase` | [`TFirebirdMetaData`](./Firebird/TFirebirdMetaData.md) |

## Patterns & Gotchas

- **Always use `getInstance()`** — never instantiate a driver subclass directly. The factory handles driver detection and the third-party fallback event.
- **`getTableInfo()` caches** — repeated calls return the same `TDbTableInfo` object. If schema changes at runtime, the metadata instance must be discarded and recreated.
- **Third-party drivers** — implement [`IDataMetaData`](./IDataMetaData.md) and register via an `fxDataGetMetaDataClass` handler returning your class name (not an instance). The last handler wins, so plugins can override earlier registrations.
- **`$conn` need not be `TDbConnection`** — `getInstance()` accepts any `IDataConnection`, enabling custom connection implementations end-to-end.

## See Also

- [IDataMetaData](./IDataMetaData.md) — Interface this class implements
- [TDbTableInfo](./TDbTableInfo.md) — Returned by `getTableInfo()`
- [TDbCommandBuilder](./TDbCommandBuilder.md) — Returned by `createCommandBuilder()`
- [IDataConnection](../IDataConnection.md) — Connection contract
