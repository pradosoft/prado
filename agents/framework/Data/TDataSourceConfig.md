# Data/TDataSourceConfig

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDataSourceConfig`**

## Class Info
**Location:** `framework/Data/TDataSourceConfig.php`
**Namespace:** `Prado\Data`

## Overview
`TDataSourceConfig` is a module class that provides configuration for database connections in application.xml. It uses [`TDbPropertiesTrait`](./TDbPropertiesTrait.md) to manage the underlying connection.

## Configuration

```xml
<modules>
    <module id="db1">
        <database ConnectionString="mysql:host=localhost;dbname=test"
            Username="dbuser" Password="dbpass" />
    </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'db' => [
            'class' => 'Prado\Data\TDataSourceConfig',
            'properties' => [
                'ConnectionString' => 'mysql:host=localhost;dbname=mydb',
                'Username' => 'dbuser',
                'Password' => 'secret',
            ],
        ],
    ],
];
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

- [TDbPropertiesTrait](./TDbPropertiesTrait.md) - Connection management trait used by this class
- [TDbConnection](./TDbConnection.md) - Database connection class