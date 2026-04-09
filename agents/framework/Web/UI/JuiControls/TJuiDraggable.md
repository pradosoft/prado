# TJuiDraggable

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiDraggable](./TJuiDraggable.md)

**Location:** `framework/Web/UI/JuiControls/TJuiDraggable.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Draggable panel based on jQuery UI Draggable interaction. Extends [TActivePanel](../ActiveControls/TActivePanel.md). Can be moved using the mouse and dropped over a [TJuiDroppable](TJuiDroppable.md).

## Key Properties/Methods

- `getOptions()` - Draggable options (axis, containment, cursor, helper, etc.)
- `getValidOptions()` - Valid options: addClasses, appendTo, axis, cancel, connectToSortable, containment, cursor, cursorAt, delay, disabled, distance, grid, handle, helper, iframeFix, opacity, refreshPositions, revert, revertDuration, scope, scroll, scrollSensitivity, scrollSpeed, snap, snapMode, snapTolerance, stack, zIndex
- `getValidEvents()` - Events: create, drag, start, stop
- `onCreate($params)` - Raises OnCreate event
- `onDrag($params)` - Raises OnDrag event
- `onStart($params)` - Raises OnStart event
- `onStop($params)` - Raises OnStop event

## See Also

- [TJuiDroppable](TJuiDroppable.md)
- [TJuiEventParameter](TJuiEventParameter.md)
