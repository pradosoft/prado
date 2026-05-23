# Util/TDbLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TDbLogRoute`**

## Class Info
**Location:** `framework/Util/TDbLogRoute.php`
**Namespace:** `Prado\Util`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Uses trait:** `TDbPropertiesTrait`

## Overview
`TDbLogRoute` persists log entries to a relational database table via PDO. It auto-creates the log table if it does not exist (supported for MySQL, PostgreSQL, and SQLite), provides query and delete helpers for retrieving stored log data, and can purge rows older than a configurable retention period.

## Table Schema

`log_id` (PK) · `level INTEGER` · `category VARCHAR(128)` · `prefix VARCHAR(128)` · `logtime VARCHAR(20)` · `message VARCHAR(255)`

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `LogTableName` | `string` | `'pradolog'` | Database table that receives log rows. |
| `AutoCreateLogTable` | `bool` | `true` | Creates the table automatically on `init()` if it does not exist. |
| `RetainPeriod` | `?float` | `null` | Seconds to retain; older rows are purged on each flush. Accepts ISO 8601 duration strings (e.g. `'PT1H'`). |
| `ConnectionID` | `string` | — | ID of a `TDataSourceConfig` module; defaults to an in-app SQLite3 `sqlite3.log` file. |

## Key Methods

| Method | Description |
|--------|-------------|
| `getDBLogCount(?int $level, ..., ?float $minTime, ?float $maxTime): int` | Returns the count of stored log rows matching the given criteria. |
| `getDBLogs(...): TDbDataReader` | Returns a result set of log rows matching criteria. |
| `deleteDBLog(...): int` | Deletes matching log rows and returns the removed count. |
| `static timespanToSeconds(string $timespan): ?int` | Converts an ISO 8601 duration string to seconds. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TDbLogRoute" ConnectionID="db" LogTableName="prado_log" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\TDbLogRoute', 'properties' => ['ConnectionID' => 'db', 'LogTableName' => 'prado_log']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
