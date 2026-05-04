# Web/UI/ActiveControls/TActiveClientScript

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveClientScript`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveClientScript.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TClientScript. Can render itself during AJAX callbacks, making JavaScript variables and functions available to the page. Scripts execute after DOM modifications are complete when rendered during callback.

## Key Properties/Methods

- `renderCustomScriptFile($writer)` - Renders script file (different behavior for callback vs postback)
- `renderCustomScript($writer)` - Renders inline script with callback support

## See Also

- `TClientScript`, [TActivePageAdapter](./TActivePageAdapter.md)
