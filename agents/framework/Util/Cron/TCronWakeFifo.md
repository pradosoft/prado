# Util/Cron/TCronWakeFifo

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronWakeFifo`**

## Class Info
**Location:** `framework/Util/Cron/TCronWakeFifo.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakeFifo` wakes the cron process through a named pipe: `signal()` writes one byte into `FifoFile`, which the waiting process reads. The pipe is created on first use with `posix_mkfifo()`. Both sides open it for reading and writing (`r+`, non-blocking), so opening never blocks and a byte written while the waiting process is busy stays in the pipe it holds open until its next `wait()`. Without a reader holding the pipe open a byte is discarded, the harmless no-op of a wake with no listener. `wait()` is a `stream_select()` on the pipe; `watch()` is the loop's `onReadable()`. It needs the `posix` extension and reaches the processes of one host.

## Properties

| Property | Default | Description |
|---|---|---|
| `FifoFile` | `<runtime>/cronwake.fifo` | The pipe path, as a path or a namespace. The transport is unavailable without `posix_mkfifo()` or when the directory does not exist. |

`close()` closes the pipe handle; the pipe file stays for the next process.

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronModule](TCronModule.md)
