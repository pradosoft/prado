# TMultiView

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [WebControls](./INDEX.md) > [TMultiView](./TMultiView.md)

**Location:** `framework/Web/UI/WebControls/TMultiView.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview

TMultiView serves as a container for a group of TView controls. Only one view can be active at a time. It responds to command events from buttons to switch views and raises OnActiveViewChanged when the active view changes.

## Key Properties/Methods

- `getActiveViewIndex()` / `setActiveViewIndex()` - Gets or sets zero-based active view index
- `getActiveView()` / `setActiveView()` - Gets or sets active TView control
- `getViews()` - Returns the TViewCollection
- `onActiveViewChanged()` - Raises OnActiveViewChanged event
- `bubbleEvent()` - Handles view-related command events (NextView, PreviousView, SwitchViewID, SwitchViewIndex)
- `render()` - Renders the currently active view

## See Also

- [TView](./TView.md)
- [TViewCollection](./TViewCollection.md)
