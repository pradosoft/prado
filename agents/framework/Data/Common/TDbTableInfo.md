# Data/Common/TDbTableInfo

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`TDbTableInfo`**

## Class Info
**Location:** `framework/Data/Common/TDbTableInfo.php`
**Namespace:** `Prado\Data\Common`
**Implements:** [`IDataTableInfo`](./IDataTableInfo.md)

## Overview
`TDbTableInfo` describes the metadata of a database table including columns, primary keys, and foreign keys.

## Properties

- `TableName` - Name of the table
- `SchemaName` - Schema/owner name; returns `null` for schema-less drivers (SQLite, Firebird). Non-null only when the concrete subclass implements [`IDbHasSchema`](./IDbHasSchema.md). (@since 4.3.3)
- `Columns` - Map of column names to [`TDbTableColumn`](./TDbTableColumn.md) objects
- `PrimaryKeys` - Array of primary key column names
- `ForeignKeys` - Array of foreign key definitions

## Key Methods

```php
// Create a command builder for this table
$builder = $tableInfo->createCommandBuilder($connection);

// Get specific column
$column = $tableInfo->getColumn('username');
```

## See Also

- [IDbHasSchema](./IDbHasSchema.md) - Marker interface controlling `getSchemaName()` behaviour
- [TDbTableColumn](./TDbTableColumn.md) - Column metadata
- [TDbCommandBuilder](./TDbCommandBuilder.md) - Query builder