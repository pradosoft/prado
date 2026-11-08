# Util/Cron/Wake/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / **`Wake`**

## Purpose

The wake transports of [`TCronModule`](../TCronModule.md) (`Prado\Util\Cron\Wake`): a process that schedules a task sends a wake to the cron process waiting for its next trigger, so the task runs on time. [`ICronWakeTransport`](ICronWakeTransport.md) is the seam (`signal`, `wait`, `watch`, `unwatch`, `close`); the module holds one transport as `WakeTransport`, by module ID, and uses it for per-minute waits (`waitForWake()`), the daemon (`watch()`), and `signalWake()`. A wake carries no data; the cron process re-polls its pending tasks.

## Transports

| Class | Reach | Mechanism | Latency |
|---|---|---|---|
| [`TCronWakeSocket`](TCronWakeSocket.md) | one host | datagram socket (`udg`, else `udp` loopback); minute-parity and daemon addresses; the default | immediate |
| [`TCronWakeSignal`](TCronWakeSignal.md) | one host | `SIGUSR1` to the pid in the pid file (`pcntl`, `posix`) | immediate (daemon); 50 ms slices (per-minute) |
| [`TCronWakeFifo`](TCronWakeFifo.md) | one host | named pipe held open `r+` (`posix`) | immediate |
| [`TCronWakeFile`](TCronWakeFile.md) | hosts sharing a file system | microtime stamp in a file, polled | `PollInterval` |
| [`TCronWakeCache`](TCronWakeCache.md) | clients of a cache | microtime stamp under a cache key, polled | `PollInterval` |
| [`TCronWakePgsql`](TCronWakePgsql.md) | hosts sharing a PostgreSQL database | `NOTIFY` on the app connection, `LISTEN` on a connection of its own | immediate (per-minute); `PollInterval` (daemon) |

[`TCronWakePoll`](TCronWakePoll.md) is the abstract base of the file and cache transports: `readStamp()`/`writeStamp()`/`getIsAvailable()` behind a `PollInterval` wait and a `repeat` watcher.

## Patterns & Gotchas

- **Buffered wakes.** Every transport keeps a wake that arrives while the waiting process is busy (a bound socket, a held pipe, a pending signal, a stamp, a queued notification), so the next `wait()` returns at once.
- **No listener is a no-op.** `signal()` to a host with no waiting process returns without effect; the per-minute sweep or `DaemonIdle` poll catches the task.
- **`wait()` blocks.** The socket, FIFO, and PostgreSQL waits block the process in `stream_select()`/`pgsqlGetNotify()`; the file, cache, and signal waits sleep through `TAsync::sleep()`, so the event loop runs between slices.
- **Authentication.** Any local process can send to a loopback port or a `udg` path, so a `TCronWakeSocket` datagram is a signed message (a fresh nonce and the time, an HMAC with a `Key` derived from the application's validation key), accepted once within a 30 s window; foreign or replayed datagrams are ignored. The signal, FIFO, and file transports rely on the kernel's permissions (same user, owner-only files); the cache and PostgreSQL transports on the credentials of their medium.
- **Defaults.** The `DEFAULT_*` constants are read through `static::` in `init()` and the getters, so a subclass overrides a default by redefining the constant.
- **Availability.** An unavailable medium (no extension, no directory, no cache) makes `signal()` false, `wait()` null, and `watch()` false, and the module falls back to a plain sleep or the `DaemonIdle` poll.

## See Also

- [TCronModule](../TCronModule.md) — `WakeTransport`, `signalWake()`, daemon mode
- [TDbCronManager](../TDbCronManager.md) — `signalWakeIfEarliest()`
