# Data/Common/Sqlite/TSqliteTableColumn

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Sqlite](./INDEX.md) / **`TSqliteTableColumn`**

## Class Info
**Location:** `framework/Data/Common/Sqlite/TSqliteTableColumn.php`
**Namespace:** `Prado\Data\Common\Sqlite`

## Overview
`TSqliteTableColumn` provides SQLite-specific column metadata. `DbType` is the declared type in lowercase without its size (`VARCHAR(45)` → `varchar`).

## PHP Type

`getPHPType()` looks the declared type up in a type map, then falls back to `getAffinityType()`, which applies the SQLite affinity rules in order:

| Declared type | PHP type |
|---|---|
| `int`, `integer`, `tinyint`, `smallint`, `mediumint`, `bigint`, `unsigned big int`, `int2`, `int8` | `integer` |
| `boolean`, `bool` | `boolean` |
| `real`, `double`, `double precision`, `float`, `numeric`, `decimal` | `float` |
| contains `int` | `integer` |
| contains `char`, `clob`, or `text` | `string` |
| contains `blob`, or no declared type | `string` |
| contains `real`, `floa`, or `doub` | `float` |
| anything else (NUMERIC affinity: `date`, `datetime`, `money`, ...) | `string` |

`getPdoType()` follows: `integer` → `PDO::PARAM_INT`, `boolean` → `PDO::PARAM_BOOL`, else `PDO::PARAM_STR`.

## Auto-Increment

`getAutoIncrement()` and `hasSequence()` are true only for a lone `INTEGER PRIMARY KEY`, the rowid alias.

## See Also

- [TDbTableColumn](../TDbTableColumn.md) - Base class