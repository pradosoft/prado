# Web/UI/ActiveControls/TCallbackResponseWriter

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackResponseWriter`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackResponseWriter.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Wraps content within HTML comment boundaries for multiple chunk delivery in callback responses. Each chunk gets a unique boundary identifier, allowing the client to update multiple HTML elements from a single response.

## Key Properties/Methods

- `getBoundary()` / `setBoundary($value)` - Boundary identifier
- `flush()` - Returns content wrapped in boundary comments

## See Also

- [TCallbackResponseAdapter](./TCallbackResponseAdapter.md), [TActivePageAdapter](./TActivePageAdapter.md)
