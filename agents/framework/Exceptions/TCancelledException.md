# Exceptions/TCancelledException

### Directories
[framework](../INDEX.md) / [Exceptions](./INDEX.md) / **`TCancelledException`**

## Class Info
**Location:** `framework/Exceptions/TCancelledException.php`
**Namespace:** `Prado\Exceptions`
**Extends:** [`TSystemException`](TSystemException.md)
**Since:** 4.4.0

## Overview
Thrown from a wait that a [`TCancellationToken`](../IO/Async/TCancellationToken.md) cancels, from [`TFuture::await()`](../IO/Async/TFuture.md) with a cancelled token, and from a [`TAsync::timeout()`](../IO/Async/TAsync.md) that elapses. `getPrevious()` carries the reason given to `cancel()` when one is.

| Code | Meaning |
|---|---|
| `async_cancelled` | `cancel()` with no reason or a non-cancellation reason (as previous). |
| `async_timeout` | A token timeout or `TAsync::timeout()` deadline; `{0}` is the seconds. |
| `async_any_empty` | `TAsync::any()` with no futures. |
