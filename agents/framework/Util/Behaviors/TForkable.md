# Util/Behaviors/TForkable

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TForkable`**

## Class Info
**Location:** `framework/Util/Behaviors/TForkable.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
This behavior attaches the owner component's `fxPrepareForFork` and `fxRestoreAfterFork` methods as handlers for the PRADO global events of the same name. This enables components to participate in process forking via `[TProcessHelper](../Helpers/TProcessHelper.md)::fork()`.

## Key Properties/Methods

- `attachEventHandlers([TComponent](../TComponent.md) $component)` - Attaches `fxPrepareForFork` and `fxRestoreAfterFork` handlers if the component has these methods
- `detachEventHandlers([TComponent](../TComponent.md) $component)` - Detaches the fork event handlers

## See Also

- [TProcessHelper](../Helpers/TProcessHelper.md)
- [TCaptureForkLog](./TCaptureForkLog.md)
