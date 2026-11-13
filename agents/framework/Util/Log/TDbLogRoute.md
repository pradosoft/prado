# Util/Log/TDbLogRoute

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TDbLogRoute`**

## Class Info
**Location:** `framework/Util/Log/TDbLogRoute.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Uses trait:** `TDbPropertiesTrait`

## Overview
`TDbLogRoute` persists log entries to a relational database table via PDO. It auto-creates the log table if it does not exist (supported for MySQL, PostgreSQL, and SQLite), provides query and delete helpers for retrieving stored log data, and can purge rows older than a configurable retention period.

## Table Schema

`log_id` (PK) · `level INTEGER` · `category VARCHAR(128)` · `prefix VARCHAR(128)` · `logtime VARCHAR(20)` · `message VARCHAR(255)`

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `LogTableName` | `string` | `'pradolog'` | Database table that receives log rows. Must be an SQL identifier (4.4.0). |
| `AutoCreateLogTable` | `bool` | `true` | Creates the table automatically on `init()` if it does not exist. |
| `RetainPeriod` | `?float` | `null` | Seconds to retain; older rows are purged on each flush. Accepts ISO 8601 duration strings (e.g. `'PT1H'`). |
| `PruneOnFlush` | `bool` | `true` | Whether each flush deletes the rows older than `RetainPeriod`; false leaves it to the cron task (4.4.0). |
| `ConnectionID` | `string` | — | ID of a `TDataSourceConfig` module; defaults to an in-app SQLite3 `sqlite3.log` file. |

## Key Methods

| Method | Description |
|--------|-------------|
| `getDBLogCount(?int $level, ..., ?float $minTime, ?float $maxTime): int` | Returns the count of stored log rows matching the given criteria. |
| `getDBLogs(...): TDbDataReader` | Returns a result set of log rows matching criteria. `$order` lists columns with optional ASC/DESC; `$limit` is `count`, `count OFFSET offset`, or `offset, count`; other values throw `TInvalidDataValueException` (4.4.0). |
| `deleteDBLog(...): int` | Deletes matching log rows and returns the removed count. |
| `pruneLogs(?float $retainPeriod = null): int` | Deletes the rows older than the period, default `RetainPeriod`; 0 without a positive period (4.4.0). |
| `static timespanToSeconds(string $timespan): ?int` | Converts an ISO 8601 duration string to seconds. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\Log\TLogRouter">
    <route class="Prado\Util\Log\TDbLogRoute" ConnectionID="db" LogTableName="prado_log" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\Log\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\Log\TDbLogRoute', 'properties' => ['ConnectionID' => 'db', 'LogTableName' => 'prado_log']],
            ],
        ],
    ],
];
```

## Cron Pruning

With `RetainPeriod` set, every flush runs a DELETE. On a busy site set `PruneOnFlush="false"` and schedule the `TLogRouter` cron task `dblogprune` (`'<routerId>->pruneDbLogs()'`), which calls `pruneLogs()` on each `TDbLogRoute` of the router.

```xml
<module id="log" class="Prado\Util\Log\TLogRouter">
  <route class="Prado\Util\Log\TDbLogRoute" ConnectionID="db" RetainPeriod="P30D" PruneOnFlush="false" />
</module>
<module id="cron" class="Prado\Util\Cron\TCronModule">
  <job Name="dblogprune" Schedule="0 3 * * *" Task="log->pruneDbLogs()" />
</module>
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
