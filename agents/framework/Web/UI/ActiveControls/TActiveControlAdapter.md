# Web/UI/ActiveControls/TActiveControlAdapter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActiveControlAdapter`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActiveControlAdapter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Adapts any control to add active/callback support. Instantiates [TBaseActiveControl](./TBaseActiveControl.md) or [TBaseActiveCallbackControl](./TBaseActiveCallbackControl.md) depending on whether the control implements [ICallbackEventHandler](./ICallbackEventHandler.md). Tracks viewstate changes via [TCallbackPageStateTracker](./TCallbackPageStateTracker.md).

## Key Properties/Methods

- `getBaseActiveControl()` - Returns TBaseActiveCallbackControl or TBaseActiveControl
- `setBaseActiveControl($control)` - Sets base active control instance
- `onPreRender($param)` - Registers ajax script
- `render($writer)` - Renders with visibility handling
- `onLoad($param)` - Starts viewstate tracking if needed
- `saveState()` - Responds to viewstate changes
- `getStateTracker()` - Returns [TCallbackPageStateTracker](./TCallbackPageStateTracker.md)
- `getIsTrackingPageState()` - Checks if state tracking is needed

## See Also

- `TControlAdapter`, [IActiveControl](./IActiveControl.md), [TCallbackPageStateTracker](./TCallbackPageStateTracker.md)
