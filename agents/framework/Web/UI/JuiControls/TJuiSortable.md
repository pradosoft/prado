# TJuiSortable

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [JuiControls](./INDEX.md) > [TJuiSortable](./TJuiSortable.md)

**Location:** `framework/Web/UI/JuiControls/TJuiSortable.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview

Sortable list based on jQuery UI Sortable interaction. Extends [TActivePanel](../ActiveControls/TActivePanel.md) and internally renders a [TRepeater](../WebControls/TRepeater.md) as an unordered list. Items can be sorted by dragging and dropping.

## Key Properties/Methods

- `setDataSource($data)` - Sets data source for sortable items
- `getSortables()` - Returns the internal TRepeater
- `getOptions()` - Sortable options (connectWith, containment, cursor, handle, etc.)
- `getValidOptions()` - Valid options: appendTo, axis, cancel, connectWith, containment, cursor, cursorAt, delay, disabled, distance, dropOnEmpty, forceHelperSize, forcePlaceholderSize, grid, handle, helper, items, opacity, placeholder, revert, scroll, scrollSensitivity, scrollSpeed, tolerance, zIndex
- `getValidEvents()` - Events: activate, beforeStop, change, create, deactivate, out, over, receive, remove, sort, start, stop, update
- `onActivate($params)` - Raises OnActivate event
- `onBeforeStop($params)` - Raises OnBeforeStop event
- `onChange($params)` - Raises OnChange event
- `onSort($params)` - Raises OnSort event
- `onUpdate($params)` - Raises OnUpdate event

## See Also

- [TJuiSelectable](TJuiSelectable.md)
- [TJuiSortableTemplate](TJuiSortableTemplate.md)
