# TListBox

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TListBox](./TListBox.md)

**Location:** `framework/Web/UI/WebControls/TListBox.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TListBox displays a list box on a Web page that allows single or multiple selection. The number of visible rows can be configured, and it supports optgroup for item grouping.

## Key Properties/Methods

- `getRows()` / `setRows()` - Gets or sets number of visible rows (default 4)
- `getSelectionMode()` / `setSelectionMode()` - Gets or sets selection mode (Single or Multiple)
- `getSelectedIndices()` / `setSelectedIndices()` - Gets or sets array of selected indices (multi-select only)
- `loadPostData()` - Loads user input data from postback
- `raisePostDataChangedEvent()` - Raises postdata changed event
- `getValidationPropertyValue()` - Returns selected value for validation
- `getIsValid()` / `setIsValid()` - Gets or sets validation status

## See Also

- [TListControl](./TListControl.md)
- [TListSelectionMode](./TListSelectionMode.md)
