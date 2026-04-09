# TJuiEventParameter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiEventParameter](./TJuiEventParameter.md)

**Location:** `framework/Web/UI/JuiControls/TJuiEventParameter.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Encapsulates callback parameters for TJui* component events. Provides helper methods to retrieve PRADO controls from client-side IDs returned by callbacks.

## Key Properties/Methods

- `getControl($id)` - Retrieves a PRADO control from its client-side ID
- `__get($name)` - Dynamic property access (e.g., `$param->DraggableControl`)
- `getCallbackParameter()` - Gets the callback parameter from jQuery UI

## See Also

- [TJuiDroppable](TJuiDroppable.md)
- [TJuiDraggable](TJuiDraggable.md)
