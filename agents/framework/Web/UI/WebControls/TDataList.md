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
- `SelectedItemIndex` / `EditItemIndex` - Current selection/edit index; an item leaving edit mode returns to `SelectedItem` when it is the selected index
- `RepeatLayout` / `RepeatColumns` / `RepeatDirection` - Layout options
- `ShowHeader` / `ShowFooter` - Header/footer visibility
- `onItemCommand()` - Bubbled command events
- `onEditCommand()` / `onUpdateCommand()` / `onDeleteCommand()` / `onCancelCommand()` - Item state change events

## Item Creation
`createItem()` uses the renderer of the item type, else its template; a type without either falls back: AlternatingItem to Item, SelectedItem to Item (even index) or AlternatingItem (odd), EditItem to SelectedItem. Header and footer are created only when the data has an item. An item type with no renderer and no template creates no item; `Items` accepts only controls, so data bound with no `ItemTemplate` or `ItemRenderer` throws (also [TRepeater](./TRepeater.md)).

`applyItemStyles()` merges ItemStyle into AlternatingItemStyle and SelectedItemStyle into EditItemStyle; each item takes the style of its index: edit, selected, even, odd. A selected or edit item does not take ItemStyle.

## See Also

- [TBaseDataList](./TBaseDataList.md)
