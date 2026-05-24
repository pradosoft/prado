# Caching/TDbCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TDbCache`**

## Class Info
**Location:** `framework/Caching/TDbCache.php`
**Namespace:** `Prado\Caching`

## Overview
[TDbCache](./TDbCache.md) stores cached data in a database using PDO. By default uses SQLite for zero-configuration caching.

## Configuration

```xml
<modules>
    <!-- SQLite (default - runtime directory) -->
    <module id="cache" class="Prado\Caching\TDbCache" />

    <!-- MySQL with existing connection -->
    <module id="cache" class="Prado\Caching\TDbCache" ConnectionID="db" />

    <!-- Custom connection -->
    <module id="cache" class="Prado\Caching\TDbCache" 
            ConnectionString="mysql:host=localhost;dbname=app" 
            Username="user" Password="pass" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'cache' => [
            'class' => 'Prado\Caching\TDbCache',
            'properties' => ['ConnectionID' => 'db', 'CacheTableName' => 'pradocache'],
        ],
    ],
];
```

## Properties

- `ConnectionID` - ID of TDataSourceConfig module
- `ConnectionString` - PDO DSN
- `Username` / `Password` - Database credentials
- `CacheTableName` - Table name (default: `pradocache`)
- `AutoCreateCacheTable` - Auto-create table (default: true)
- `FlushInterval` - Seconds between expired item cleanup (default: 60, 0=manual)

## Database Schema

```sql
CREATE TABLE pradocache (
    itemkey CHAR(128) PRIMARY KEY,
    value BLOB,
    expire INTEGER
);
CREATE INDEX IX_expire ON pradocache (expire);
```

Note: auto-create uses `LONGBLOB` for MySQL, `BYTEA` for PostgreSQL, and `BLOB` for all other drivers. Indices are non-unique.

## Expiry Boundary Condition

`getValue()` uses `expire >= time()` — the exact moment of expiry is still considered valid (not expired). Items are treated as expired only when `expire < time()`. Similarly, `flushCacheExpired()` deletes rows where `expire < now` (not `<=`), so a row expiring at precisely the current second survives the flush pass.

## Cron Integration

`TDbCache` implements `fxGetCronTaskInfos()`, registering a cron task (`dbcacheflushexpired`) that calls `flushCacheExpired(true)` on demand. This allows scheduled expired-key cleanup instead of relying solely on the automatic `FlushInterval`.

## Subclass Extension Points (@since 4.3.3)

These protected methods are designed for overriding in subclasses:

| Method | Purpose |
|--------|---------|
| `getDbConnectionActivationType()` | Returns `true` — auto-activates the connection on every `getDbConnection()` call |
| `getCustomDbConnection()` | Builds a `TDbConnection` from `ConnectionString`/`Username`/`Password` when set; returns `null` if not configured |
| `getSqliteDatabaseName()` | Returns `'sqlite3.cache'` — the SQLite file name within the runtime path; override to use a different filename |

## See Also

- [TCache](./TCache.md) for full caching documentation