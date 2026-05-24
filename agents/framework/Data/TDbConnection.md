# Data/TDbConnection

### Directories
[framework](../INDEX.md) / [Data](./INDEX.md) / **`TDbConnection`**

## Class Info
**Location:** `framework/Data/TDbConnection.php`
**Namespace:** `Prado\Data`
**Implements:** [`IDbConnection`](./IDbConnection.md) (which extends [`IDataConnection`](./IDataConnection.md))

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
| `Charset` | Character set to use for the connection. Universal IANA-style names (e.g. `UTF-8`, `ISO-8859-1`) are auto-translated per driver. @since 4.3.3 |
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
$conn->getDriverName();                    // e.g. 'mysql', 'pgsql', 'firebird', 'ibm'
$conn->getLastInsertID($sequence = '');    // last auto-increment ID
$conn->getDbMetaData();                    // returns [`TDbMetaData`](Common/TDbMetaData.md) for schema introspection
$conn->getDatabaseCharset();               // charset reported by active connection (@since 4.3.3)
$conn->getCanCharsetChange();              // true if charset can still be changed (@since 4.3.3)
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
- **Charset setup** — `Charset` accepts universal IANA-style names (`UTF-8`, `ISO-8859-1`, etc.) translated per-driver via `resolveCharsetForDriver()`. MySQL: `SET NAMES`; PostgreSQL: `SET client_encoding`; SQLite: `PRAGMA encoding`; Firebird/Oracle/MSSQL: injected into the DSN before opening. IBM DB2 has no charset support.
- **`Charset` cannot be changed after opening** for drivers that only support DSN-level charset (Firebird, Oracle, MSSQL). `getCanCharsetChange()` indicates whether it is still safe to set.
- **`getDatabaseCharset()`** queries the live connection for the actual charset in use; useful for verification.
- **One active transaction at a time** — `TDbConnection` tracks one `TDbTransaction`; nested transactions are not natively supported (use SAVEPOINTs manually if needed).
- **`getDbMetaData()`** returns a cached `TDbMetaData` subclass — the correct driver is selected automatically from `getDriverName()`. Supports MySQL, PostgreSQL, SQLite, MSSQL, Oracle, IBM DB2, and Firebird.
- **Implements `IDataConnection`** — can be used wherever a [`IDataConnection`](./IDataConnection.md) is expected.
