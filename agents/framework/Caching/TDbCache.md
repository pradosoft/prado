# TDbCache

### Directories
[./](../INDEX.md) > [Caching](./INDEX.md) > [TDbCache](./TDbCache.md)

**Location:** `framework/Caching/TDbCache.php`
**Namespace:** `Prado\Caching`

## Overview

[TDbCache](./TDbCache.md) stores cached data in a database using PDO. By default uses SQLite for zero-configuration caching.

## Configuration

```xml
<!-- SQLite (default - runtime directory) -->
<module id="cache" class="Prado\Caching\TDbCache" />

<!-- MySQL with existing connection -->
<module id="cache" class="Prado\Caching\TDbCache" ConnectionID="db" />

<!-- Custom connection -->
<module id="cache" class="Prado\Caching\TDbCache" 
        ConnectionString="mysql:host=localhost;dbname=app" 
        Username="user" Password="pass" />
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

## See Also

- [TCache](./TCache.md) for full caching documentation