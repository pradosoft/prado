# TActiveTableCell

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveTableCell](./TActiveTableCell.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveTableCell.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TTableCell. Clicking the cell triggers a callback and raises `OnCellSelected` event. Also bubbles the event to parent [TActiveTableRow](./TActiveTableRow.md). Contents can be updated during callback response.

## Key Properties/Methods

- `raiseCallbackEvent($param)` - Raises `OnCellSelected` with cell index
- `onCellSelected($param)` - Event raised when cell is clicked
- `getCellIndex()` - Returns zero-based index within row
- `getRow()` - Returns parent TTableRow
- `getClientClassName()` - Returns `Prado.WebUI.TActiveTableCell`

## See Also

- `TTableCell`, [TActiveTableRow](./TActiveTableRow.md), [TActiveTableCellEventParameter](./TActiveTableCellEventParameter.md)
