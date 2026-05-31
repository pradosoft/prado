# Data/Common/Ibm/TIbmTableInfo

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Ibm](./INDEX.md) / **`TIbmTableInfo`**

## Class Info
**Location:** `framework/Data/Common/Ibm/TIbmTableInfo.php`
**Namespace:** `Prado\Data\Common\Ibm`
**Extends:** [`TDbTableInfo`](../TDbTableInfo.md)
**Implements:** [`IDbHasSchema`](../IDbHasSchema.md)
**Since:** 4.3.3

## Overview
`TIbmTableInfo` provides table metadata for IBM DB2 databases. Implements [`IDbHasSchema`](../IDbHasSchema.md), so `getSchemaName()` returns the DB2 schema (owner) name.

## Key Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getTableFullName()` | `string` | Returns `"SCHEMA"."TABLE"` when schema is set, or `"TABLE"` if not. |
| `createCommandBuilder($connection)` | [`TIbmCommandBuilder`](./TIbmCommandBuilder.md) | Creates a DB2-specific command builder for this table. |

## See Also

- [TIbmMetaData](./TIbmMetaData.md) - Creates instances of this class
- [TIbmCommandBuilder](./TIbmCommandBuilder.md) - Builder created by this class
- [IDbHasSchema](../IDbHasSchema.md) - Marker interface (implemented — DB2 supports schemas)
- [TDbTableInfo](../TDbTableInfo.md) - Base class
