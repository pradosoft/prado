# Web/UI/ActiveControls/TCallbackEventParameter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackEventParameter`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackEventParameter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Provides parameter passed during callback requests. Contains the callback request parameter and allows setting response data to return to the client. Uses [TCallbackResponseWriter](./TCallbackResponseWriter.md) for rendering content.

## Key Properties/Methods

- `getCallbackParameter()` - The callback request parameter
- `getNewWriter()` - Returns new [TCallbackResponseWriter](./TCallbackResponseWriter.md) for rendering
- `getResponseData()` / `setResponseData($value)` - Data to return to client

## See Also

- [ICallbackEventHandler](./ICallbackEventHandler.md), [TActivePageAdapter](./TActivePageAdapter.md)
