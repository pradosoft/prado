# Web/UI/WebControls/TDropDownList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDropDownList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDropDownList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDropDownList displays a dropdown list on a Web page. It inherits from TListControl and supports optgroup for grouping items, and prompt text for displaying a selectable prompt item as the first option.

`Customizable` ([TCustomizableSelectTrait](./TCustomizableSelectTrait.md)) opts the select into `appearance: base-select` and renders `<button><selectedcontent></selectedcontent></button>` before the options.

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
- `getCustomizable()` / `setCustomizable()` - Customizable select appearance; default false
- `getCustomizableCssUrl()` / `setCustomizableCssUrl()` - Stylesheet of a customizable select
- `renderContents()` - Renders the `<button>` of a customizable select, then the options

## See Also

- [TListControl](./TListControl.md)
- [TListItem](./TListItem.md)
- [TDropDownListColumn](./TDropDownListColumn.md)
