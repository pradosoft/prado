# Web/UI/ActiveControls/TCallbackErrorHandler

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackErrorHandler`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackErrorHandler.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Captures errors and exceptions during callback processing and sends them back to the client. In debug mode, displays exception stack traces via `TJavascriptLogger`. In production, logs errors and returns HTTP 500 status.

## Key Properties/Methods

- `displayException($exception)` - Sends error details to client in JSON format
- `getExceptionStackTrace($exception)` - Formats exception data for client response

## See Also

- [TInvalidCallbackException](./TInvalidCallbackException.md), [TActivePageAdapter](./TActivePageAdapter.md)
