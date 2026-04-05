# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Stateless Table Gateway pattern implementation. Provides a thin, object-based interface to a single database table without any identity tracking or object state.

## Classes

- **`TTableGateway`** — Main gateway class. Constructor: `new TTableGateway($tableOrView, $connection)`. Key methods:
  - **Finders:** `findByPk($pk)`, `findAll($criteria)`, `findAllBySql($sql, $params)`, `find($criteria)`, `findBySql($sql, $params)`, `findCount($criteria)`
  - **Mutators:** `insert($data)`, `update($data, $criteria)`, `updateByPk($data, $pk)`, `delete($criteria)`, `deleteByPk($pk)`
  - Multi-row results return a `TDbDataReader`; single-row results return an array or `false`.
  - Events: `OnCreateCommand` (before execution, inspect/modify the command), `OnExecuteCommand` (after execution, inspect results).

- **`TDataGatewayCommand`** — Internal command builder used by `TTableGateway`. Wraps `TDbCommandBuilder` and binds `TSqlCriteria` parameters to `TDbCommand` objects. Raises `OnCreateCommand` and `OnExecuteCommand` on the parent gateway.

- **`TSqlCriteria`** — Query criteria value object. Properties:
  - `Condition` — SQL WHERE clause (parameterised, e.g. `"name = :name"`)
  - `Parameters` — `TAttributeCollection` of named bind values (`:name => 'value'`)
  - `OrdersBy` — ordered map of `column => 'asc'|'desc'`
  - `Limit` — integer row limit
  - `Offset` — integer row offset
  - `Select` — custom SELECT expression (default `*`)
  - Constructor shorthand: `new TSqlCriteria('name = :name AND active = 1', [':name' => $name])`

- **`TDataGatewayEventParameter`** — Event parameter for `OnCreateCommand`: gives access to the `TDbCommand` before execution.

- **`TDataGatewayResultEventParameter`** — Event parameter for `OnExecuteCommand`: gives access to the `TDbCommand` and result after execution.

## Usage Pattern

```php
$conn = new TDbConnection('pgsql:host=localhost;dbname=mydb', 'user', 'pass');
$gateway = new TTableGateway('users', $conn);

// Find with criteria
$criteria = new TSqlCriteria('active = :a', [':a' => 1]);
$criteria->OrdersBy['created_at'] = 'desc';
$criteria->Limit = 20;
$rows = $gateway->findAll($criteria)->readAll();

// Insert
$newId = $gateway->insert(['name' => 'Alice', 'email' => 'alice@example.com']);
```

## Patterns & Gotchas

- **Stateless** — `TTableGateway` holds no row data; every call hits the database. Prefer `TActiveRecord` when you need object identity or lazy relations.
- **Always use parameterised `Condition`** — never interpolate user input into the `Condition` string.
- **`OnCreateCommand` hook** — useful for logging, query modification (e.g. adding tenant scoping), or debugging. Attach a handler before calling find/insert/update/delete.
- `findByPk` / `deleteByPk` accept a scalar for single-column PKs or an associative array for composite PKs.
