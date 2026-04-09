# TDbTableInfo

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [Common](./INDEX.md) > [TDbTableInfo](./TDbTableInfo.md)

**Location:** `framework/Data/Common/TDbTableInfo.php`
**Namespace:** `Prado\Data\Common`

## Overview

`TDbTableInfo` describes the metadata of a database table including columns, primary keys, and foreign keys.

## Properties

- `TableName` - Name of the table
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

- [TDbTableColumn](./TDbTableColumn.md) - Column metadata
- [TDbCommandBuilder](./TDbCommandBuilder.md) - Query builder