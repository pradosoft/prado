# Data/Common/Sqlite/TSqliteMetaData

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Sqlite](./INDEX.md) / **`TSqliteMetaData`**

## Class Info
**Location:** `framework/Data/Common/Sqlite/TSqliteMetaData.php`
**Namespace:** `Prado\Data\Common\Sqlite`

## Overview
`TSqliteMetaData` provides SQLite-specific database metadata introspection from `PRAGMA table_info` and `PRAGMA foreign_key_list`.

## Column Mapping

| `PRAGMA table_info` field | column property |
|---|---|
| `notnull` = 0 | `AllowNull` true; 1 → false |
| `pk` > 0 | `IsPrimaryKey`; the primary key list follows the `pk` position, so a composite key lists every column in key order |
| `type` = `integer` and the only key column | `AutoIncrement` (the rowid alias) |
| `dflt_value` | `DefaultValue` |
| `type` with `(n)` or `(p,s)` | `ColumnSize`, or `NumericPrecision` and `NumericScale`; `DbType` drops the parentheses |

A non-integer `PRIMARY KEY` without `NOT NULL` reports `AllowNull` true, as SQLite stores NULL in it.

## See Also

- [TDbMetaData](../TDbMetaData.md) - Base class