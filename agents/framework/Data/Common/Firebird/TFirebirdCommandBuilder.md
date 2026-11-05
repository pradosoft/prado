# Data/Common/Firebird/TFirebirdCommandBuilder

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Firebird](./INDEX.md) / **`TFirebirdCommandBuilder`**

## Class Info
**Location:** `framework/Data/Common/Firebird/TFirebirdCommandBuilder.php`
**Namespace:** `Prado\Data\Common\Firebird`
**Extends:** [`TDbCommandBuilder`](../TDbCommandBuilder.md)
**Since:** 4.3.3

## Overview
`TFirebirdCommandBuilder` provides Firebird-specific LIMIT/OFFSET pagination and last-insert-ID retrieval.

## Key Overrides

### `applyLimitOffset($sql, $limit, $offset)`

Firebird does not support a trailing `LIMIT`/`OFFSET` clause. Instead it uses `SELECT FIRST n SKIP m` injected immediately after the `SELECT` keyword (works with `SELECT DISTINCT` too):

```sql
-- limit only
SELECT FIRST 10 * FROM my_table

-- limit + offset
SELECT FIRST 10 SKIP 20 * FROM my_table
```

### `getLastInsertID()`

For Firebird 3+ IDENTITY columns, queries:
```sql
SELECT RDB$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID') FROM RDB$DATABASE
```
Returns `null` if the table has no IDENTITY column (`hasSequence()` is `false` for all columns).

> Firebird 2.x generator-based sequences are not supported by this method; those must be queried directly.

## See Also

- [TFirebirdTableInfo](./TFirebirdTableInfo.md) - Creates instances of this builder
- [TFirebirdTableColumn](./TFirebirdTableColumn.md) - `hasSequence()` used to detect identity columns
- [TDbCommandBuilder](../TDbCommandBuilder.md) - Base class
