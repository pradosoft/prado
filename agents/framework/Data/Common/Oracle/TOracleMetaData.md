# Data/Common/Oracle/TOracleMetaData

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Oracle](./INDEX.md) / **`TOracleMetaData`**

## Class Info
**Location:** `framework/Data/Common/Oracle/TOracleMetaData.php`
**Namespace:** `Prado\Data\Common\Oracle`

## Overview
`TOracleMetaData` provides Oracle-specific database metadata introspection. Column data is read from `ALL_TAB_COLUMNS`; constraints from `ALL_CONSTRAINTS` and `ALL_CONS_COLUMNS`.

## Key Characteristics

- Default schema: `'system'`
- Oracle `ALL_*` views store unquoted identifiers in **uppercase**; the driver uppercases schema/table names for SQL `WHERE` clauses while preserving the original case in `TableInfo` for display.
- `PDO::ATTR_CASE` is forced to `PDO::CASE_LOWER` on the connection so fetched column names are lowercase.
- Column names are stored **without** surrounding quotes (unlike MySQL/PostgreSQL drivers).
- Auto-increment / sequence detection is currently commented out — Oracle trigger-based sequences must be handled manually.
- **Oracle fix:** `getIsView()` correctly compares the `OBJECT_TYPE` scalar returned by PDO (previously the cast `(int)` was wrong; fixed to `=== 'VIEW'` string comparison).
- `assertIdentifier()` rejects names containing double-quote characters.
- `DefaultSchema` property — configurable; defaults to `'system'`.

## See Also

- [TDbMetaData](../TDbMetaData.md) - Base class