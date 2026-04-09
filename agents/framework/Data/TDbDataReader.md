# TDbDataReader

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [TDbDataReader](./TDbDataReader.md)

**Location:** `framework/Data/TDbDataReader.php`
**Namespace:** `Prado\Data`

## Overview

`TDbDataReader` represents a forward-only stream of rows from a query result set. It implements `Iterator` and `Countable`.

## Key Methods

```php
$reader->read();      // Read single row as array
$reader->readAll();   // Read all rows as array
$reader->nextResult(); // Get next result set (for stored procedures)

// Iterator methods
foreach($reader as $row) { /* ... */ }
```

## Properties

- `RowCount` - Number of rows affected (for INSERT/UPDATE/DELETE)
- `FetchMode` - PDO fetch mode (default: `PDO::FETCH_ASSOC`)

## Usage

```php
$command = $connection->createCommand('SELECT * FROM users');
$reader = $command->query();

foreach($reader as $row) {
    echo $row['username'];
}
```

## See Also

- [TDbCommand](./TDbCommand.md) - SQL command execution
- [TDbConnection](./TDbConnection.md) - Database connection