# TDataSourceConfig

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [TDataSourceConfig](./TDataSourceConfig.md)

**Location:** `framework/Data/TDataSourceConfig.php`
**Namespace:** `Prado\Data`

## Overview

`TDataSourceConfig` is a module class that provides configuration for database connections in application.xml.

## Configuration

```xml
<modules>
    <module id="db1">
        <database ConnectionString="mysql:host=localhost;dbname=test"
            Username="dbuser" Password="dbpass" />
    </module>
</modules>
```

## Usage

```php
$db = $this->Application->Modules['db1']->DbConnection;
$db->createCommand('SELECT * FROM users');
```

## Properties

- `ConnectionClass` - Custom database connection class extending [`TDbConnection`](./TDbConnection.md)
- `DbConnection` - The configured [`TDbConnection`](./TDbConnection.md) instance

## See Also

- [TDbConnection](./TDbConnection.md) - Database connection class