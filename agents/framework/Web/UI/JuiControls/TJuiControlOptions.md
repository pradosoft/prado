# Web/UI/JuiControls/TJuiControlOptions

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiControlOptions`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiControlOptions.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Helper class that collects jQuery UI widget options for a control. The control must implement IJuiOptions. Options are validated against the control's getValidOptions() array. Options and event handlers are serialized to JSON for the jQuery UI widget.

## Key Properties/Methods

- `getOptions()` / `setOption($name, $value)` - Manage widget options
- `toArray()` - Returns options array with event handlers attached
- `raiseCallbackEvent($param)` - Raises the specific callback event handler

## See Also

- [IJuiOptions](IJuiOptions.md)
- [TJuiControlAdapter](TJuiControlAdapter.md)
