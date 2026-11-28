# IO/INDEX.md

### Directories
[framework](../INDEX.md) / **`IO`**

## Purpose

Input/output utilities for the Prado framework: text writer abstractions, archive extraction, streams, sockets, processes, HTTP clients, and the event loop API.

## Subdirectories

| Directory | Purpose |
|---|---|
| [Async/](Async/INDEX.md) | The async API: [`IEventLoop`](Async/IEventLoop.md), [`ITickableEventLoop`](Async/ITickableEventLoop.md), the default [`TRevoltEventLoop`](Async/TRevoltEventLoop.md) over `revolt/event-loop`, [`TLoopSuspension`](Async/TLoopSuspension.md); the primitives [`TFuture`](Async/TFuture.md), [`TDeferred`](Async/TDeferred.md), [`TCancellationToken`](Async/TCancellationToken.md), [`TFiberContext`](Async/TFiberContext.md), the [`TAsync`](Async/TAsync.md) facade (`run`, `all`, `any`, `timeout`, `sleep`), [`TAsyncStream`](Async/TAsyncStream.md) for readiness-driven reads and writes, and [`TChannel`](Async/TChannel.md) for values between fibers. `TProcess::wait()` and `TProcessFork::wait()` suspend on the loop. |
| [Http/](Http/INDEX.md) | PSR-7 HTTP messages (`TMessage`, `TRequest`, `TResponse`) and the PSR-17 `THttpFactory`. |
| [HttpClient/](HttpClient/INDEX.md) | The PSR-18 `THttpClient`, its `IHttpHandler` transports (`TCurlHttpHandler`, `TCurlMultiHttpHandler`, `TSocketHttpHandler`, `TStreamHttpHandler`), `TCachedHttpClient`, and the PSR-18 exceptions. |
| [Socket/](Socket/INDEX.md) | Socket streams, servers, the dependency-free [`TSocketReactor`](Socket/TSocketReactor.md) event loop, and `TSocketServerModule`. |

`Prado\IO\Rpc` (the JSON-RPC and XML-RPC clients `TRpcClient`, `TJsonRpcClient`, `TXmlRpcClient` and the `TXmlRpcCodec`) is the `pradosoft/prado-rpc` package, built on `HttpClient/`; see [Web/Services](../Web/Services/INDEX.md#rpc-the-pradosoftprado-rpc-package).

## Classes

### Text Writers

All writers implement [ITextWriter](./ITextWriter.md).

- **[ITextWriter](./ITextWriter.md)** — Interface: `write(string)`, `writeLine(string)`, `flush(): string`.

- **[TTextWriter](./TTextWriter.md)** — In-memory buffer. `write()` / `writeLine()` accumulate output; `flush()` returns the buffer and resets it. Use when you need to capture output as a string.

- **[TOutputWriter](./TOutputWriter.md)** — Writes directly to PHP output buffer (`echo`-equivalent).

- **[TStdOutWriter](./TStdOutWriter.md)** — Writes to `STDOUT` stream (useful in CLI context).

### Archive

- **[TTarFileExtractor](./TTarFileExtractor.md)** — Extracts TAR archives (local paths or remote `http://`/`ftp://` URLs). Remote archives are downloaded to a temp file first and cleaned up in `__destruct()`. Constructor takes the tar filename; call `extract($targetPath)` to unpack. `extractModify()` can rewrite extracted paths. Manifest scans close their archive handle for every `Throwable`.

### Stream Notifications

- **[TStreamNotificationCallback](./TStreamNotificationCallback.md)** — Stream context notification handler. Wraps PHP stream notification callbacks for monitoring file downloads (progress, authentication prompts, etc.).

- **[TStreamNotificationParameter](./TStreamNotificationParameter.md)** — Event parameter for stream notifications. Properties: `notification`, `severity`, `message`, `messageCode`, `bytesTransferred`, `bytesMax`.

## Gotchas

- `TTextWriter::flush()` **clears** the internal buffer — only call it when you're done accumulating output.
- `TTarFileExtractor` stores the temp download path in `_temp_tarname`; it is deleted automatically in `__destruct()`.
- Remote TAR downloads are synchronous and block execution.
- `TStream::seek()` past the end works on every seekable stream: the size is unchanged until a `write()`, which fills the gap with NUL bytes. PHP 8.2 fails that seek on `php://memory` and `php://temp` (PHP bug #52335, fixed in 8.3), so `TStream` records the position (`seekPastEnd()`) and extends the stream on the next write (`fillPastEndGap()`). An `fseek()` on the raw `getResource()` bypasses this.
