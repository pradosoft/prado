# Web/Services/ServerEvents/IServerEventSink

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`IServerEventSink`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/IServerEventSink.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Since:** 4.4.0

## Overview
The response a [`TServerEventStreamer`](TServerEventStreamer.md) writes a server-sent event stream to: headers once, chunks, then close. It separates the stream from the host that carries it.

| Method | Description |
|---|---|
| `open(array $headers): void` | Sends the response headers (field name => value) and readies unbuffered output. |
| `write(string $chunk): bool` | Sends a chunk now; returns whether the client is still connected. An event loop host may suspend the fiber until the client accepts the chunk. |
| `close(): void` | Ends the stream. |

## Implementations

| Host | Sink | Disconnect | Backpressure |
|---|---|---|---|
| PHP SAPI | [`TServerEventResponseSink`](TServerEventResponseSink.md) | `connection_aborted()` after a flush | none |
| HTTP/2 on the event loop (`belisoful/prado-http2`) | not in the framework: `open()` → `TH2Session::respond($stream, [':status' => '200'] + $headers)`, `write()` → `TH2Stream::writeAsync()`, `close()` → `markLocalClosed()` | the stream's close (`RST_STREAM` when a tab closes; the connection stays open) | the peer's flow-control window |
