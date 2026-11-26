# Web/UI/JuiControls/TJuiControlOptions

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiControlOptions`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiControlOptions.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Helper class that collects jQuery UI widget options for a control. The control must implement IJuiOptions. Options are validated against the control's getValidOptions() array. Options and event handlers are serialized to JSON for the jQuery UI widget.

## Key Properties/Methods

- `__set($name, $value)` / `__get($name)` - Set and get an option; the name matches a valid option without case. A string value (from a template) becomes `null`, `true`, `false`, a number, or a `TJavaScriptLiteral` for `function...`; any other value is stored as is
- `toArray()` - Returns options array with event handlers attached
- `raiseCallbackEvent($param)` - Raises the `On` event the callback names, only for an event in the control's `getValidEvents()`

## See Also

- [IJuiOptions](IJuiOptions.md)
- [TJuiControlAdapter](TJuiControlAdapter.md)
