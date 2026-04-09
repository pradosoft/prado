# TDropDownList

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TDropDownList](./TDropDownList.md)

**Location:** `framework/Web/UI/WebControls/TDropDownList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TDropDownList displays a dropdown list on a Web page. It inherits from TListControl and supports optgroup for grouping items, and prompt text for displaying a selectable prompt item as the first option.

## Key Properties/Methods

- `getSelectedIndex()` / `setSelectedIndex()` - Gets or sets the selected index
- `getSelectedValue()` / `setSelectedValue()` - Gets or sets the selected item value
- `getItems()` - Returns the TListItemCollection containing all list items
- `getPromptText()` / `setPromptText()` - Sets prompt text displayed as first item
- `getPromptValue()` / `setPromptValue()` - Sets prompt selection value
- `clearSelection()` - Clears current selection
- `loadPostData()` - Loads user input data from postback
- `raisePostDataChangedEvent()` - Raises postdata changed event
- `getValidationPropertyValue()` - Returns value to be validated
- `getIsValid()` / `setIsValid()` - Gets or sets validation status

## See Also

- [TListControl](./TListControl.md)
- [TListItem](./TListItem.md)
- [TDropDownListColumn](./TDropDownListColumn.md)
