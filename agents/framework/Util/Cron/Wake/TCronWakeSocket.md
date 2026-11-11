# Util/Cron/TCronWakeSocket

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakeSocket`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakeSocket.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakeSocket` is the same-host wake transport and the default `WakeTransport` of [`TCronModule`](../TCronModule.md). It is a connectionless datagram socket: a unix-domain datagram (`udg`) when PHP has it, otherwise a `udp` loopback socket (`getTransport()`), and "no transport" when neither exists.

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
| `Key` | the key in `cronwake.key` | Signs the wake messages. By default the key is read from `KEY_FILE` (`cronwake.key`) in the `RuntimePath`, which every process of the application shares (`readKeyFile()`): the first process to need it creates the file, owner-only, with a random 64-hex-digit key through an exclusive create, and the others read it under a shared lock. A file left empty by a crashed author is rewritten after `KEY_FILE_ATTEMPTS` reads. When the file cannot be written, every process uses an HMAC of the file path. A key derived from the `TSecurityManager` validation key would not be shared: a fresh application generates that key on first use and saves it to global state only at the end of the process, which a daemon never reaches. |

## Wake message

`createToken()` builds `time:nonce:signature`, a fresh 8-byte random nonce and the current time signed with the `Key`. `verifyToken()` accepts a message whose signature matches, whose time is within `TOKEN_WINDOW` (30 s) of now, and whose nonce was not accepted before (nonces are remembered until they expire), so a captured datagram cannot be replayed and a datagram from another local process is read and ignored. The key file and the `udg` socket file are owner-only, so the web server and cron run as one user.

## Methods

- `getTransport(): ?string`, `getAddress(int $minute): ?string`, `getDaemonAddress(): ?string`
- `signal(float $now): bool`, `wait(float $until, float $now): ?bool`, `watch(IEventLoop $loop, callable $onWake): bool`, `unwatch(): void`, `close(): void`
- `getKey()`, `createToken(): string`, `verifyToken(string $data): bool`
- Protected seams: `bind($address)`, `release($server, $address)`, `send($address)`, `drain()`, `ensureBound($address)`

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronModule](../TCronModule.md)
