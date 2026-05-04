# Web/UI/JuiControls/TJuiControlAdapter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiControlAdapter`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiControlAdapter.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Base adapter class for jQuery UI widget controls. Publishes jQuery UI CSS/JS assets and sets up TJuiCallbackPageStateTracker to track JuiOptions changes during callbacks.

## Key Properties/Methods

- `setJuiBaseStyle($value)` / `getJuiBaseStyle()` - jQuery UI theme style
- `publishJuiStyle($file)` - Publish jQuery UI CSS asset
- `onInit($param)` - Replaces default StateTracker with TJuiCallbackPageStateTracker
- `onPreRender($param)` - Registers jqueryui script and publishes CSS

## See Also

- [TJuiCallbackPageStateTracker](TJuiCallbackPageStateTracker.md)
- [TJuiControlOptions](TJuiControlOptions.md)
