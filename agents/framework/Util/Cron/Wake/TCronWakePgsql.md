# Util/Cron/TCronWakePgsql

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakePgsql`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakePgsql.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md), [`IDbModule`](../../IDbModule.md)
**Since:** 4.4.0

## Overview
`TCronWakePgsql` wakes the cron process through PostgreSQL `LISTEN`/`NOTIFY`: `signal()` runs `NOTIFY <Channel>` on the `ConnectionID` connection (or one set with `setDbConnection()`), and the waiting process holds a connection of its own, opened with the same credentials, that runs `LISTEN <Channel>` and receives the notifications through `PDO::pgsqlGetNotify()`. Every host sharing the database receives the wake, with no shared file system or cache. The listening connection is separate because a `LISTEN` belongs to its session and because the cron daemon closes the application connection while it waits.

| Side | Mechanism |
|---|---|
| per-minute `wait()` | blocks in `pgsqlGetNotify()` for the remaining milliseconds; notifications queued since the last wait are consumed at once |
| daemon `watch()` | a `repeat(PollInterval)` loop callback that takes the queued notifications |
| `close()` | cancels the watcher and closes the listening connection, which ends its `LISTEN` |

```xml
<module id="cronwake" class="Prado\Util\Cron\TCronWakePgsql" ConnectionID="db" PollInterval="0.25" />
<module id="cron" class="Prado\Util\Cron\TDbCronManager" ConnectionID="db" WakeTransport="cronwake" Daemon="true" />
```

## Properties

| Property | Default | Description |
|---|---|---|
| `ConnectionID` | — | ID of the `TDataSourceConfig` module; its driver must be PostgreSQL (`cronwakepgsql_driver_invalid`). |
| `Channel` | `prado_cron_wake` | The notification channel, an SQL identifier (`dbproperties_identifier_invalid`). |
| `PollInterval` | `1.0` | Seconds between polls of the notifications in `watch()`. |

The transport is unavailable without the `pdo_pgsql` extension.

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TDbCronManager](../TDbCronManager.md)
