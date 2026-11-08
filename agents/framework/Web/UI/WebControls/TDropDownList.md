# Web/UI/WebControls/TDropDownList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDropDownList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDropDownList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TDropDownList displays a dropdown list on a Web page. It inherits from TListControl and supports optgroup for grouping items, and prompt text for displaying a selectable prompt item as the first option.

`RichOptions` ([TRichOptionsTrait](./TRichOptionsTrait.md)) shows the HTML content of the options with `appearance: base-select` and renders `<button><selectedcontent></selectedcontent></button>` before the options.

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
- `getRichOptions()` / `setRichOptions()` - Rich option content with `appearance: base-select`; default false
- `getRichOptionsCssUrl()` / `setRichOptionsCssUrl()` - Stylesheet of a RichOptions select
- `renderContents()` - Renders the `<button>` of a RichOptions select, then the options

## See Also

- [TListControl](./TListControl.md)
- [TListItem](./TListItem.md)
- [TDropDownListColumn](./TDropDownListColumn.md)
