# Web/UI/WebControls/TDataGridPager

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataGridPager`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataGridPager.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataGridPager represents a datagrid pager control. Acts as a naming container and bubbles OnCommand events. It renders a `<nav>` named "Pagination" (a `<div>` before 4.4.0); set `aria-label` on the pager in `OnPagerCreated` to rename it, for example to tell a top and a bottom pager apart. With `PagerStyle.ListLayout` the buttons render as `<ul>` list items. See [TPagerAccessibilityTrait](./TPagerAccessibilityTrait.md).

## Key Properties/Methods

- `DataGrid` - Reference to the owning datagrid
- `ItemType` - Always returns TListItemType::Pager
- `bubbleEvent()` - Bubbles command events with item information

## See Also

- [TDataGrid](./TDataGrid.md)
