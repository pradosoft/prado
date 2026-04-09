# TActivePanel

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActivePanel](./TActivePanel.md)

**Location:** `framework/Web/UI/ActiveControls/TActivePanel.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Active counterpart to TPanel. Allows client-side content updates during callback response via the `render()` method. Supports deferred rendering when render() is called before OnPreRender.

## Key Properties/Methods

- `render($writer)` - Renders and replaces panel content on client-side
- `getActiveControl()` - Returns [TBaseActiveControl](./TBaseActiveControl.md) options

## See Also

- `TPanel`, [TCallbackClientScript](./TCallbackClientScript.md)
