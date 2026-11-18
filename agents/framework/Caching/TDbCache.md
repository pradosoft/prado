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
- `CacheTableName` - Table name (default: `pradocache`); must be an SQL identifier (4.4.0)
- `AutoCreateCacheTable` - Auto-create table (default: true)
- `FlushInterval` - Seconds between expired item cleanup (default: 60, 0=manual)

## Database Schema

```sql
CREATE TABLE pradocache (
    itemkey CHAR(128) NOT NULL PRIMARY KEY,
    value BLOB,
    expire INTEGER
);
CREATE INDEX IX_pradocache_expire ON pradocache (expire);
```

Note: auto-create takes its column types from `getColumnTypes()`: the value is `LONGBLOB` on MySQL, `BYTEA` on PostgreSQL, `VARBINARY(MAX)` on SQL Server, `BLOB(1G)` on IBM DB2, and `BLOB` elsewhere; the key is `VARCHAR2(128)` on Oracle and `VARCHAR(128)` on IBM DB2, where a padded `CHAR` key would not match, and `CHAR(128)` elsewhere. `set()` runs the statement of `getUpsertSql()`: `REPLACE INTO` on MySQL and SQLite, `ON CONFLICT` on PostgreSQL, and `MERGE` on SQL Server, IBM DB2, and Oracle; other drivers delete and insert in a transaction. `getValueSql()` gives the value expression: SQL Server binds hex and decodes it with `CONVERT(VARBINARY(MAX), :value, 2)`, since pdo_sqlsrv binds a string as `nvarchar`; DB2 casts the parameter to `BLOB(1G)`. `TOracleDbCommand` writes bound values into the SQL as quoted strings, which Oracle reads as hex for a `BLOB`, so on Oracle `getOracleValueBlock()` runs the statement in a PL/SQL block that builds the value in a temporary `BLOB` from `HEXTORAW` chunks of `ORACLE_CHUNK_SIZE` bytes. pdo_ibm fetches a `BLOB` of about 64 KiB or more as `NULL` (65,000 bytes reads back in CI; 65,536 bytes does not); `getSerializedValue()` reads `NULL` as a miss. The driver tests are `TDbCacheDriverTestTrait` in the `DbSpecific` suites. The `expire` index is named after the table (`getExpireIndexName()`), since SQLite and PostgreSQL scope index names to the schema. `itemkey` is the primary key, which the upsert of `set()` needs.

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