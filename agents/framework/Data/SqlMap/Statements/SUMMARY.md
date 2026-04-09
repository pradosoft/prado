# SUMMARY.md

Statement execution classes for SqlMap: mapped statements, SQL preparation, caching wrappers, and result-set processing.

## Classes

- **`IMappedStatement`** — Contract for all mapped statement executors; methods: `executeQueryForObject()`, `executeQueryForList()`, `executeQueryForMap()`, `executeUpdate()`, `executeInsert()`.

- **`TMappedStatement`** — Primary implementation coordinating parameter binding, SQL execution, and result mapping; handles nested selects and `N+1` post-select bindings.

- **`TSelectMappedStatement`** — Extends `TMappedStatement` for `<select>`; adds result cache lookup/store.

- **`TInsertMappedStatement`** — Extends `TMappedStatement` for `<insert>`; handles `<selectKey>` for generated keys.

- **`TUpdateMappedStatement`** / **`TDeleteMappedStatement`** — Extends `TMappedStatement` for update/delete.

- **`TCachingStatement`** — Decorator around `IMappedStatement`; intercepts `executeQueryForObject/List` to check/populate cache.

- **`TStaticSql`** — Holds plain non-dynamic SQL string; `getSql($parameter)` always returns same string.

- **`TSimpleDynamicSql`** — Holds SQL with `$property$` substitution markers; replaces markers with literal values.

- **`TPreparedStatement`** — Holds parameterized SQL with ordered list of `TParameterProperty` entries to bind.

- **`TPreparedStatementFactory`** — Builds `TPreparedStatement` from `TSqlMapStatement` + parameter object.

- **`TPreparedCommand`** — Binds `TPreparedStatement` to `TDbCommand`; iterates parameter properties, applies type handlers, calls `bindValue()`.

- **`TPostSelectBinding`** — Captures deferred work of nested `<select>` to execute after outer result assembled.

- **`TResultSetListItemParameter`** — Parameter object for loading list via post-select binding.

- **`TResultSetMapItemParameter`** — Parameter object for loading map/scalar via post-select binding.

- **`TSqlMapObjectCollectionTree`** — Builds tree of objects from flat result set when using nested result maps with `groupBy`.
