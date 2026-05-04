# Web/UI/WebControls/IItemDataRenderer

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / [WebControls](./Web/UI/WebControls/INDEX.md) / **`IItemDataRenderer`**

**Location:** `framework/Web/UI/WebControls/IItemDataRenderer.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
IItemDataRenderer defines the interface that item renderers must implement. It extends IDataRenderer and provides ItemIndex and ItemType properties.

## Key Properties/Methods

- `getItemIndex()` / `setItemIndex($value)` - Zero-based index of item
- `getItemType()` / `setItemType($value)` - Item type (Header, Footer, Item, etc.)

## See Also

- [IDataRenderer](./IDataRenderer.md)
- [TRepeater](./TRepeater.md)
- [TRepeaterItem](./TRepeaterItem.md)
