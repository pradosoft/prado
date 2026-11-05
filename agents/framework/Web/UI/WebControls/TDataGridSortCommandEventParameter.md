# Web/UI/WebControls/TDataGridSortCommandEventParameter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGridSortCommandEventParameter`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataGridSortCommandEventParameter.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Event parameter for TDataGrid onSortCommand events.

## Key Properties/Methods

- `getCommandSource()` - Control that originally raised the command
- `getSortExpression()` - Sort expression from the command
- `getSortDirection()` - `TDataGridSortDirection` the command requests: `Ascending` for a new expression, the reverse of the grid's `SortDirection` for its current `SortExpression`. The optional third constructor argument sets it (@since 4.4.0)

## See Also

- [TDataGrid](./TDataGrid.md)
- [TDataGridSortDirection](./TDataGridSortDirection.md)
