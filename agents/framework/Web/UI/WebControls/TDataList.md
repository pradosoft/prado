# Web/UI/WebControls/TDataList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDataList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDataList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDataList represents a data-bound and updatable list control. Displays items repeatedly based on data source, supports tiling layouts, and maintains item state for editing and selection.

## Key Properties/Methods

- `Items` - Collection of data list items
- `ItemTemplate` / `AlternatingItemTemplate` / `SelectedItemTemplate` / `EditItemTemplate` - Item templates
- `ItemRenderer` / `AlternatingItemRenderer` / etc. - Renderer classes
- `SelectedItemIndex` / `EditItemIndex` - Current selection/edit index
- `RepeatLayout` / `RepeatColumns` / `RepeatDirection` - Layout options
- `ShowHeader` / `ShowFooter` - Header/footer visibility
- `onItemCommand()` - Bubbled command events
- `onEditCommand()` / `onUpdateCommand()` / `onDeleteCommand()` / `onCancelCommand()` - Item state change events

## See Also

- [TBaseDataList](./TBaseDataList.md)
