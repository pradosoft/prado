# THttpResponseAdapter

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [THttpResponseAdapter](./THttpResponseAdapter.md)

**Location:** `framework/Web/THttpResponseAdapter.php`
**Namespace:** `Prado\Web`

## Overview

THttpResponseAdapter allows the base HTTP response class to change behavior without modifying the class hierarchy. It wraps a [THttpResponse](./THttpResponse.md) and delegates operations while allowing subclasses to override specific behaviors.

## Key Properties/Methods

- `getResponse()` - Returns the adapted [THttpResponse](./THttpResponse.md) object
- `flushContent($continueBuffering)` - Invoked when the response flushes content and headers
- `httpRedirect($url)` - Invoked when redirecting to another page
- `createNewHtmlWriter($type, $writer)` - Creates a new HtmlWriter instance
- `setResponseData($data)` - Throws [TInvalidOperationException](../../Exceptions/TInvalidOperationException.md) (unavailable by default)
- `getResponseData()` - Throws [TInvalidOperationException](../../Exceptions/TInvalidOperationException.md) (unavailable by default)

## See Also

- [THttpResponse](./THttpResponse.md)
