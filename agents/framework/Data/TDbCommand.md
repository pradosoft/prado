# Data/TDbCommand

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbCommand`**

## Class Info
**Location:** `framework/Data/TDbCommand.php`
**Namespace:** `Prado\Data`

## Overview
`TDbCommand` represents an SQL statement to execute against a database. It is created by calling [`TDbConnection::createCommand()`](./TDbConnection.md).

## Key Methods

```php
// Execute non-query SQL (INSERT, UPDATE, DELETE)
$command->execute();

// Query methods
$reader = $command->query();           // Returns [`TDbDataReader`](./TDbDataReader.md)
$row = $command->queryRow();          // Returns single row array
$column = $command->queryColumn();    // Returns single column array
$scalar = $command->queryScalar();   // Returns single value

// Parameter binding
$command->bindParameter($name, $value);
$command->bindValue($name, $value);
```

## Properties

- `Text` - The SQL statement text
- `Connection` - The parent [`TDbConnection`](./TDbConnection.md)

## Usage

```php
$connection = new TDbConnection($dsn, $user, $pass);
$connection->Active = true;

$command = $connection->createCommand('SELECT * FROM users WHERE id = :id');
$command->bindParameter(':id', $userId);
$reader = $command->query();
$user = $reader->read();
```

## See Also

- [TDbConnection](./TDbConnection.md) - Database connection
- [TDbDataReader](./TDbDataReader.md) - Result set iterator
- [TDbTransaction](./TDbTransaction.md) - Transaction support