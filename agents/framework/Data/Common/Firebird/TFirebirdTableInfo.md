# Data/Common/Firebird/TFirebirdTableInfo

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Firebird](./INDEX.md) / **`TFirebirdTableInfo`**

## Class Info
**Location:** `framework/Data/Common/Firebird/TFirebirdTableInfo.php`
**Namespace:** `Prado\Data\Common\Firebird`
**Extends:** [`TDbTableInfo`](../TDbTableInfo.md)
**Since:** 4.3.3

## Overview
`TFirebirdTableInfo` provides table metadata for Firebird databases. Firebird has no schema namespace; table names are unique within a database file. This class does **not** implement [`IDbHasSchema`](../IDbHasSchema.md), so `getSchemaName()` always returns `null`.

## Key Methods

| Method | Return | Description |
|--------|--------|-------------|
| `getTableFullName()` | `string` | Returns the double-quoted, uppercased table name: `"TABLE_NAME"`. |
| `createCommandBuilder($connection)` | [`TFirebirdCommandBuilder`](./TFirebirdCommandBuilder.md) | Creates a Firebird-specific command builder for this table. |

## See Also

- [TFirebirdMetaData](./TFirebirdMetaData.md) - Creates instances of this class
- [TFirebirdCommandBuilder](./TFirebirdCommandBuilder.md) - Builder created by this class
- [IDbHasSchema](../IDbHasSchema.md) - Marker interface (not implemented — Firebird has no schema)
- [TDbTableInfo](../TDbTableInfo.md) - Base class
