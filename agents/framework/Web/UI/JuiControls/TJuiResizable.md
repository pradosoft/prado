# Web/UI/JuiControls/TJuiResizable

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiResizable`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiResizable.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Resizable panel based on jQuery UI Resizable interaction. Extends [TActivePanel](../ActiveControls/TActivePanel.md). Shows a resize handle on the bottom-right corner.

## Key Properties/Methods

- `getOptions()` - Resizable options (minHeight, minWidth, maxHeight, maxWidth, etc.)
- `getValidOptions()` - Valid options: alsoResize, animate, animateDuration, animateEasing, aspectRatio, autoHide, cancel, containment, delay, disabled, distance, ghost, grid, handles, helper, maxHeight, maxWidth, minHeight, minWidth
- `getValidEvents()` - Events: create, resize, start, stop
- `onCreate($params)` - Raises OnCreate event
- `onResize($params)` - Raises OnResize event
- `onStart($params)` - Raises OnStart event
- `onStop($params)` - Raises OnStop event

## See Also

- [TJuiDraggable](TJuiDraggable.md)
