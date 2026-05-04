# Data/ActiveRecord/TActiveRecordManager

### Directories
[framework](../../INDEX.md) / [Data](../INDEX.md) / [ActiveRecord](./INDEX.md) / **`TActiveRecordManager`**

## Class Info
**Location:** `framework/Data/ActiveRecord/TActiveRecordManager.php`
**Namespace:** `Prado\Data\ActiveRecord`

## Overview
`TActiveRecordManager` is a singleton that manages the default database connection and table metadata for all Active Record classes.

## Configuration

```php
// Set connection programmatically
TActiveRecordManager::getInstance()->setDbConnection($conn);

// Enable metadata caching
TActiveRecordManager::getInstance()->setCache($cache);
```

## Properties

- `DbConnection` - The default [`TDbConnection`](../TDbConnection.md)
- `Cache` - ICache implementation for table metadata caching

## Usage

```php
$conn = new TDbConnection($dsn, $user, $pass);
TActiveRecordManager::getInstance()->setDbConnection($conn);

// Now AR classes can use the connection
$user = UserRecord::finder()->findByPk(1);
```

## See Also

- [TActiveRecord](./TActiveRecord.md) - Base Active Record class
- [TActiveRecordConfig](./TActiveRecordConfig.md) - Configuration module