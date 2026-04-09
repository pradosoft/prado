# TTableFooterRow

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TTableFooterRow](./TTableFooterRow.md)

**Location:** `framework/Web/UI/WebControls/TTableFooterRow.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TTableFooterRow displays a table footer row (tfoot element). It extends TTableRow and always returns 'Footer' for the TableSection property. This property is read-only and cannot be changed.

## Key Properties/Methods

- `getTableSection()` - Always returns 'Footer'
- `setTableSection($value)` - Throws TInvalidOperationException (read-only)

## See Also

- [TTableRow](./TTableRow.md)
- [TTable](./TTable.md)
