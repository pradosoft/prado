# TDbConnection / TDbCommand / TDbDataReader / TDbTransaction

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [TDbConnection](./TDbConnection.md)

**Location:** `framework/Data/`
**Namespace:** `Prado\Data`

## Overview

Thin PDO wrapper providing a consistent API for database operations. All four classes work together: `TDbConnection` creates `TDbCommand` objects; commands return `TDbDataReader` iterators or scalar results; transactions are managed via [`TDbTransaction`](./TDbTransaction.md).

---

## TDbConnection

PDO wrapper. Does not open the connection until `Active=true`.

### Constructor & Activation

```php
$conn = new TDbConnection('mysql:host=localhost;dbname=myapp', 'user', 'pass');
$conn->Active = true;  // opens connection
$conn->close();        // or $conn->Active = false
```

### Key Properties

| Property | Description |
|----------|-------------|
| `ConnectionString` | PDO DSN string |
| `Username` | DB username |
| `Password` | DB password |
| `Charset` | Character set (MySQL: utf8mb4; PostgreSQL: UTF8) |
| `Active` | Open/close the connection |
| `Attributes` | PDO attributes array (e.g., `[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]`) |
| `ColumnCase` | [`TDbColumnCaseMode`](./TDbColumnCaseMode.md): `Preserved`, `LowerCase`, `UpperCase` |
| `NullConversion` | [`TDbNullConversionMode`](./TDbNullConversionMode.md): `Preserved`, `EmptyStringToNull`, `NullToEmptyString` |
| `TransactionClass` | Class name for transaction objects (default: `TDbTransaction`) |

### Key Methods

```php
$cmd = $conn->createCommand($sql);         // returns [`TDbCommand`](./TDbCommand.md)
$tx  = $conn->beginTransaction();          // returns [`TDbTransaction`](./TDbTransaction.md)
$conn->quoteTableName('my_table');         // driver-specific quoting
$conn->quoteColumnName('my_col');
$conn->getPdoInstance();                   // raw PDO object
$conn->getDriverName();                    // e.g. 'mysql', 'pgsql'
$conn->getLastInsertID($sequence = '');    // last auto-increment ID
$conn->getSchema();                        // returns [`TDbMetaData`](../Common/TDbMetaData.md) for schema introspection
```

### Event

`OnAfterOpen` — raised after connection opens. Useful for per-connection setup (e.g., `SET search_path`).

---

## TDbCommand

Wraps a PDO prepared statement. Lazily prepares on first execute/query.

```php
$cmd = $conn->createCommand('SELECT * FROM users WHERE id = :id');
$cmd->bindValue(':id', 42, PDO::PARAM_INT);
$reader = $cmd->query();     // returns TDbDataReader

// One-shot helpers:
$row    = $cmd->queryRow();     // first row as array
$col    = $cmd->queryColumn();  // first column as array
$scalar = $cmd->queryScalar();  // first column of first row

// Non-query:
$rowsAffected = $cmd->execute();
```

Parameter binding:
```php
$cmd->bindParameter(':name', $var);          // bind by reference
$cmd->bindValue(':age', 30, PDO::PARAM_INT); // bind by value
```

---

## TDbDataReader

Forwards-only iterator over a PDO result set.

```php
$reader = $cmd->query();

// Iterator:
foreach ($reader as $row) { /* $row is associative array */ }

// Explicit:
while ($row = $reader->read()) { ... }

// All at once:
$rows = $reader->readAll();

// Multiple result sets:
$reader->nextResult();
```

Implements `Iterator` and `Countable` (`count()` returns remaining rows — may not be supported by all drivers).

---

## TDbTransaction

```php
$tx = $conn->beginTransaction();
try {
    $conn->createCommand($sql1)->execute();
    $conn->createCommand($sql2)->execute();
    $tx->commit();
} catch (Exception $e) {
    $tx->rollBack();
}
```

Properties: `Active` (bool — whether transaction is open).

---

## Patterns & Gotchas

- **Always use parameter binding** — never interpolate user input into SQL strings.
- **DSN format** — standard PDO DSN: `mysql:host=localhost;dbname=mydb`, `pgsql:host=localhost;dbname=mydb`, `sqlite:/path/to/db.sqlite`.
- **Charset setup** — MySQL sets charset via `SET NAMES`; PostgreSQL via `SET client_encoding`; both handled automatically when `Charset` is set.
- **One active transaction at a time** — `TDbConnection` tracks one `TDbTransaction`; nested transactions are not natively supported (use SAVEPOINTs manually if needed).
- **`getSchema()`** returns a cached `TDbMetaData` subclass — the correct driver is selected automatically from `getDriverName()`.
