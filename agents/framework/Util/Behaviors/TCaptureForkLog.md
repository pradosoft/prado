# Util/Behaviors/TCaptureForkLog

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TCaptureForkLog`**

## Class Info
**Location:** `framework/Util/Behaviors/TCaptureForkLog.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
This behavior captures logs from forked child processes and sends them back to the parent process. When `[TProcessHelper](../Helpers/TProcessHelper.md)::fork()` is called, this behavior creates socket pairs before the fork and routes child process logs to the parent. The parent receives logs when processing its own logs via `onCollectLogs`, whose handler is `collectLogsFromChildren()`. A final collection waits for every child's final logs; a non-final collection receives only the messages already sent.

A child replaces its `onFlushLogs` handlers with `sendLogsToParent()`. `TLogger::onFlushLogs` raises `onCollectLogs` first, so a child that forks sends its own children's logs with its own. The pending socket pair (`_forkConnection`) is separate from a child's link to its parent (`_childConnection`), so forking in a child keeps that link.

### Wire format
Each message is a 4-byte big-endian header and the serialized logs. The low 31 bits hold the length; the high bit marks the final message. The parent keeps a per-child receive buffer (`_receiveBuffers`), so a message split across reads, including its header, completes on a later read. End of stream on a connection closes it as final.

### Signals and closed connections
`stream_select()` and `fwrite()` are called with `@`, because Prado's error handler turns their warnings into exceptions. A signal (e.g. SIGCHLD) interrupts `stream_select()` with EINTR; both loops retry it as a timeout. When the parent has closed its end, the child's `fwrite()` fails with EPIPE; `sendLogsToParent()` drops the logs and releases `_childConnection`.

### Requirements
Requires `pcntl` to fork and the `sockets` extension to capture. Without `socket_create_pair()`, `generateConnection()` creates no connection and nothing is captured (Windows CI has no `sockets`).

## Key Properties/Methods

- `events()` - Returns `[TProcessHelper::FX_PREPARE_FOR_FORK => 'generateConnection', TProcessHelper::FX_RESTORE_AFTER_FORK => 'configureForChildLogs']`
- `getPriority()` - Returns -10 (default priority for capture)
- `dyAttachBehavior($name, $behavior, [TCallChain](../TCallChain.md) $chain)` - Singleton enforcement for this behavior
- `generateConnection(mixed $sender, mixed $param)` - Creates socket pair before fork
- `configureForChildLogs(mixed $sender, mixed $data)` - Configures parent/child for log routing after fork
- `collectLogsFromChildren(mixed $sender, mixed $final)` - `TLogger::onCollectLogs` handler; calls `receiveLogsFromChildren(null, (bool) $final)`
- `receiveLogsFromChildren(?int $pid, bool $wait)` - Receives and merges logs from all children, or only `$pid`; not an event handler
- `extractChildLogs(int $childPid, array &$completeLogs)` - Moves complete messages out of a child's receive buffer; returns whether the final message arrived
- `sendLogsToParent($logger, $final)` - Child `onFlushLogs` handler; sends unlogged logs to the parent, closing the connection when final

## See Also

- [TForkable](./TForkable.md)
- [TProcessHelper](../Helpers/TProcessHelper.md)
- [TApplicationSignals](./TApplicationSignals.md)
