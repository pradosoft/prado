# Data/Common/IDataMetaData

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`IDataMetaData`**

## Interface Info
**Location:** `framework/Data/Common/IDataMetaData.php`
**Namespace:** `Prado\Data\Common`

## Overview

`IDataMetaData` defines the contract for a database metadata handler — an object that can introspect a data store's schema (tables, columns, keys) and produce command builders for CRUD operations.

All built-in driver-specific metadata classes implement this interface by extending [`TDbMetaData`](./TDbMetaData.md). Third-party driver implementations may implement it directly without extending `TDbMetaData`.

## When This Interface Matters

When a PDO driver has no built-in Prado metadata class, `TDbMetaData::getInstance()` raises the **`fxDataGetMetaDataClass`** global event on the connection. Event handlers must return the fully-qualified class name of a class implementing `IDataMetaData`. The returned class is then instantiated with the connection as the sole constructor argument.

## Methods

```php
$meta->getDbConnection(): IDataConnection   // the connection this instance was created for
$meta->getTableInfo(?string $tableName): IDataTableInfo  // table/column schema (cached per call)
$meta->createCommandBuilder(?string $tableName): IDataCommandBuilder  // CRUD builder for a table
$meta->findTableNames(string $schema = ''): array  // all table names in the database/schema
```

## Implementing for a Custom Driver

```php
class MyDriverMetaData implements IDataMetaData
{
    public function __construct(private IDataConnection $conn) {}
    public function getDbConnection() { return $this->conn; }
    public function getTableInfo($tableName = null) { /* ... */ }
    public function createCommandBuilder($tableName = null) { /* ... */ }
    public function findTableNames($schema = '') { /* ... */ }
}

// Register via global fx event:
TComponent::attachClassBehavior('MyDriverBehavior', new TClassBehavior(), TDbConnection::class);
// or handle fxDataGetMetaDataClass on the connection object directly.
```

## See Also

- [TDbMetaData](./TDbMetaData.md) — Abstract base class implementing this interface
- [IDataConnection](../IDataConnection.md) — Connection interface used in `getDbConnection()`
