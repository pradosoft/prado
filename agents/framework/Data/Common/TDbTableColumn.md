# Data/Common/TDbTableColumn

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`TDbTableColumn`**

## Class Info
**Location:** `framework/Data/Common/TDbTableColumn.php`
**Namespace:** `Prado\Data\Common`

## Overview
`TDbTableColumn` describes the metadata of a single database table column.

## Properties

- `ColumnName` - Name of the column
- `ColumnId` - Column ID (usually same as ColumnName)
- `DbType` - Database type (e.g., VARCHAR, INTEGER)
- `PhpType` - Corresponding PHP type (string, integer, boolean, etc.)
- `IsPrimaryKey` - Whether this is a primary key
- `IsExcluded` - Whether excluded from INSERT/UPDATE
- `AllowNull` - Whether NULL values are allowed
- `DefaultValue` - Default value for the column
- `SequenceName` - Sequence name for auto-increment

## Key Methods

```php
$column->getAutoIncrement();  // Check if auto-increment
$column->getPdoType();        // Get PDO::PARAM_* constant
```

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Table metadata