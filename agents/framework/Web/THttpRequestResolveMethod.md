# Web/THttpRequestResolveMethod

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`THttpRequestResolveMethod`**

## Class Info
**Location:** `framework/Web/THttpRequestResolveMethod.php`
**Namespace:** `Prado\Web`

## Overview
THttpRequestResolveMethod defines the enumerable type for determining which service handles a user request. It controls the matching logic between request parameters and configured services.

## Key Properties/Methods

- `ServiceOrder` - Uses the first service matching a request parameter (legacy behavior before 4.2.2)
- `ParameterOrder` - Uses the first request parameter matching a configured service (default since 4.2.2)

## See Also

- [THttpRequest](./THttpRequest.md)
