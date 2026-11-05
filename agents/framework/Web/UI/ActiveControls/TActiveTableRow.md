# Web/UI/ActiveControls/TActiveTableRow

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveTableRow`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveTableRow.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TTableRow. Clicking the row triggers a callback and raises `OnRowSelected` event. Responds to bubbled `OnCellSelected` events from child [TActiveTableCell](./TActiveTableCell.md) controls. A click in one of its cells that has an `OnCellSelected` handler sends only the cell's callback; the client `Prado.WebUI.TActiveTableRow::isCellCallback()` skips the row's own callback, and the server bubble raises `OnRowSelected` once. Contents can be updated during callback response.

## Key Properties/Methods

- `raiseCallbackEvent($param)` - Raises `OnRowSelected` with row index
- `bubbleEvent($sender, $param)` - Handles bubbled cell events
- `onRowSelected($param)` - Event raised when row is clicked
- `getRowIndex()` - Returns zero-based index within table
- `getTable()` - Returns parent TTable
- `getClientClassName()` - Returns `Prado.WebUI.TActiveTableRow`

## See Also

- `TTableRow`, [TActiveTableCell](./TActiveTableCell.md), [TActiveTableRowEventParameter](./TActiveTableRowEventParameter.md)
