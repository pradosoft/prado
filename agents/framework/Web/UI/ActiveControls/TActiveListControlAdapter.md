# TActiveListControlAdapter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveListControlAdapter](./TActiveListControlAdapter.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveListControlAdapter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Adapts list controls ([TActiveDropDownList](./TActiveDropDownList.md), [TActiveListBox](./TActiveListBox.md), etc.) to allow client-side selection changes during callback. Manages setSelectedIndex, setSelectedValue, clearSelection, and list item updates.

## Key Properties/Methods

- `setSelectedIndex($index)` - Select item by zero-based index
- `setSelectedIndices($indices)` - Select multiple items by index
- `setSelectedValue($value)` - Select item by value
- `setSelectedValues($values)` - Select multiple items by value
- `clearSelection()` - Clear all selections
- `updateListItems()` - Update client-side list options

## See Also

- [TActiveControlAdapter](./TActiveControlAdapter.md), `IListControlAdapter`
