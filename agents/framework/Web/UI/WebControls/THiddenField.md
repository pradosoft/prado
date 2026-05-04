# Web/UI/WebControls/THiddenField

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`THiddenField`**

## Class Info
**Location:** `framework/Web/UI/WebControls/THiddenField.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
THiddenField displays a hidden input field on a Web page. The value can be accessed via the Value property, and if changed between posts, raises an OnValueChanged event.

## Key Properties/Methods

- `getValue()` / `setValue()` - Gets or sets the hidden field value
- `getData()` / `setData()` - IDataRenderer implementation (same as getValue/setValue)
- `loadPostData()` - Loads hidden field data from postback
- `raisePostDataChangedEvent()` - Raises postdata changed event
- `onValueChanged()` - Raises OnValueChanged event when value changes
- `getValidationPropertyValue()` - Returns value for validation
- `getIsValid()` / `setIsValid()` - Gets or sets validation status
- `focus()` - Throws TNotSupportedException (cannot set focus to hidden field)

## See Also

- [TControl](./TControl.md)
- [IPostBackDataHandler](./IPostBackDataHandler.md)
