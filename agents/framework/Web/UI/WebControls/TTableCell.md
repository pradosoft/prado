# Web/UI/WebControls/TTableCell

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TTableCell`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TTableCell.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TTableCell displays a table cell (td element) on a Web page. Content can be set via the Text property or child controls. Supports horizontal/vertical alignment, colspan, rowspan, and text wrapping.

## Key Properties/Methods

- `getHorizontalAlign()` / `setHorizontalAlign(string)` - Horizontal alignment ('NotSet', 'Left', 'Right', 'Center', 'Justify')
- `getVerticalAlign()` / `setVerticalAlign(string)` - Vertical alignment ('NotSet', 'Top', 'Bottom', 'Middle')
- `getColumnSpan()` / `setColumnSpan(int)` - Number of columns to span
- `getRowSpan()` / `setRowSpan(int)` - Number of rows to span
- `getWrap()` / `setWrap(bool)` - Whether text wraps
- `getText()` / `setText(string)` - Cell text content
- `getData()` / `setData($value)` - IDataRenderer implementation

## See Also

- [TTableRow](./TTableRow.md)
- [TTableItemStyle](./TTableItemStyle.md)
