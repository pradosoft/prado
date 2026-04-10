# Web/UI/JuiControls/TJuiSelectable

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [JuiControls](./INDEX.md) / **`TJuiSelectable`**

## Class Info
**Location:** `framework/Web/UI/JuiControls/TJuiSelectable.php`
**Namespace:** `Prado\Web\UI\JuiControls`

## Overview
Selectable list based on jQuery UI Selectable interaction. Extends [TActivePanel](../ActiveControls/TActivePanel.md) and internally renders a [TRepeater](../WebControls/TRepeater.md) as an unordered list. Items can be selected individually or in a group using click or lasso selection.

## Key Properties/Methods

- `setDataSource($data)` - Sets data source for selectable items
- `getSelectables()` - Returns the internal TRepeater
- `getOptions()` - Selectable options (filter, tolerance, etc.)
- `getValidOptions()` - Valid options: appendTo, autoRefresh, cancel, delay, disabled, distance, filter, tolerance
- `getValidEvents()` - Events: create, selected, selecting, start, stop, unselected, unselecting
- `onSelected($params)` - Raises OnSelected event
- `onSelecting($params)` - Raises OnSelecting event
- `onUnselected($params)` - Raises OnUnselected event
- `onUnselecting($params)` - Raises OnUnselecting event

## See Also

- [TJuiSortable](TJuiSortable.md)
- [TJuiSelectableTemplate](TJuiSelectableTemplate.md)
