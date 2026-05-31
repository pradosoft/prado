# Data/Common/Firebird/TFirebirdMetaData

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Firebird](./INDEX.md) / **`TFirebirdMetaData`**

## Class Info
**Location:** `framework/Data/Common/Firebird/TFirebirdMetaData.php`
**Namespace:** `Prado\Data\Common\Firebird`
**Extends:** [`TDbMetaData`](../TDbMetaData.md)
**Since:** 4.3.3

## Overview
`TFirebirdMetaData` loads Firebird database table and column information from `RDB$` system tables. Requires the `pdo_firebird` PHP extension. Tested against Firebird 2.5+.

Firebird has **no schema namespace**; `getSchemaTableName()` always returns `[null, $uppercasedTableName]`. All unquoted identifiers are stored uppercase in Firebird; this class normalises to uppercase for system-table queries and returns lowercase identifiers to PHP.

## RDB$FIELD_TYPE Code Mapping

The following type codes are mapped to SQL type names:

| Code | SQL Type | Notes |
|------|----------|-------|
| 7 | `SMALLINT` | |
| 8 | `INTEGER` | |
| 10 | `FLOAT` | |
| 12 | `DATE` | |
| 13 | `TIME` | |
| 14 | `CHAR` | |
| 16 | `BIGINT` | |
| 23 | `BOOLEAN` | Firebird 3+ |
| 24 | `DECFLOAT(16)` | Firebird 4+ |
| 25 | `DECFLOAT(34)` | Firebird 4+ |
| 26 | `INT128` | Firebird 4+ |
| 27 | `DOUBLE PRECISION` | |
| 28 | `TIME WITH TIME ZONE` | Firebird 4+ |
| 29 | `TIMESTAMP WITH TIME ZONE` | Firebird 4+ |
| 35 | `TIMESTAMP` | |
| 37 | `VARCHAR` | |
| 261 | `BLOB` / `TEXT` | sub-type 1 → `TEXT`, sub-type 0 → binary `BLOB` |

## Key Methods

| Method | Description |
|--------|-------------|
| `createTableInfo($table)` | Queries `RDB$RELATION_FIELDS` + `RDB$FIELDS` to build [`TFirebirdTableInfo`](./TFirebirdTableInfo.md). |
| `findTableNames($schema = '')` | Returns all user table names (lowercased). `$schema` is ignored (Firebird has no schema). |
| `getConstraintKeys($tableName)` | Returns `[$primary, $foreign]` arrays from `RDB$RELATION_CONSTRAINTS`. |
| `quoteTableName($name)` | Double-quote delimited: `"TABLE_NAME"`. |
| `quoteColumnName($name)` | Double-quote delimited: `"COLUMN_NAME"`. |
| `quoteColumnAlias($name)` | Double-quote delimited. |

## See Also

- [TFirebirdTableInfo](./TFirebirdTableInfo.md) - Table metadata container
- [TFirebirdTableColumn](./TFirebirdTableColumn.md) - Column metadata
- [TFirebirdCommandBuilder](./TFirebirdCommandBuilder.md) - Query builder
- [TDbMetaData](../TDbMetaData.md) - Base class
