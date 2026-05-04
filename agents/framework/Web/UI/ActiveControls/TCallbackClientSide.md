# Web/UI/ActiveControls/TCallbackClientSide

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [ActiveControls](./INDEX.md) / **`TCallbackClientSide`**

## Class Info
**Location:** `framework/Web/UI/ActiveControls/TCallbackClientSide.php`
**Namespace:** `Prado\Web\UI\ActiveControls`

## Overview
Stores client-side callback options and event handlers. Manages the callback lifecycle events (onPreDispatch, onLoading, onLoaded, onInteractive, onComplete, onSuccess, onFailure, onException) and configuration options like PostState, RequestTimeOut, and RetryLimit.

## Key Properties/Methods

- `setOnPreDispatch($javascript)` / `getOnPreDispatch()` - Before request dispatch
- `setOnLoading($javascript)` / `getOnLoading()` - Request initiated
- `setOnSuccess($javascript)` / `getOnSuccess()` - Successful response
- `setOnFailure($javascript)` / `getOnFailure()` - Failed response
- `setPostState($value)` / `getPostState()` - Post form inputs with callback
- `setRequestTimeOut($value)` / `getRequestTimeOut()` - Request timeout in ms
- `setRetryLimit($value)` / `getRetryLimit()` - Number of retries on timeout

## See Also

- [TCallbackOptions](./TCallbackOptions.md), [TBaseActiveCallbackControl](./TBaseActiveCallbackControl.md)
