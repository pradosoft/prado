# TActiveMultiView

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActiveMultiView](./TActiveMultiView.md)

**Location:** `framework/Web/UI/ActiveControls/TActiveMultiView.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TMultiView. Re-renders on callback when ActiveView or ActiveViewIndex is changed. Uses a span container element for the MultiView content.

## Key Properties/Methods

- `setActiveViewIndex($value)` - Sets active view by index with callback update
- `setActiveView($value)` - Sets active view with callback update
- `getContainerID()` - Returns container span ID
- `renderMultiView($writer)` - Renders MultiView wrapped in container span

## See Also

- `TMultiView`, [IActiveControl](./IActiveControl.md)
