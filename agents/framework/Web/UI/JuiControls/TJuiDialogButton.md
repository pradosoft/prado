# TJuiDialogButton

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiDialogButton](./TJuiDialogButton.md)

**Location:** `framework/Web/UI/JuiControls/TJuiDialogButton.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Button control for TJuiDialog. Must be a child of a TJuiDialog to bind callbacks to dialog buttons. Renders buttons specified in the dialog's buttons option.

## Key Properties/Methods

- `getText()` / `setText($value)` - Button caption
- `getPostBackOptions()` - Returns button configuration for jQuery UI dialog
- `onClick($params)` - Raises OnClick event
- `raiseCallbackEvent($param)` - Processes callback events

## See Also

- [TJuiDialog](TJuiDialog.md)
