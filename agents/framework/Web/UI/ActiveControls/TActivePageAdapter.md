# Web/UI/ActiveControls/TActivePageAdapter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TActivePageAdapter`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TActivePageAdapter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Callback request handler for TPage. Intercepts callback requests, routes to `raiseCallbackEvent()`, and sends response via `X-PRADO-*` headers. Manages deferred control rendering, response data, page state updates, and delayed redirects.

## Key Properties/Methods

- `processCallbackEvent($writer)` - Processes the callback request
- `renderCallbackResponse($writer)` - Renders callback response with headers
- `registerControlToRender($control, $writer)` - Registers control for deferred rendering
- `renderDeferredControl($control, $writer)` - Renders a registered control with `render()`; its `onRenderFilter` handlers, then those of ancestors with `RenderFilterFragments` (`getDeferredFilters()`, raised by `filterFragment()` with `getIsFragment()` true, inside `getFragmentContext()`), filter what it writes into a `TCallbackResponseWriter`, which keeps its boundary. Each capture starts `getControlDepth()` levels below its filtering control, and `filterFragment()` encloses the fragment in the markers of the levels above it, so `TControl.RenderFilterDepth` counts as in a full render. (@since 4.4.0)
- `getCallbackEventTarget()` - Gets the callback event target control
- `setCallbackEventTarget($control)` - Sets the callback event target
- `getCallbackClientHandler()` - Returns [TCallbackClientScript](./TCallbackClientScript.md) handler
- `CALLBACK_DATA_HEADER`, `CALLBACK_ACTION_HEADER`, etc. - Response header constants
- `CALLBACK_CSRFTOKEN_HEADER` (`X-PRADO-CSRF-TOKEN`) - A fresh `THttpRequest::getCsrfToken()` sent by `renderResponse()` while `TPage.EnableCsrfValidation` is on; `ajax3.js` `updateCsrfToken()` writes it into the `PRADO_CSRF_TOKEN` field (@since 4.4.0)

## See Also

- `TControlAdapter`, [ICallbackEventHandler](./ICallbackEventHandler.md), [TCallbackResponseAdapter](./TCallbackResponseAdapter.md)
