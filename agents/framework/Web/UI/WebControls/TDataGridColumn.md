# Web/UI/WebControls/TDataGridColumn

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGridColumn`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataGridColumn.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataGridColumn serves as the base class for all DataGrid column types. Defines common properties and methods for header/footer cells, sorting, and cell rendering.

## Key Properties/Methods

- `HeaderText` / `FooterText` - Header and footer text
- `HeaderImageUrl` - Image for header instead of text
- `HeaderStyle` / `FooterStyle` / `ItemStyle` - Column styles
- `SortExpression` - Field/expression for sorting
- `Visible` - Column visibility
- `HeaderRenderer` / `FooterRenderer` - Custom renderer classes
- `EnableCellGrouping` - Group consecutive cells with same content
- `initializeCell()` - Initializes column cells
- `formatDataValue()` - Formats cell data values

## See Also

- [TBoundColumn](./TBoundColumn.md)
- [TButtonColumn](./TButtonColumn.md)
- [TCheckBoxColumn](./TCheckBoxColumn.md)
- [THyperLinkColumn](./THyperLinkColumn.md)
