# Util/Behaviors/TCaptureForkLog

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TCaptureForkLog`**

## Class Info
**Location:** `framework/Util/Behaviors/TCaptureForkLog.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
This behavior captures logs from forked child processes and sends them back to the parent process. When `[TProcessHelper](../Helpers/TProcessHelper.md)::fork()` is called, this behavior creates socket pairs before the fork and routes child process logs to the parent. The parent receives logs when processing its own logs via `onCollectLogs`.

## Key Properties/Methods

- `events()` - Returns `[TProcessHelper::FX_PREPARE_FOR_FORK => 'generateConnection', TProcessHelper::FX_RESTORE_AFTER_FORK => 'configureForChildLogs']`
- `getPriority()` - Returns -10 (default priority for capture)
- `dyAttachBehavior($name, $behavior, [TCallChain](../TCallChain.md) $chain)` - Singleton enforcement for this behavior
- `generateConnection(mixed $sender, mixed $param)` - Creates socket pair before fork
- `configureForChildLogs(mixed $sender, array $data)` - Configures parent/child for log routing after fork
- `receiveLogsFromChildren(?int $pid, bool $wait)` - Receives and merges logs from child processes
- `sendLogsToParent($logger, $final)` - Sends logs from child to parent process

## See Also

- [TForkable](./TForkable.md)
- [TProcessHelper](../Helpers/TProcessHelper.md)
- [TApplicationSignals](./TApplicationSignals.md)
