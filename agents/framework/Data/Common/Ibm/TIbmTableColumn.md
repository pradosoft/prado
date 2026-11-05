# Data/Common/Ibm/TIbmTableColumn

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Ibm](./INDEX.md) / **`TIbmTableColumn`**

## Class Info
**Location:** `framework/Data/Common/Ibm/TIbmTableColumn.php`
**Namespace:** `Prado\Data\Common\Ibm`
**Extends:** [`TDbTableColumn`](../TDbTableColumn.md)
**Since:** 4.3.3

## Overview
`TIbmTableColumn` describes the column metadata of an IBM DB2 database table. Maps DB2 SQL types to PHP primitive types and exposes IDENTITY (auto-increment) column information.

## PHP Type Mapping

| PHP Type | DB2 Types |
|----------|-----------|
| `integer` | `integer`, `int`, `bigint`, `smallint` |
| `boolean` | `boolean` |
| `float` | `double`, `real`, `float`, `decimal`, `numeric`, `decfloat` |
| `string` | All other types (`char`, `varchar`, `date`, `time`, `timestamp`, `blob`, `clob`, etc.) |

## Key Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getPHPType()` | `string` | Returns the PHP primitive type for this column (type names lowercased before lookup). |
| `getAutoIncrement()` | `bool` | `true` if this is an IDENTITY column (`SYSCAT.COLUMNS.IDENTITY = 'Y'`). |
| `hasSequence()` | `bool` | Alias for `getAutoIncrement()`. Used by [`TIbmCommandBuilder`](./TIbmCommandBuilder.md) to detect last-insert-ID support. |

## See Also

- [TIbmMetaData](./TIbmMetaData.md) - Creates instances of this class
- [TDbTableColumn](../TDbTableColumn.md) - Base class
