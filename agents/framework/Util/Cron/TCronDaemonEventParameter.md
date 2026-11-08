# Util/Cron/TCronDaemonEventParameter

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronDaemonEventParameter`**

## Class Info
**Location:** `framework/Util/Cron/TCronDaemonEventParameter.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TEventParameter`
**Since:** 4.4.0

## Overview
The parameter of the [`TCronModule`](TCronModule.md) daemon events `onDaemonStart`, `onDaemonIdle`, and `onDaemonEnd`.

| Property | Set for | Meaning |
|---|---|---|
| `LockName` | all | the lease lock name (`cron:daemon:<module id>`) |
| `Watching` | all | whether the wake transport is watched on the loop |
| `Tasks` | idle, end | tasks run in the sweep just finished (idle) or in the daemon's life (end) |
| `Elapsed` | idle, end | seconds since the daemon started (monotonic) |
| `Wait` | idle | seconds the daemon is about to wait; a handler shortens or lengthens it with `setWait()`, floored at `DAEMON_MIN_WAIT` |
| `Reason` | end | `REASON_HANDOFF` (the lease went to the next tick), `REASON_STOP`, `REASON_SIGNAL`, `REASON_ERROR`, or `REASON_LIFETIME` |

```php
$cron->attachEventHandler('OnDaemonIdle', function ($cron, TCronDaemonEventParameter $param) {
    $this->redis->close();                    // release while idle
    if (file_exists($this->deployMarker)) {
        $cron->stopDaemon();                  // the next tick elects a daemon on the new code
    }
});
```

## See Also

- [TCronModule](TCronModule.md#daemon-mode-440)
