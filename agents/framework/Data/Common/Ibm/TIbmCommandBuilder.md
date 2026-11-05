# Data/Common/Ibm/TIbmCommandBuilder

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [Common](../INDEX.md) / [Ibm](./INDEX.md) / **`TIbmCommandBuilder`**

## Class Info
**Location:** `framework/Data/Common/Ibm/TIbmCommandBuilder.php`
**Namespace:** `Prado\Data\Common\Ibm`
**Extends:** [`TDbCommandBuilder`](../TDbCommandBuilder.md)
**Since:** 4.3.3

## Overview
`TIbmCommandBuilder` provides IBM DB2-specific LIMIT/OFFSET pagination and last-insert-ID retrieval.

## Key Overrides

### `applyLimitOffset($sql, $limit, $offset)`

DB2 does not support the standard `LIMIT n OFFSET m` syntax:

- **Limit only** (`offset <= 0`): appends `FETCH FIRST n ROWS ONLY`.
- **Limit + offset**: rewrites the query using a `ROW_NUMBER()` window function subquery, compatible with DB2 LUW 9.x and later:

```sql
SELECT * FROM (
    SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum
    FROM (<original_sql>) AS prado_inner
) AS prado_outer
WHERE prado_rownum BETWEEN <offset+1> AND <offset+limit>
```

### `getLastInsertID()`

For tables with an IDENTITY column, queries:
```sql
SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1
```
Returns `null` if the table has no IDENTITY column.

## See Also

- [TIbmTableInfo](./TIbmTableInfo.md) - Creates instances of this builder
- [TIbmTableColumn](./TIbmTableColumn.md) - `hasSequence()` used to detect identity columns
- [TDbCommandBuilder](../TDbCommandBuilder.md) - Base class
