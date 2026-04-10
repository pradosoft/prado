# Web/UI/WebControls/TBoundColumn

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TBoundColumn`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TBoundColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TBoundColumn represents a column bound to a field in a data source. It displays data using the specified DataField and optional DataFormatString. Can display in read-only mode (static text) or edit mode (textbox).

## Key Properties/Methods

- `DataField` - Field name from data source to bind
- `DataFormatString` - Formatting string for display
- `ReadOnly` - Whether column is editable (default false)
- `ItemRenderer` / `EditItemRenderer` - Custom renderer classes for cells
- `initializeCell()` - Initializes cell with textbox or static text
- `dataBindColumn()` - Populates cell with data from source

## See Also

- [TDataGridColumn](./TDataGridColumn.md)
- [TDataGrid](./TDataGrid.md)
