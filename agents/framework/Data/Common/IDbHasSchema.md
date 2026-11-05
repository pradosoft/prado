# Data/Common/IDbHasSchema

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [Common](./INDEX.md) / **`IDbHasSchema`**

## Interface Info
**Location:** `framework/Data/Common/IDbHasSchema.php`
**Namespace:** `Prado\Data\Common`
**Since:** 4.3.3

## Overview
`IDbHasSchema` is a marker interface for database table-info classes whose underlying engine supports the concept of a **schema** (also called an owner or namespace that groups tables within a database).

The interface is intentionally empty — it acts as a capability flag rather than a method contract.

## Which Drivers Implement It

| Driver | Implements `IDbHasSchema` |
|--------|--------------------------|
| MySQL | Yes |
| PostgreSQL | Yes |
| MSSQL | Yes |
| IBM DB2 ([`TIbmTableInfo`](./Ibm/TIbmTableInfo.md)) | Yes |
| Oracle | Yes |
| SQLite | No |
| Firebird | No |

## Interaction With `TDbTableInfo`

[`TDbTableInfo::getSchemaName()`](./TDbTableInfo.md) returns a non-null value **only** when the concrete `TDbTableInfo` subclass implements `IDbHasSchema`. For schema-less drivers (SQLite, Firebird) it always returns `null`, even if a `SchemaName` value was accidentally stored in the info array.

## See Also

- [TDbTableInfo](./TDbTableInfo.md) - Uses this interface to guard `getSchemaName()`
