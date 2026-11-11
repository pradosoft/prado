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

`Tls` connects with `tls://` and an `ssl` stream context (`getStreamContextOptions()`): the server certificate is verified against the system CA bundle or `CaFile` and must carry `PeerName` (default: the host); `LocalCert` and `LocalKey` present a client certificate; `VerifyPeer` false accepts any certificate. A Unix socket is never TLS. The subscribed TLS socket works on the loop like a plain one; the transport is unavailable without the `openssl` extension when `Tls` is set.

`wait()` is null, `signal()` false, and `watch()` false when the server is unreachable, rejects the credentials, or fails the certificate check, so the cron module falls back to a plain sleep or the `DaemonIdle` poll. A message received between waits is consumed by the next wait; a subscription lost while nothing waits loses the messages published in between, as Pub/Sub does not queue, and the next wait resubscribes.

```xml
<module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeRedis" Host="redis" Port="6379" Password="secret" Tls="true" CaFile="Application.config.redis-ca.pem" />
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
| `Tls` | `false` | Connect over TLS. |
| `CaFile` | null | PEM file (path or dot namespace) of the CA that signed the server certificate; null uses the system bundle. |
| `VerifyPeer` | `true` | Verify the server certificate and its name; false accepts any certificate. |
| `PeerName` | null | The name the server certificate must carry; null uses `Host`. |
| `LocalCert` | null | PEM file of the client certificate, for a server that authenticates clients. |
| `LocalKey` | null | PEM file of the client private key; null when it is in `LocalCert`. |

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronWakeCache](TCronWakeCache.md) — polls a stamp in a cache, Redis included, when the cache is shared anyway
- [TRedisMutex](../../Mutex/TRedisMutex.md)
