# Web/UI/WebControls/TRadioButton

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRadioButton`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRadioButton.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
TRadioButton displays a radio button on the page. It extends TCheckBox and uses GroupName to group together a set of radio buttons. The OnCheckedChanged event is raised when the checked state changes between posts to the server.

## Key Properties/Methods

- `getGroupName()` / `setGroupName($value)` - Name of the radio button group
- `getUniqueGroupName()` - Unique group name across page hierarchy
- `setUniqueGroupName($value)` - Sets unique group name
- `getRadioButtonsInGroup()` - Returns array of TRadioButtons with same group
- `getChecked()` / `setChecked($value)` - Whether the radio button is checked
- `getAutoPostBack()` / `setAutoPostBack($value)` - Whether to postback on change
- `getEnableClientScript()` / `setEnableClientScript($value)` - Whether to render JavaScript

## See Also

- [TCheckBox](./TCheckBox.md)
- [TRadioButtonList](./TRadioButtonList.md)
- [TRadioButtonItem](./TRadioButtonItem.md)
