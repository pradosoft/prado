# Util/Cron/TCronWakeRedis

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakeRedis`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakeRedis.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md)
**Since:** 4.4.0

## Overview
`TCronWakeRedis` wakes the cron process through Redis Pub/Sub: `signal()` opens a connection, runs `PUBLISH <Channel> <time>`, and closes it; the waiting process holds a socket of its own that ran `SUBSCRIBE <Channel>` and receives the messages. Every client of the server receives the wake, with no shared file system, cache, or database. The transport speaks RESP on a stream socket itself (`encode()`, `decode()`), so it needs no `redis` extension, and a `PUBLISH` from any client, such as `redis-cli`, wakes it.

| Side | Mechanism |
|---|---|
| per-minute `wait()` | `drain()` takes the messages already received, else `stream_select()` on the subscribed socket for the remaining time |
| daemon `watch()` | `onReadable()` of the subscribed socket, so the daemon wakes at once; a lost connection is reopened, resubscribed, and re-registered |
| `close()` | cancels the watcher and closes the subscribed socket |

`wait()` is null, `signal()` false, and `watch()` false when the server is unreachable or rejects the credentials, so the cron module falls back to a plain sleep or the `DaemonIdle` poll. A message received between waits is consumed by the next wait; a subscription lost while nothing waits loses the messages published in between, as Pub/Sub does not queue, and the next wait resubscribes.

```xml
<module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeRedis" Host="redis" Port="6379" Password="secret" />
<module id="cron" class="Prado\Util\Cron\TDbCronManager" WakeTransport="cronwake" Daemon="true" />
```

## Properties

| Property | Default | Description |
|---|---|---|
| `Host` | `localhost` | The host name of the server. |
| `Port` | `6379` | The port of the server. |
| `Socket` | null | The Unix socket of the server; set, it replaces `Host` and `Port`. |
| `Username` | null | The ACL user name sent with `Password` (`AUTH user password`). |
| `Password` | null | The password; null authenticates nothing. |
| `Channel` | `prado:cron:wake` | The channel, without whitespace (`cronwakeredis_channel_invalid`). |
| `Timeout` | `2.0` | Seconds to wait for a connection and for a reply (`cronwakeredis_timeout_invalid`). |

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronWakeCache](TCronWakeCache.md) — polls a stamp in a cache, Redis included, when the cache is shared anyway
- [TRedisMutex](../../Mutex/TRedisMutex.md)
