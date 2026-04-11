# Data/SqlMap/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](./INDEX.md) / [Data](./Data/INDEX.md) / [SqlMap](./Data/SqlMap/INDEX.md) / **`SqlMap/INDEX.md`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | Data Directory |
| [`Configuration/`](Configuration/INDEX.md) | XML parsing: statements, parameter maps, result maps, cache models |
| [`DataMapper/`](DataMapper/INDEX.md) | Runtime: type handlers, caches, lazy loading, exceptions |
| [`Statements/`](Statements/INDEX.md) | Statement execution: SQL preparation, binding, result mapping |

## Purpose

Top-level entry points for the SqlMap (iBATIS-style) SQL mapping framework. Separates SQL from PHP code using external XML mapping files.

## Classes

- **`TSqlMapManager`** — Central registry and runtime engine. Holds all parsed statements, parameter maps, result maps, cache models, and the database connection. Obtain an instance via `TSqlMapConfig` or construct directly:
  ```php
  $manager = new TSqlMapManager($connection);
  $manager->configureXml('/path/to/sqlmap.xml');
  $gateway = $manager->getDataMapper(); // returns TSqlMapGateway
  ```
  Key methods: `getDataMapper()`, `getDbConnection()`, `getMappedStatement($id)`, `getParameterMap($id)`, `getResultMap($id)`, `getCacheModel($id)`.

- **`TSqlMapGateway`** — Facade for executing mapped statements. All application code should use this class, not `TSqlMapManager` directly. Key methods:
  - `queryForObject($statement, $parameter)` — returns a single object or `null`
  - `queryForList($statement, $parameter, $result, $skip, $max)` — returns array or `TList`
  - `queryForMap($statement, $parameter, $keyProperty, $valueProperty)` — returns assoc array keyed by a result property
  - `queryForPagedList($statement, $parameter, $pageSize)` — returns `TSqlMapPagedList`
  - `insert($statement, $parameter)` — returns generated key (if `<selectKey>` defined)
  - `update($statement, $parameter)` — returns affected row count
  - `delete($statement, $parameter)` — returns affected row count
  - `beginTransaction()`, `commitTransaction()`, `rollbackTransaction()`

- **`TSqlMapConfig`** — `TModule` subclass registered in `application.xml`. Properties: `ConfigFile` (path to `sqlmap.xml`), `ConnectionID` (reference to a `TDataSourceConfig` module), `EnableCache`. Sets up `TSqlMapManager` during application init and stores it in `TApplication` parameters.

## Patterns & Gotchas

- **Always use `TSqlMapGateway`** for query execution — it provides transaction support, cache integration, and a clean API.
- **`TSqlMapConfig`** auto-creates the `TSqlMapManager` and stores it in `TApplication` parameters under the module ID; retrieve with `Prado::getApplication()->getModule('sqlmap')->getClient()`.
- Statement IDs are global within a `TSqlMapManager` instance; use dot-notation prefixes (`namespace.id`) for large projects.
- Transactions wrap multiple gateway calls; always `commitTransaction()` or `rollbackTransaction()` in a `try`/`finally` block.
