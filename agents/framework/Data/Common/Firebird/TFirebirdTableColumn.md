# Data/Common/Firebird/TFirebirdTableColumn

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Firebird](./INDEX.md) / **`TFirebirdTableColumn`**

## Class Info
**Location:** `framework/Data/Common/Firebird/TFirebirdTableColumn.php`
**Namespace:** `Prado\Data\Common\Firebird`
**Extends:** [`TDbTableColumn`](../TDbTableColumn.md)
**Since:** 4.3.3

## Overview
`TFirebirdTableColumn` describes the column metadata of a Firebird database table. Maps Firebird SQL types to PHP primitive types and exposes IDENTITY (auto-increment) column information for Firebird 3+.

## PHP Type Mapping

| PHP Type | Firebird Types |
|----------|----------------|
| `integer` | `SMALLINT`, `INTEGER`, `BIGINT` |
| `boolean` | `BOOLEAN` |
| `float` | `FLOAT`, `DOUBLE PRECISION`, `DECIMAL`, `NUMERIC`, `DECFLOAT(16)`, `DECFLOAT(34)` |
| `string` | All other types (`CHAR`, `VARCHAR`, `DATE`, `TIME`, `TIMESTAMP`, `BLOB`, `TEXT`, etc.) |

## Key Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getPHPType()` | `string` | Returns the PHP primitive type for this column. |
| `getAutoIncrement()` | `bool` | `true` if this is a Firebird 3+ IDENTITY column. |
| `hasSequence()` | `bool` | Alias for `getAutoIncrement()`. Used by [`TFirebirdCommandBuilder`](./TFirebirdCommandBuilder.md) to detect last-insert-ID support. |

## See Also

- [TFirebirdMetaData](./TFirebirdMetaData.md) - Creates instances of this class
- [TDbTableColumn](../TDbTableColumn.md) - Base class
