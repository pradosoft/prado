# Web/UI/WebControls/TButtonColumn

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TButtonColumn`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TButtonColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TButtonColumn contains user-defined command buttons (like Add/Remove) for each row in a DataGrid. Button captions can be static or bound to data fields.

## Key Properties/Methods

- `Text` - Static button caption
- `DataTextField` - Data field for dynamic button caption
- `DataTextFormatString` - Format string for caption
- `ImageUrl` - Image URL for image buttons
- `ButtonType` - Type of button (LinkButton, PushButton, ImageButton)
- `CommandName` - Command name for OnCommand event
- `CausesValidation` - Whether button triggers validation
- `ValidationGroup` - Group of validators to trigger
- `initializeCell()` - Creates the button in the cell
- `dataBindColumn()` - Binds data to button caption

## See Also

- [TDataGridColumn](./TDataGridColumn.md)
- [TButtonColumnType](./TButtonColumnType.md)
