# Web/UI/TEventContent

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [UI](./INDEX.md) / **`TEventContent`**

## Class Info
**Location:** `framework/Web/UI/TEventContent.php`
**Namespace:** `Prado\Web\UI`

## Overview
TEventContent loads child controls by raising a broadcast event ('fx' global event). Handlers of the event add their own controls to the child control list passed as the event parameter. This enables a decoupled approach to adding controls dynamically via event handlers.

## Key Properties/Methods

- `BroadcastEvent` (stored in controlstate) - The `fx` event name to raise during `createChildControls`. Handlers receive `$this` as sender and a `TEventParameter` wrapping the `Controls` list as `$param`.
- `createChildControls()` - Raises the configured broadcast event; then calls `parent::createChildControls()`.

## See Also

- [TCompositeControl](./TCompositeControl.md)
- [TBroadcastEventParameter](./TBroadcastEventParameter.md)

(End of file - total 18 lines)
