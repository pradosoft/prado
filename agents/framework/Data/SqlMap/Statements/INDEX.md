# Data/SqlMap/Statements/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](./INDEX.md) / [Data](./Data/INDEX.md) / [SqlMap](./Data/SqlMap/INDEX.md) / [Statements](./Data/SqlMap/Statements/INDEX.md) / **`Statements/INDEX.md`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | SqlMap Directory |

## Purpose

Statement execution classes for SqlMap: mapped statements, SQL preparation, caching wrappers, and result-set processing.

## Classes

### Interfaces

- **`IMappedStatement`** — Contract for all mapped statement executors. Methods: `executeQueryForObject()`, `executeQueryForList()`, `executeQueryForMap()`, `executeUpdate()`, `executeInsert()`.

### Core Execution

- **`TMappedStatement`** — Primary implementation of `IMappedStatement`. Coordinates parameter binding (via `TPreparedCommand`), SQL execution, and result mapping. Handles nested selects and `N+1` post-select bindings.

- **`TSelectMappedStatement`** — Extends `TMappedStatement` for `<select>` — adds result cache lookup/store around execution.

- **`TInsertMappedStatement`** — Extends `TMappedStatement` for `<insert>` — handles `<selectKey>` pre/post execution to retrieve generated keys.

- **`TUpdateMappedStatement`** — Extends `TMappedStatement` for `<update>`.

- **`TDeleteMappedStatement`** — Extends `TMappedStatement` for `<delete>`.

- **`TCachingStatement`** — Decorator around another `IMappedStatement`; intercepts `executeQueryForObject/List` to check/populate a `TSqlMapCache`.

### SQL Representation

- **`TStaticSql`** — Holds a plain, non-dynamic SQL string. `getSql($parameter)` always returns the same string.

- **`TSimpleDynamicSql`** — Holds a SQL string with `$property$` substitution markers. Replaces markers with literal values from the parameter object before execution.

- **`TPreparedStatement`** — Holds a parameterised SQL string with an ordered list of `TParameterProperty` entries to bind.

- **`TPreparedStatementFactory`** — Builds a `TPreparedStatement` from a `TSqlMapStatement` + parameter object. Resolves inline `#param#` maps and applies type handlers.

- **`TPreparedCommand`** — Binds a `TPreparedStatement` to a `TDbCommand`: iterates parameter properties, applies type handlers, and calls `bindValue()`.

### Result Processing Helpers

- **`TPostSelectBinding`** — Captures the deferred work of a nested `<select>` that must be executed after the outer result is assembled: stores the statement ID, parameters, result object reference, and property name to populate.

- **`TResultSetListItemParameter`** — Parameter object for loading a list via a post-select binding.

- **`TResultSetMapItemParameter`** — Parameter object for loading a map/scalar via a post-select binding.

- **`TSqlMapObjectCollectionTree`** — Builds a tree of objects from a flat result set when using nested result maps with a `groupBy` key — avoids duplicate parent objects.

## Execution Flow

1. `TSqlMapGateway::queryForList($id, $param)` → looks up `TMappedStatement` by ID
2. `TMappedStatement::executeQueryForList()` → calls `TPreparedStatementFactory` to build SQL
3. `TPreparedCommand` binds parameters onto a `TDbCommand`
4. `TDbCommand::query()` returns a `TDbDataReader`
5. Result mapper iterates rows → populates objects using `TResultMap` / `TResultProperty`
6. Post-select bindings (`TPostSelectBinding`) are executed after the main result loop

## Patterns & Gotchas

- **`N+1` problem** — Nested `<select>` in a result map triggers one query per parent row. Use `groupBy` + nested result maps for join-based loading instead.
- **Caching** — `TCachingStatement` is automatically wrapped around `TSelectMappedStatement` when a `cacheModel` is defined; it must not be wrapped manually.
- **`TSimpleDynamicSql` injection risk** — `$property$` substitution is literal; never use it with user-controlled input.
