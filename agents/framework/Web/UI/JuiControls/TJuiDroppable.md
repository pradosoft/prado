# TJuiDroppable

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiDroppable](./TJuiDroppable.md)

**Location:** `framework/Web/UI/JuiControls/TJuiDroppable.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Drop target based on jQuery UI Droppable interaction. Extends [TActivePanel](../ActiveControls/TActivePanel.md). When a [TJuiDraggable](TJuiDraggable.md) is dropped, the OnDrop event is raised with the DraggableControl reference.

## Key Properties/Methods

- `getOptions()` - Droppable options (accept, activeClass, hoverClass, scope, tolerance, etc.)
- `getValidOptions()` - Valid options: accept, activeClass, addClasses, disabled, greedy, hoverClass, scope, tolerance
- `getValidEvents()` - Events: activate, create, deactivate, drop, out, over
- `onActivate($params)` - Raises OnActivate event
- `onCreate($params)` - Raises OnCreate event
- `onDeactivate($params)` - Raises OnDeactivate event
- `onDrop($params)` - Raises OnDrop event
- `onOut($params)` - Raises OnOut event
- `onOver($params)` - Raises OnOver event
- `onCallback($param)` - Raises OnCallback event

## See Also

- [TJuiDraggable](TJuiDraggable.md)
- [TJuiEventParameter](TJuiEventParameter.md)
