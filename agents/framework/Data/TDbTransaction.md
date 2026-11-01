# Data/TDbTransaction

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbTransaction`**

## Class Info
**Location:** `framework/Data/TDbTransaction.php`
**Namespace:** `Prado\Data`

## Overview
`TDbTransaction` represents a database transaction. It is created by calling [`TDbConnection::beginTransaction()`](./TDbConnection.md). Implements [`IDataTransaction`](./IDataTransaction.md). It raises `OnCommit` after the database commits it and `OnRollback` after the database rolls it back.

## Key Methods

```php
$transaction->commit();    // Commit the transaction, then raise OnCommit
$transaction->rollBack();  // Roll back the transaction, then raise OnRollback
$transaction->getActive(); // Check if transaction is active
```

## Usage

```php
$transaction = $connection->beginTransaction();
try {
    $connection->createCommand($sql1)->execute();
    $connection->createCommand($sql2)->execute();
    $transaction->commit();
} catch (Exception $e) {
    if ($transaction->getActive()) {
        $transaction->rollBack();
    }
    throw $e;
}
```

## Events (@since 4.4.0)

| Event | Raised | Sender | Parameter |
|-------|--------|--------|-----------|
| `OnCommit` | after `PDO::commit()` succeeds | the transaction, inactive | `null` |
| `OnRollback` | after `PDO::rollBack()` succeeds | the transaction, inactive | `null` |

Deferring work until the commit:

```php
$transaction = $connection->getCurrentTransaction();
if ($transaction === null) {
    $this->announce($change);
} else {
    $transaction->attachEventHandler('OnCommit', fn ($sender, $param) => $this->announce($change));
}
```

- **Failed commit or rollback** → no event. A PDO exception leaves the transaction active, as before; a `false` return makes it inactive without an event.
- **Handler state** → the transaction is inactive and `TDbConnection::getCurrentTransaction()` returns `null`. A handler may begin a new transaction on the connection.
- **Once** → a transaction ends once, so its handlers run at most once. Each `beginTransaction()` returns a new transaction without handlers.
- **Handler exception** → propagates out of `commit()`/`rollback()`; later handlers do not run. The database work is already committed or rolled back, and the transaction stays inactive. A `catch` block that rolls back checks `getActive()` first, or its `rollBack()` throws `dbtransaction_transaction_inactive` and hides the handler exception.
- **Nesting** → not supported. `TDbConnection` holds one transaction; a second `beginTransaction()` while one is active throws a `PDOException` from the driver. The events belong to the outermost transaction.
- **No events** → for a transaction begun, committed, or rolled back on `getPdoInstance()` directly, a transaction ended by closing the connection, and a transaction the server ends implicitly (MySQL DDL).
- **No handlers** → behavior is unchanged.
- **Not on `IDataTransaction`** → the interface does not declare the events, so other implementers stay valid.

## See Also

- [IDataTransaction](./IDataTransaction.md) - Interface this class implements
- [TDbConnection](./TDbConnection.md) - Database connection
