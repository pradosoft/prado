# IO/TStreamNotificationCallback

### Directories
[framework](../INDEX.md) / [IO](./INDEX.md) / **`TStreamNotificationCallback`**

## Class Info
**Location:** `framework/IO/TStreamNotificationCallback.php`
**Namespace:** `Prado\IO`

## Overview
Wraps PHP's stream context notification callback mechanism into a `TComponent`-based event system. Intercepts the low-level callback triggered by `fopen()`, `file_get_contents()`, and `file_put_contents()` when using a stream context with a `notification` parameter.

The class is **invokable** (`__invoke`) — it is passed directly as the stream context notification callable. Each `STREAM_NOTIFY_*` code is mapped to a corresponding Prado `on*` event (`onProgress`, `onMimeType`, `onFailure`, etc.) so handlers can be attached via the normal `$obj->onProgress[] = ...` event syntax.

The companion class [TStreamNotificationParameter](./TStreamNotificationParameter.md) is reused (mutated in-place) for all events in a single stream operation, minimizing object allocation.

## Key Constants

| Constant | Value | Description |
|---|---|---|
| `NOTIFICATION` | `'notification'` | Key used in stream context params array |

## Key Properties (read-only via getters)

| Property | Type | Description |
|---|---|---|
| `Severity` | `?int` | Severity of last failure (`STREAM_NOTIFY_SEVERITY_*`) |
| `Message` | `?string` | Last notification message string |
| `MessageCode` | `?int` | Message code from last failure |
| `BytesTransferred` | `?int` | Bytes transferred so far (set on `PROGRESS` and `FILE_SIZE_IS`) |
| `FileSize` | `?int` | Total file size in bytes (set on `FILE_SIZE_IS` and `PROGRESS`) |
| `MimeType` | `?string` | MIME type parsed from `STREAM_NOTIFY_MIME_TYPE_IS` |
| `Charset` | `?string` | Charset extracted from MIME type `Content-Type` header |
| `IsCompleted` | `bool` | `true` after `STREAM_NOTIFY_COMPLETED` fires |
| `IsFailure` | `bool` | `true` after `STREAM_NOTIFY_FAILURE` fires |
| `Parameter` | `?TStreamNotificationParameter` | Shared event parameter object (reused per invocation) |
| `Callbacks` | `TWeakCallableCollection` | Additional raw callbacks forwarded after Prado events |

## Key Methods

### Static Factory

- `static filterStreamContext(mixed $context): mixed` — The primary entry point for consumers. Accepts a `TStreamNotificationCallback`, a raw callable, or an array of stream context options. Returns a proper `resource` stream context.
  - If `$context` is callable: wraps in `stream_context_create(null, ['notification' => $context])`.
  - If `$context` is array: extracts `'notification'` key; detects any `on*` event keys (case-insensitive) and creates/wraps a `TStreamNotificationCallback` automatically; applies remaining keys as context options.
  - If `'notification'` is itself an array: treated as a component config array (must include `'class'`); created via `Prado::createComponent()`.
- `static getContextNotificationCallback(mixed $context): mixed` — Extracts the `'notification'` param from an existing stream context resource via `stream_context_get_params()`.

### Instance

- `__construct(...$args)` — Optional: pass callables to pre-populate `Callbacks`.
- `getCallbacks(): TWeakCallableCollection` — Returns the collection of raw pass-through callbacks. Lazily created.
- `__invoke(int $notification_code, int $severity, ?string $message, int $message_code, int $bytes_transferred, int $bytes_max): void` — Called directly by PHP's stream layer. Dispatches to the appropriate `on*` event and then forwards to all entries in `Callbacks`.

### Events

Each event receives a [TStreamNotificationParameter](./TStreamNotificationParameter.md) `$param` and is raised via `raiseEvent()`:

| Event | PHP Constant | Notes |
|---|---|---|
| `onResolve` | `STREAM_NOTIFY_RESOLVE` (1) | Address resolved |
| `onConnected` | `STREAM_NOTIFY_CONNECT` (2) | Connection established |
| `onAuthRequired` | `STREAM_NOTIFY_AUTH_REQUIRED` (3) | Auth needed |
| `onAuthResult` | `STREAM_NOTIFY_AUTH_RESULT` (10) | Auth result |
| `onRedirected` | `STREAM_NOTIFY_REDIRECTED` (6) | Redirect |
| `onMimeType` | `STREAM_NOTIFY_MIME_TYPE_IS` (4) | Sets `MimeType`/`Charset` before firing |
| `onFileSize` | `STREAM_NOTIFY_FILE_SIZE_IS` (5) | Sets `BytesTransferred`/`FileSize` |
| `onProgress` | `STREAM_NOTIFY_PROGRESS` (7) | Sets `BytesTransferred`/`FileSize` |
| `onCompleted` | `STREAM_NOTIFY_COMPLETED` (8) | Sets `IsCompleted = true` |
| `onFailure` | `STREAM_NOTIFY_FAILURE` (9) | Sets `IsFailure = true`, `Severity`, `MessageCode` |

## Patterns & Gotchas

- **The `TStreamNotificationParameter` is reused** — the same object is mutated on each invocation. Do not hold a reference to it after the event handler returns; clone it if you need to preserve snapshot values.
- **`Callbacks` fires after Prado events** — raw callables in `Callbacks` always receive the original PHP stream notification signature `($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max)`, not a `TStreamNotificationParameter`.
- **`filterStreamContext()` merges `on*` keys from the options array directly onto the `TStreamNotificationCallback`** — it calls `setSubProperty($property, $value)`, so the keys must be valid properties or events on the callback instance.
- **MIME charset parsing** — the `onMimeType` handler splits on `;` and `=` to extract charset; only fires if the MIME type header contains a `;charset=` segment.
- **`IsCompleted` and `IsFailure` are one-way flags** — once set to `true` they are never reset. Create a new instance per stream operation.
- **Typical usage:**
  ```php
  $cb = new TStreamNotificationCallback();
  $cb->onProgress[] = function($sender, $param) { echo $sender->getBytesTransferred(); };
  $ctx = TStreamNotificationCallback::filterStreamContext($cb);
  $data = file_get_contents($url, false, $ctx);
  ```
