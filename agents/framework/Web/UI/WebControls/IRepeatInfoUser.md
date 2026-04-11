# Web/UI/WebControls/IRepeatInfoUser

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [UI](./Web/UI/INDEX.md) / [WebControls](./Web/UI/WebControls/INDEX.md) / **`IRepeatInfoUser`**

**Location:** `framework/Web/UI/WebControls/IRepeatInfoUser.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
IRepeatInfoUser is the interface that classes must implement to use TRepeatInfo for rendering repeated items.

## Key Properties/Methods

- `getHasFooter()` - Whether contains footer
- `getHasHeader()` - Whether contains header
- `getHasSeparators()` - Whether contains separators
- `getItemCount()` - Number of items to render
- `generateItemStyle($itemType, $index)` - Returns TStyle for rendering
- `renderItem($writer, $repeatInfo, $itemType, $index)` - Renders an item

## See Also

- [TRepeatInfo](./TRepeatInfo.md)
- [TCheckBoxList](./TCheckBoxList.md)
