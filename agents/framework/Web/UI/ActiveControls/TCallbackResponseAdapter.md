# Web/UI/ActiveControls/TCallbackResponseAdapter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackResponseAdapter`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackResponseAdapter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Alters `THttpResponse` for callback output. Uses [TCallbackResponseWriter](./TCallbackResponseWriter.md) instances instead of plain writers, allowing multiple content chunks with boundary delimiters. Supports delayed redirects and response data.

## Key Properties/Methods

- `createNewHtmlWriter($type, $response)` - Creates [TCallbackResponseWriter](./TCallbackResponseWriter.md)
- `flushContent($continueBuffering)` - Flushes all writer contents
- `getResponseData()` / `setResponseData($data)` - Callback response data
- `httpRedirect($url)` - Delays redirect until after page processing

## See Also

- [TCallbackResponseWriter](./TCallbackResponseWriter.md), [TActivePageAdapter](./TActivePageAdapter.md), `THttpResponse`
