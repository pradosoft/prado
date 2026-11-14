# Util/Cron/TCronWakeOracle

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakeOracle`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakeOracle.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** `TModule`
**Implements:** [`ICronWakeTransport`](ICronWakeTransport.md), [`IDbModule`](../../IDbModule.md)
**Since:** 4.4.0

## Overview
`TCronWakeOracle` wakes the cron process through Oracle `DBMS_ALERT`: `signal()` runs `DBMS_ALERT.SIGNAL(<Alert>, <time>)` and `COMMIT` on a connection of the transport's own, and the waiting process holds a second connection of its own, registered with `DBMS_ALERT.REGISTER`, that blocks in `DBMS_ALERT.WAITONE`. Both are opened with the credentials of the `ConnectionID` connection (or one set with `setDbConnection()`), through `newConnection()`. Every host sharing the database receives the wake, with no shared file system or cache. The user needs `EXECUTE` on `DBMS_ALERT`.

A signal is delivered when its transaction commits, so signaling on the application's connection would commit the application's transaction; the signaling connection of its own avoids that. Several signals before a wait coalesce into one. `WAITONE` takes whole seconds, so `wait()` rounds the remaining time up.

| Side | Mechanism |
|---|---|
| per-minute `wait()` | `WAITONE` on the listening connection with the remaining whole seconds; status 0 is a signal, 1 a timeout |
| daemon `watch()` | a `repeat(PollInterval)` loop callback running `WAITONE` with a zero timeout |
| `close()` | cancels the watcher, runs `DBMS_ALERT.REMOVE`, and closes both connections |

```xml
<module id="cronwake" class="Prado\Util\Cron\Wake\TCronWakeOracle" ConnectionID="db" PollInterval="0.25" />
<module id="cron" class="Prado\Util\Cron\TDbCronManager" ConnectionID="db" WakeTransport="cronwake" Daemon="true" />
```

## Properties

| Property | Default | Description |
|---|---|---|
| `ConnectionID` | — | ID of the `TDataSourceConfig` module; its driver must be Oracle (`cronwakeoracle_driver_invalid`). |
| `Alert` | `PRADO_CRON_WAKE` | The alert name, an SQL identifier of at most 30 characters, case-insensitive (`dbproperties_identifier_invalid`, `cronwakeoracle_alert_invalid`). |
| `PollInterval` | `1.0` | Seconds between polls of the alert in `watch()`. |

The transport is unavailable without the `pdo_oci` extension. The unit test scripts the `DBMS_ALERT` blocks over `TTestLockConnection`; the live round trip is `TCronWakeOracleIntegrationTest` in the `db-oracle` suite.

## See Also

- [ICronWakeTransport](ICronWakeTransport.md)
- [TCronWakePgsql](TCronWakePgsql.md)
- [TDbCronManager](../TDbCronManager.md)
