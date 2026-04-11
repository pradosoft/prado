# Data/SqlMap/Configuration/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories

[framework](./INDEX.md) / [Data](./Data/INDEX.md) / [SqlMap](./Data/SqlMap/INDEX.md) / [Configuration](./Data/SqlMap/Configuration/INDEX.md) / **`Configuration/INDEX.md`**

| Directory | Purpose |
|---|---|
| [`../`](../INDEX.md) | SqlMap Directory |

## Purpose

XML configuration parsing and in-memory object model for SqlMap mapped statements, parameter maps, result maps, and cache models.

## Classes

### XML Loaders

- **`TSqlMapXmlConfiguration`** — Parses the top-level SqlMap XML config file (`sqlmap.xml`). Registers data sources, type handlers, and triggers loading of all `<sqlMap>` resource files.

- **`TSqlMapXmlConfigBuilder`** — Builder that processes the parsed XML DOM into live `TSqlMapManager` state. Registers statements, parameter maps, result maps, and cache models.

- **`TSqlMapXmlMappingConfiguration`** — Parses individual SQL mapping XML files (`*.xml`). Processes `<select>`, `<insert>`, `<update>`, `<delete>`, `<parameterMap>`, `<resultMap>`, `<cacheModel>` elements.

### Statement Types

- **`TSqlMapStatement`** — Base mapped statement. Properties: `Id`, `ParameterClass`, `ResultClass`, `ParameterMap`, `ResultMap`, `Sql` (a `TStaticSql` or `TSimpleDynamicSql`).

- **`TSqlMapSelect`** — `<select>` statement. Adds `ResultSetType` (list or map) and cache model support.

- **`TSqlMapInsert`** — `<insert>` statement. Adds `SelectKey` support for retrieving generated keys.

- **`TSqlMapUpdate`** — `<update>` statement.

- **`TSqlMapDelete`** — `<delete>` statement.

- **`TSqlMapSelectKey`** — Nested `<selectKey>` inside an insert; can be pre or post insert, using a SQL query or a sequence.

### Parameter & Result Maps

- **`TParameterMap`** — Named parameter map: defines ordered/named parameter properties and their type handlers. Used to bind PHP object/array properties to SQL `:param` placeholders.

- **`TParameterProperty`** — One entry in a parameter map: property name, column name, type handler, jdbc type, null value.

- **`TResultMap`** — Named result map: defines how a result row is mapped back to a PHP object or array. Supports inheritance (`extends`), discriminators, and nested result maps.

- **`TResultProperty`** — One column-to-property mapping in a result map: column name/index, property name, type handler, lazy-load setting, nested select statement.

- **`TDiscriminator`** — Maps a column value to a sub-result-map (polymorphic result mapping).

- **`TSubMap`** — A discriminator sub-map entry: value → result map name.

### Cache Models

- **`TSqlMapCacheModel`** — Cache configuration for a statement. Properties: `Type` (`LRU`, `FIFO`, `OSCache`, `MemoryCache`), `FlushInterval`, `Size`. Flushes on associated `flushOnExecute` statements.

- **`TSqlMapCacheTypes`** — Enum-like class listing valid cache type strings.

- **`TSqlMapCacheKey`** — Generates a unique cache key from a statement ID + parameter object hash.

### Dynamic SQL Helpers

- **`TInlineParameterMapParser`** — Parses inline `#propertyName#` parameter syntax in SQL strings into `TParameterMap` entries.

- **`TSimpleDynamicParser`** — Handles `$propertyName$` (direct string substitution, not parameterised) in SQL.

## Patterns & Gotchas

- **`#param#` vs `$param$`** — `#param#` is safe parameterised binding; `$param$` is literal string substitution (SQL injection risk — avoid with user input).
- Result maps cascade: a `TResultProperty` may reference another `TSqlMapSelect` for lazy/nested loading.
- Cache flushes are triggered by `flushOnExecute` references between statements — circular flush graphs must be avoided.
- Statement IDs must be unique within a SqlMap configuration; use namespaced IDs (`namespace.statementId`) for large apps.
