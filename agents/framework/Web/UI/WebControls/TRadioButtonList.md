# Web/UI/WebControls/TRadioButtonList

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRadioButtonList`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRadioButtonList.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRadioButtonList displays a group of radio buttons on a Web page, allowing single selection only. It inherits from TCheckBoxList and overrides multi-select behavior.

## Key Properties/Methods

- `getIsMultiSelect()` - Always returns false (single selection only)
- `createRepeatedControl()` - Returns TRadioButtonItem template control
- `loadPostData()` - Loads user input data from postback
- `setSelectedIndices()` - Throws TNotSupportedException (not supported for radio buttons)
- `getClientClassName()` - Returns 'Prado.WebUI.TRadioButtonList'

## See Also

- [TCheckBoxList](./TCheckBoxList.md)
- [TListControl](./TListControl.md)
