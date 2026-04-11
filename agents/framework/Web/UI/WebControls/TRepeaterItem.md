# Web/UI/WebControls/TRepeaterItem

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRepeaterItem`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRepeaterItem.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRepeaterItem represents an item in the TRepeater control, such as heading, footer, or data item. It implements IItemDataRenderer and INamingContainer.

## Key Properties/Methods

- `getItemIndex()` / `setItemIndex($value)` - Zero-based index in item collection
- `getItemType()` / `setItemType($value)` - Type of item (Item, AlternatingItem, Header, Footer, Separator)
- `getData()` / `setData($value)` - Data associated with the item
- `bubbleEvent($sender, $param)` - Bubbles OnCommand events as TRepeaterCommandEventParameter

## See Also

- [TRepeater](./TRepeater.md)
- [IItemDataRenderer](./IItemDataRenderer.md)
- [TListItemType](./TListItemType.md)
