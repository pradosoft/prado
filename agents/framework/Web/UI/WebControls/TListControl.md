# Web/UI/WebControls/TListControl

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TListControl`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TListControl.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TListControl is the base class for list controls like TListBox, TDropDownList, TCheckBoxList, and TRadioButtonList. It manages items, selections, data binding, and auto-postback behavior.

## Key Properties/Methods

- `getItems()` - Returns the TListItemCollection
- `getSelectedIndex()` / `setSelectedIndex()` - Gets or sets zero-based selected index
- `getSelectedItem()` - Gets the selected TListItem
- `getSelectedValue()` / `setSelectedValue()` - Gets or sets selected item value
- `getSelectedValues()` / `setSelectedValues()` - Gets or sets array of selected values (multi-select)
- `getSelectedIndices()` / `setSelectedIndices()` - Gets or sets array of selected indices
- `clearSelection()` - Clears all selections
- `getAutoPostBack()` / `setAutoPostBack()` - Gets or sets auto postback on change
- `getCausesValidation()` / `setCausesValidation()` - Gets or sets whether validation triggers on postback
- `getValidationGroup()` / `setValidationGroup()` - Gets or sets validation group
- `getDataSource()` / `setDataSource()` - Gets or sets data source for items
- `getDataTextField()` / `setDataTextField()` - Gets or sets data field for item text
- `getDataValueField()` / `setDataValueField()` - Gets or sets data field for item value
- `getDataTextFormatString()` / `setDataTextFormatString()` - Gets or sets text format string
- `getDataGroupField()` / `setDataGroupField()` - Gets or sets data field for item grouping
- `getPromptText()` / `setPromptText()` - Gets or sets prompt text for first item
- `getPromptValue()` / `setPromptValue()` - Gets or sets prompt selection value
- `getAppendDataBoundItems()` / `setAppendDataBoundItems()` - Gets or sets whether to append items on databind
- `onSelectedIndexChanged()` - Raises OnSelectedIndexChanged event
- `onTextChanged()` - Raises OnTextChanged event
- `dataBind()` - Binds data source to items

## See Also

- [TDataBoundControl](./TDataBoundControl.md)
- [TListItem](./TListItem.md)
- [TListSelectionMode](./TListSelectionMode.md)
