# Web/Services/TServerEventResponseSink

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TServerEventResponseSink`**

## Class Info
**Location:** `framework/Web/Services/TServerEventResponseSink.php`
**Namespace:** `Prado\Web\Services`
**Extends:** `Prado\TComponent`
**Implements:** [`IServerEventSink`](IServerEventSink.md)
**Since:** 4.4.0

## Overview
The PHP SAPI sink of [`TServerEventService`](TServerEventService.md), over `THttpResponse`. Constructor: `($response, int $timeLimit = 0)`; a time limit of 0 (or negative) leaves `set_time_limit()` unchanged.

| Method | Behavior |
|---|---|
| `open($headers)` | `Content-Type` through `setContentType()` and `setCharset()` (`applyContentType()`), other fields through `appendHeader()`, then `openOutput()` |
| `openOutput()` (protected) | `zlib.output_compression` off, `ignore_user_abort(true)`, `set_time_limit(TimeLimit)`, `THttpResponse::flush(false)`, every removable output buffer ended |
| `write($chunk)` | `THttpResponse::write()` and `flush()`, then `isClientConnected()` |
| `isClientConnected()` (protected) | `connection_aborted() === 0` |
| `close()` | nothing; the response ends with the request |

Tests override `openOutput()` and `isClientConnected()`.
