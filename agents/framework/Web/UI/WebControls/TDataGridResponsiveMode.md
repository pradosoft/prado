# Web/UI/WebControls/TDataGridResponsiveMode

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGridResponsiveMode`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataGridResponsiveMode.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataGridResponsiveMode (@since 4.4.0) is the enumerable type of `TDataGrid::ResponsiveMode`: how the table fits a viewport narrower than itself.

## Values

- `None` - The table renders as is and may overflow the page (default)
- `Scroll` - The table renders in a `<div role="region" tabindex="0">` with `overflow-x: auto`, named by the grid caption, else the grid `aria-label`, else "Table"

A stacked (card) mode is not implemented: it needs a published stylesheet and explicit table roles, since `display: block` on table elements drops their semantics in some browsers.

## See Also

- [TDataGrid](./TDataGrid.md)
