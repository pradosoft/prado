# IO/Async/TAsyncStream

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TAsyncStream`**

## Class Info
**Location:** `framework/IO/Async/TAsyncStream.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\IO\Stream\TStreamDecorator`
**Since:** 4.4.0

## Overview
Decorates a stream that owns a PHP stream resource (`TStream`, `TSocketStream`, `TPipeStream`) with reads and writes that wait for readiness on [`TAsync::getLoop()`](TAsync.md) instead of blocking. The constructor sets the inner stream non-blocking. `read()` returns `''` only at end of stream; while no bytes are ready the current fiber suspends until the stream is readable. `write()` writes the whole string, suspending while the buffer is full. This removes the ambiguity of a non-blocking `read()` returning `''` for both "nothing yet" and "end of stream".

```php
$stream = new TAsyncStream(TSocketStream::connect('tcp://example.com:80'));
$stream->write("GET / HTTP/1.0\r\nHost: example.com\r\n\r\n");
$body = $stream->getContents();   // reads to end of stream, yielding meanwhile
```

## Methods

| Method | Description |
|---|---|
| `__construct(?StreamInterface $stream = null)` | Decorates the stream; an `IResource` that is open is set non-blocking. |
| `read(int $length, ?TCancellationToken $token = null): string` | Inner `read()`; on `''` without `eof()`, `waitReadable()` then retry. |
| `write(string $string, ?TCancellationToken $token = null): int` | Inner `write()` in a loop; `waitWritable()` while bytes remain. Returns the string length. |
| `waitReadable(?TCancellationToken $token = null): void` / `waitWritable(...)` | The bare waits: a one-shot `onReadable()`/`onWritable()` callback on the loop, suspend, cancel the callback on the way out. |

## Gotchas
- The inner stream must be an `IResource` with an open resource for a wait; otherwise `TInvalidOperationException('asyncstream_no_resource')`. Ready bytes still return without a wait.
- A cancelled token throws its `TCancelledException` from the wait and leaves no callback on the loop.
- Readiness is level-triggered; a stream reported readable whose `read()` returns `''` without `eof()` (out-of-band or TLS bookkeeping) waits again.
- `getContents()` and `__toString()` come from the decorator and read through the async `read()`.
