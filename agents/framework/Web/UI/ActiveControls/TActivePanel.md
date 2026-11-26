# Web/UI/ActiveControls/TActivePanel

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActivePanel`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActivePanel.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Active counterpart to TPanel. Allows client-side content updates during callback response via the `render()` method. Supports deferred rendering when render() is called before OnPreRender.

## Key Properties/Methods

- `render($writer)` - In a callback before prerender, defers the render and disables the inner active updates; after prerender, disables them, renders, and replaces the panel on the client; outside a callback, only renders. TActiveTableCell, TActiveTableRow, TActiveSvg, and the data-bound active controls render the same way
- `getActiveControl()` - Returns [TBaseActiveControl](./TBaseActiveControl.md) options

## See Also

- `TPanel`, [TCallbackClientScript](./TCallbackClientScript.md), [TLazyPanel](./TLazyPanel.md) (loads its content by callback)
