# TDbTransaction

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [TDbTransaction](./TDbTransaction.md)

**Location:** `framework/Data/TDbTransaction.php`
**Namespace:** `Prado\Data`

## Overview

`TDbTransaction` represents a database transaction. It is created by calling [`TDbConnection::beginTransaction()`](./TDbConnection.md).

## Key Methods

```php
$transaction->commit();    // Commit the transaction
$transaction->rollBack(); // Rollback the transaction
$transaction->getActive(); // Check if transaction is active
```

## Usage

```php
$transaction = $connection->beginTransaction();
try {
    $connection->createCommand($sql1)->execute();
    $connection->createCommand($sql2)->execute();
    $transaction->commit();
} catch(Exception $e) {
    $transaction->rollBack();
}
```

## See Also

- [TDbConnection](./TDbConnection.md) - Database connection