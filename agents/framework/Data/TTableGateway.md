# TTableGateway / TSqlCriteria

### Directories

[./](../INDEX.md) > [Data](./INDEX.md) > [DataGateway](./DataGateway/INDEX.md) > [TTableGateway](./TTableGateway.md)

**Location:** `framework/Data/DataGateway/`
**Namespace:** `Prado\Data\DataGateway`

## Overview

Stateless Table Gateway pattern. [`TTableGateway`](./TTableGateway.md) provides a lightweight object-oriented interface to a single database table. No identity tracking, no object state — every call hits the database directly. Use when you want SQL control without a full ORM.

## TTableGateway

```php
$conn = new TDbConnection('mysql:host=localhost;dbname=myapp', 'user', 'pass');
$conn->Active = true;
$gateway = new TTableGateway('users', $conn);
```

### Finder Methods

```php
// By primary key (scalar or associative array for composite PKs):
$row = $gateway->findByPk(42);
$row = $gateway->findByPk(['dept_id' => 1, 'emp_id' => 42]);

// With criteria:
$criteria = new TSqlCriteria('active = :a', [':a' => 1]);
$reader = $gateway->findAll($criteria);        // TDbDataReader
$row    = $gateway->find($criteria);           // first row as array or false

// Raw SQL:
$reader = $gateway->findAllBySql('SELECT * FROM users WHERE active = 1');
$row    = $gateway->findBySql('SELECT * FROM users WHERE id = ?', [1]);

// Count:
$n = $gateway->findCount($criteria);
```

### Mutator Methods

```php
// Insert — returns last insert ID:
$id = $gateway->insert(['name' => 'Alice', 'email' => 'alice@example.com']);

// Update all rows matching criteria:
$rowsAffected = $gateway->update(['active' => 0], new TSqlCriteria('last_login < :d', [':d' => $date]));

// Update by PK:
$rowsAffected = $gateway->updateByPk(['name' => 'Bob'], 42);

// Delete:
$rowsAffected = $gateway->delete(new TSqlCriteria('active = 0'));
$rowsAffected = $gateway->deleteByPk(42);
```

### Events

| Event | Parameter | Purpose |
|-------|-----------|---------|
| `OnCreateCommand` | [`TDataGatewayEventParameter`](./TDataGatewayEventParameter.md) | Inspect/modify [`TDbCommand`](../TDbCommand.md) before execution |
| `OnExecuteCommand` | [`TDataGatewayResultEventParameter`](./TDataGatewayResultEventParameter.md) | Inspect command and result after execution |

```php
$gateway->attachEventHandler('OnCreateCommand', function($sender, $param) {
    // $param->getCommand() → [`TDbCommand`](../TDbCommand.md)
    Prado::log($param->getCommand()->getText(), TLogger::DEBUG);
});
```

---

## TSqlCriteria

Query criteria value object. Used by both [`TTableGateway`](./TTableGateway.md) and [`TActiveRecord`](../ActiveRecord/TActiveRecord.md).

```php
// Constructor shorthand:
$c = new TSqlCriteria('active = :a AND role = :r', [':a' => 1, ':r' => 'admin']);

// Property-by-property:
$c = new TSqlCriteria();
$c->Condition = 'active = :a';
$c->Parameters[':a'] = 1;
$c->OrdersBy = ['created_at' => 'DESC', 'name' => 'ASC'];
$c->Limit = 20;
$c->Offset = 40;
$c->Select = 'id, name, email';  // replaces SELECT *
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `Condition` | string | WHERE clause fragment (parameterized) |
| `Parameters` | `TAttributeCollection` | Named bind params (`:name => value`) |
| `OrdersBy` | array | `['col' => 'asc'|'desc', ...]` |
| `Limit` | int | Row limit (-1 = none) |
| `Offset` | int | Row offset (-1 = none) |
| `Select` | string | SELECT list (default `'*'`) |

---

## Patterns & Gotchas

- **Stateless** — [`TTableGateway`](./TTableGateway.md) holds no row data. Use [`TActiveRecord`](../ActiveRecord/TActiveRecord.md) when you need object identity or lazy relations.
- **Always parameterize** — never interpolate user input into `Condition`. Use `:name` style binding.
- **`findAll()` returns [`TDbDataReader`](../TDbDataReader.md)** — iterate it or call `readAll()`. It is a forward-only cursor; you cannot rewind.
- **`findByPk()` composite PKs** — pass an associative array when the table has a composite primary key.
- **`OnCreateCommand` hook** — ideal for query logging, multi-tenancy scoping (appending a tenant WHERE clause), or debugging. Attach before any gateway call.
