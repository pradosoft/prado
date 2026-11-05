# Data/Common/Ibm/TIbmMetaData

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Ibm](./INDEX.md) / **`TIbmMetaData`**

## Class Info
**Location:** `framework/Data/Common/Ibm/TIbmMetaData.php`
**Namespace:** `Prado\Data\Common\Ibm`
**Extends:** [`TDbMetaData`](../TDbMetaData.md)
**Since:** 4.3.3

## Overview
`TIbmMetaData` loads IBM DB2 database table and column information from `SYSCAT.*` catalog views. Requires the `pdo_ibm` PHP extension. Tested against DB2 LUW 9.7+.

Schema is required for all table lookups. When not specified, `DefaultSchema` is resolved by querying `VALUES CURRENT SCHEMA`. All identifiers are uppercased for catalog queries; PHP-facing identifiers are returned lowercase.

## Properties

| Property | Description |
|----------|-------------|
| `DefaultSchema` | Schema used when no schema prefix is given. Defaults to `VALUES CURRENT SCHEMA`. Can be set explicitly (uppercased automatically). |

## Key Methods

| Method | Description |
|--------|-------------|
| `createTableInfo($table)` | Queries `SYSCAT.COLUMNS` to build [`TIbmTableInfo`](./TIbmTableInfo.md). Supports `schema.table` notation. |
| `findTableNames($schema = '')` | Returns all base table names (`TYPE = 'T'`) for the given schema; defaults to `DefaultSchema`. |
| `getConstraintKeys($schemaName, $tableName)` | Returns `[$primary, $foreign]` from `SYSCAT.KEYCOLUSE`, `SYSCAT.TABCONST`, `SYSCAT.REFERENCES`. |
| `quoteTableName($name)` | Double-quote delimited: `"SCHEMA"."TABLE"`. |
| `quoteColumnName($name)` | Double-quote delimited: `"COLUMN"`. |
| `quoteColumnAlias($name)` | Double-quote delimited. |

## Gotchas

- Identifier names must not contain double-quote characters; `assertIdentifier()` throws `TDbException` if they do.
- `findTableNames()` returns only base tables, not views.

## See Also

- [TIbmTableInfo](./TIbmTableInfo.md) - Table metadata container
- [TIbmTableColumn](./TIbmTableColumn.md) - Column metadata
- [TIbmCommandBuilder](./TIbmCommandBuilder.md) - Query builder
- [TDbMetaData](../TDbMetaData.md) - Base class
