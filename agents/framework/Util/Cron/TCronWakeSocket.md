# Util/Cron/TCronWakeSocket

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TCronWakeSocket`**

## Class Info
**Location:** `framework/Util/Cron/TCronWakeSocket.php`
**Namespace:** `Prado\Util\Cron`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakeSocket` is the same-host wake transport and the default `WakeTransport` of [`TCronModule`](TCronModule.md). It is a connectionless datagram socket: a unix-domain datagram (`udg`) when PHP has it, otherwise a `udp` loopback socket (`getTransport()`), and "no transport" when neither exists.

Addresses:

| Listener | Address | `udp` port |
|---|---|---|
| per-minute process, even minute | `<RuntimePath>/cronwake-even.sock` | `BasePort` |
| per-minute process, odd minute | `<RuntimePath>/cronwake-odd.sock` | `BasePort + 1` |
| daemon | `<RuntimePath>/cronwake-daemon.sock` | `BasePort + 2` |

The minute **parity** keeps a process that overruns into the next minute from contending with the next minute's process. `signal($now)` sends to the daemon address and the current minute's parity address, so either kind of listener wakes. A bound socket stays bound across waits, so a datagram sent while the process is busy is buffered and consumed by the next `wait()`; `close()` releases it (a per-minute process closes at the end of its minute). `watch()` binds the daemon address and registers an `onReadable` loop callback that drains the socket and calls back.

## Properties

| Property | Default | Description |
|---|---|---|
| `RuntimePath` | application runtime path | Directory of the `udg` socket files; a path or namespace. |
| `BasePort` | `52800` | Base `udp` loopback port. |

## Methods

- `getTransport(): ?string`, `getAddress(int $minute): ?string`, `getDaemonAddress(): ?string`
- `signal(float $now): bool`, `wait(float $until, float $now): ?bool`, `watch(IEventLoop $loop, callable $onWake): bool`, `unwatch(): void`, `close(): void`
- Protected seams: `bind($address)`, `release($server, $address)`, `send($address)`, `drain()`, `ensureBound($address)`

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronModule](TCronModule.md)
