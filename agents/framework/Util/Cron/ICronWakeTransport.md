# Util/Cron/ICronWakeTransport

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`ICronWakeTransport`**

## Interface Info
**Location:** `framework/Util/Cron/ICronWakeTransport.php`
**Namespace:** `Prado\Util\Cron`
**Since:** 4.4.0

## Overview
`ICronWakeTransport` carries a wake from a process that schedules a task to the cron process waiting for its next trigger, so the task runs on time instead of after the remaining wait. A wake carries no data; the cron process re-polls its pending tasks. [`TCronModule`](TCronModule.md) holds one transport as `WakeTransport` and uses it in three places: `signalWake()` calls `signal()`, the per-minute `waitForWake()` calls `wait()`, and the daemon calls `watch()`/`unwatch()`.

## Methods

| Method | Description |
|---|---|
| `signal(float $now): bool` | Sends a wake. A missing listener is a harmless no-op; false when nothing could be sent. |
| `wait(float $until, float $now): ?bool` | Blocks until a wake or `$until`: true woken, false timed out, null when the transport is unavailable so the caller sleeps plainly. `$now` is the module clock. |
| `watch(IEventLoop $loop, callable $onWake): bool` | Registers loop callbacks that call `$onWake()` on each wake; false when unavailable. |
| `unwatch(): void` | Cancels the loop callbacks. |
| `close(): void` | Releases the transport's resources (a bound socket, a watcher). |

## Implementations

| Class | Reach | Latency | Module properties |
|---|---|---|---|
| [`TCronWakeSocket`](TCronWakeSocket.md) | processes of one host (`udg`, else `udp` loopback) | immediate | `RuntimePath`, `BasePort` |
| [`TCronWakeFile`](TCronWakeFile.md) | hosts sharing a file system | `PollInterval` | `WakeFile`, `PollInterval` |
| [`TCronWakeCache`](TCronWakeCache.md) | clients of a cache (memcached, Redis) | `PollInterval` | `CacheID`, `CacheKey`, `PollInterval` |
| [`TCronWakeSignal`](TCronWakeSignal.md) | processes of one host (`pcntl`, `posix`) | immediate (daemon); `WAIT_SLICE` 50 ms (per-minute) | `PidFile`, `Signal` |
| [`TCronWakeFifo`](TCronWakeFifo.md) | processes of one host (named pipe, `posix`) | immediate | `FifoFile` |
| [`TCronWakePgsql`](TCronWakePgsql.md) | hosts sharing a PostgreSQL database (`LISTEN`/`NOTIFY`) | immediate (per-minute); `PollInterval` (daemon) | `ConnectionID`, `Channel`, `PollInterval` |

The transports are `TModule`s, so an application configures one and names its ID in `TCronModule.WakeTransport`. Without configuration the module creates a `TCronWakeSocket` in the runtime directory. The socket, signal, and FIFO transports reach one host; the file, cache, and PostgreSQL transports reach every host sharing their medium.

## See Also

- [TCronModule](TCronModule.md)
- [TCronWakePoll](TCronWakePoll.md)
