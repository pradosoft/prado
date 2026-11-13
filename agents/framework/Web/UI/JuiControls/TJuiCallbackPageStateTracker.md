# Web/UI/JuiControls/TJuiCallbackPageStateTracker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiCallbackPageStateTracker`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiCallbackPageStateTracker.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Extends TCallbackPageStateTracker to track changes to JuiOptions during callbacks. When options change server-side, registers JavaScript to update the jQuery UI widget options on the client.

## Key Properties/Methods

- `addStatesToTrack()` - Adds JuiOptions to tracked states
- `updateJuiOptions($options)` - Updates widget options via JavaScript; each value is encoded by `TJavaScript::encode()` with empty strings kept, so a string is quoted and escaped, an array becomes a JavaScript array or object, and a `TJavaScriptLiteral` stays code

## See Also

- [TJuiControlAdapter](TJuiControlAdapter.md)
- [TJuiControlOptions](TJuiControlOptions.md)
