# TEventContent

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [TEventContent](./TEventContent.md)

**Location:** `framework/Web/UI/TEventContent.php`
**Namespace:** `Prado\Web\UI`

## Overview

TEventContent loads child controls by raising a broadcast event ('fx' global event). Handlers of the event add their own controls to the child control list passed as the event parameter. This enables a decoupled approach to adding controls dynamically via event handlers.

## Key Properties/Methods

- `BroadcastEvent` - The event name to raise for loading child controls
- `createChildControls()` - Raises the broadcast event for handlers to add controls

## See Also

- [TCompositeControl](./TCompositeControl.md)
- [TBroadcastEventParameter](./TBroadcastEventParameter.md)

(End of file - total 18 lines)
