# TActivePageAdapter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [UI](../INDEX.md) > [ActiveControls](./INDEX.md) > [TActivePageAdapter](./TActivePageAdapter.md)

**Location:** `framework/Web/UI/ActiveControls/TActivePageAdapter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview

Callback request handler for TPage. Intercepts callback requests, routes to `raiseCallbackEvent()`, and sends response via `X-PRADO-*` headers. Manages deferred control rendering, response data, page state updates, and delayed redirects.

## Key Properties/Methods

- `processCallbackEvent($writer)` - Processes the callback request
- `renderCallbackResponse($writer)` - Renders callback response with headers
- `registerControlToRender($control, $writer)` - Registers control for deferred rendering
- `getCallbackEventTarget()` - Gets the callback event target control
- `setCallbackEventTarget($control)` - Sets the callback event target
- `getCallbackClientHandler()` - Returns [TCallbackClientScript](./TCallbackClientScript.md) handler
- `CALLBACK_DATA_HEADER`, `CALLBACK_ACTION_HEADER`, etc. - Response header constants

## See Also

- `TControlAdapter`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TCallbackResponseAdapter](./TCallbackResponseAdapter.md)
