# ADR-001: Data Subsystem Abstraction — Completing the Interface Layer

**Status:** Proposed
**Date:** 2026-05-05
**Deciders:** Brad Anderson

---

## Context

The Data subsystem already has a rich interface layer introduced in 4.3.3:

| Interface | Location | Purpose |
|---|---|---|
| `IDataConnection` | `Data/` | Connection lifecycle + command/transaction factories |
| `IDataCommand` | `Data/` | Query execution (execute, query, queryRow, …) |
| `IDataReader` | `Data/` | Result-set iteration |
| `IDataTransaction` | `Data/` | Nested transaction management |
| `IDataMetaData` | `Data/Common/` | Schema introspection |
| `IDataCommandBuilder` | `Data/Common/` | CRUD command factory |
| `IDataTableInfo` | `Data/Common/` | Table/collection metadata |

These interfaces were designed with non-PDO back-ends in mind (the MongoDB plugin at `prado-mongo` is the first concrete example). Despite this, several subsystems still hard-wire themselves to the concrete `TDbConnection`/PDO stack, preventing non-SQL drivers from being plugged in cleanly.

### Remaining coupling sites (non-driver-specific files)

| File | Coupling | Detail |
|---|---|---|
| `Common/TDbCommandBuilder` | `use PDO;` | `PDO::PARAM_*` constants used in `getPdoType()` and `bindColumnValues()` |
| `Common/TDbCommandBuilder` | `TDbCommand` type hint | Return type of all `create*Command()` methods |
| `Common/TDbMetaData` | `TDbConnection` type hint | Constructor + `getDbConnection()` return type |
| `Common/IDataTableInfo` | `TDbTableColumn` concrete type | `getColumns()` / `getColumn()` return `TDbTableColumn`, not an interface |
| `Common/IDataCommandBuilder` | `TDbTableColumn` concrete type | Same leak through `getTableInfo()` |
| `SqlMap/IMappedStatement` | `TDbConnection` type hints | All five `execute*` method `@param` signatures |
| `SqlMap/TMappedStatement` | `TDbConnection` type hints | Implements IMappedStatement with concrete type |
| `ActiveRecord/TActiveRecord` | `TDbConnection` type hint | Property `@var`, constructor `@param`, static `$db` |
| `DataGateway/TTableGateway` | `TDbConnection` type hint | Constructor `@param`, `getDbConnection()` return type |

Additionally, `IDataTableInfo` and `IDataCommandBuilder` use the term **table** and expose **column** semantics (`getPrimaryKeys`, `getForeignKeys`, `getColumns`). These are SQL-relational concepts that do not map naturally to document stores (collections, fields, index keys) or key-value stores. A NoSQL driver implementing these interfaces must either contort its terminology or leave methods unimplemented.

---

## Decision

**Complete the abstraction in two phases without breaking backward compatibility.**

### Phase 1 — Sever the remaining PDO/TDb concrete references (4.3.3)

Targeted, low-risk changes that fix the type-hint inconsistencies without touching the public API shape:

1. **`TDbCommandBuilder::getPdoType()`** — Extract the four PDO type constants into a small internal helper method or use the integer values directly. `PDO::PARAM_NULL = 0`, `PARAM_INT = 1`, `PARAM_STR = 2`, `PARAM_BOOL = 5`. This eliminates the `use PDO;` in a file that is otherwise not PDO-specific. The method remains `public static` for back-compat; non-PDO builders simply never call it.

2. **`IMappedStatement`** — Change the `@param TDbConnection` docblock type hints to `@param IDataConnection`. The actual PHP signatures use no type hint today, so this is a documentation-only fix. The SqlMap runtime already passes `TDbConnection`, which is an `IDataConnection`; non-SQL runners can pass any `IDataConnection` implementation.

3. **`TDbMetaData`** — Change constructor and `getDbConnection()` to accept/return `IDataConnection` rather than `TDbConnection`. The internal `$_connection` field already calls only interface methods (`getDriverName()`, `setActive()`, `createCommand()`). This is a pure signature widening — backward compatible.

4. **`TActiveRecord`** — Change `@var`, `@param`, and `@return` docblocks referencing `TDbConnection` to `IDataConnection`. The underlying code already works through the interface; only the docblocks are wrong.

5. **`TTableGateway`** — Same docblock-only fix.

6. **`IDataTableInfo::getColumns()` / `getColumn()`** — Introduce `IDataColumn` as a marker/minimum interface (analogous to `IDbHasSchema`). Change the return types in the interface from the concrete `TDbTableColumn` to `IDataColumn`. `TDbTableColumn` implements `IDataColumn`. This is the only change that touches an interface contract and requires driver metadata classes to declare the return type — but since all current implementations return `TDbTableColumn`, the change is source-compatible.

### Phase 2 — Collection/document-store vocabulary (4.4.x)

For drivers like MongoDB where "table" is a collection, "column" is a field, and "primary key" is `_id`:

1. **Keep `IDataTableInfo` as-is** (the relational vocabulary is fine for SQL drivers).

2. **Add `IDataCollectionInfo extends IDataTableInfo`** — Override `getColumns()` to return `IDataField[]` and add `getIndexes(): array`. MongoDB's `TMongoCollectionInfo` implements this.

3. **Add `IDataField extends IDataColumn`** — Adds `getDbType(): string` and `isNullable(): bool` as the minimal document-field contract.

4. **`IDataMetaData::findTableNames()`** — Already named `findTableNames()`; NoSQL drivers return collection names. No change needed.

5. **`IDataCommandBuilder` SQL methods** — `applyLimitOffset()`, `applyOrdering()`, `getSearchExpression()` are SQL-centric but still meaningful for document stores (MongoDB supports `$limit`, `$sort`, `$text`). The interface methods stay; a NoSQL builder translates them to its query language internally.

---

## Options Considered

### Option A: Status quo — interfaces exist but coupling remains

| Dimension | Assessment |
|---|---|
| Complexity | Low |
| Back-compat risk | None |
| NoSQL extensibility | Blocked — non-SQL plugins must subclass TDbConnection |
| Maintenance burden | Growing — every new driver must mock out PDO constants |

**Pros:** No work required now.
**Cons:** The interface layer is misleading — it implies pluggability but concrete type hints prevent it. The MongoDB plugin at `prado-mongo` already works around this; other future drivers will hit the same friction.

---

### Option B: Full decoupling now — replace all TDb* with I* everywhere (chosen for Phase 1 core; Phase 2 deferred)

| Dimension | Assessment |
|---|---|
| Complexity | Med (Phase 1 is low; Phase 2 is med) |
| Back-compat risk | Low for Phase 1; Med for Phase 2 interfaces |
| NoSQL extensibility | Full — any driver implements the I* layer |
| Maintenance burden | Lower — new drivers follow a clear interface contract |

**Pros:** Interface layer becomes the true abstraction surface. External plugins (`prado-mongo`, hypothetical Redis or Elasticsearch adapters) implement `IDataConnection` / `IDataMetaData` without inheriting from PDO wrappers. `TActiveRecord` could theoretically work against a MongoDB back-end.

**Cons:** Phase 2 introduces new interfaces (`IDataCollectionInfo`, `IDataField`) that take design iteration. Rushing these risks a poor contract that must be deprecated quickly.

---

### Option C: Adapter pattern — wrap non-SQL drivers as TDbConnection

| Dimension | Assessment |
|---|---|
| Complexity | High per driver |
| Back-compat risk | None |
| NoSQL extensibility | Achievable but unnatural |
| Maintenance burden | High — each driver must fake PDO semantics |

**Pros:** No changes to the framework core.
**Cons:** Adapters that mimic PDO for document stores are fragile and misleading. This is what `prado-mongo` was forced to do before the interface layer existed. The interfaces were added precisely to avoid this.

---

## Trade-off Analysis

Phase 1 is all upside: it aligns docblock types with the interface already in use, adds `IDataColumn` as a minimal abstraction that does not break existing code, and removes `use PDO;` from a builder that has no business knowing it's talking to PDO. Every change is backward compatible.

Phase 2 trades a small interface surface increase (two new interfaces) for meaningful document-store extensibility. The risk is premature vocabulary — locking in terms like "collection" and "field" before MongoDB usage patterns are clear. Deferring to 4.4.x, after `prado-mongo` has been used in anger, is the safer path.

The `IDataCommandBuilder` SQL-flavored methods (`applyLimitOffset`, `getSearchExpression`, etc.) are a remaining tension point. They are abstract enough that a document-store builder can interpret them without breaking the interface contract, so they do not need renaming now. If a back-end genuinely cannot support one (e.g., a key-value store with no ordering), the method can `throw new TDbException('unsupported')` — the same pattern used by `TDbCommandBuilder::createUpsertCommand()` on drivers that lack native upsert.

---

## Consequences

**What becomes easier after Phase 1:**
- Any class that accepts `IDataConnection` works with SQL and non-SQL back-ends without modification.
- `TDbMetaData` can be extended by non-SQL metadata handlers without inheriting PDO coupling.
- IDE type-checking and PHPStan stop flagging `IDataConnection` where `TDbConnection` is expected.
- `prado-mongo` and future plugins have a clean contract to implement.

**What becomes harder:**
- Callers that relied on `TDbConnection`-specific methods (e.g., `getPdo()`, `getCharset()`) must now check `instanceof TDbConnection` before calling them. This is correct behavior — those are driver-specific concerns.

**What we'll need to revisit:**
- `IDataTableInfo::getColumns()` return type after `IDataColumn` is introduced — driver metadata classes must declare the return type.
- SqlMap's `TSqlMapManager` still resolves connections through `TDbConnection`; a future phase will need to thread `IDataConnection` all the way through the mapper.
- `TActiveRecord::$GATEWAY` uses `TActiveRecordGateway` which internally creates `TDbCommandBuilder` — this chain needs `IDataCommandBuilder` at each step for full NoSQL support.

---

## Action Items

### Phase 1 (4.3.3)

1. [ ] **`IDataColumn` interface** — Add `framework/Data/Common/IDataColumn.php`. Minimal contract: `getColumnName(): string`, `getColumnId(): string`, `allowNull(): bool`, `getDbType(): string`. `TDbTableColumn` implements it.
2. [ ] **`IDataTableInfo`** — Change `getColumns()` return type to `IDataColumn[]`; `getColumn()` to `?IDataColumn`.
3. [ ] **`IDataCommandBuilder`** — Change `getTableInfo()` return type to `IDataTableInfo` (already is); `bindColumnValues()` parameter from `array` to `IDataColumn[]` (already is an untyped array — leave for Phase 2 to avoid churn).
4. [ ] **`TDbCommandBuilder`** — Extract PDO integer constants to remove `use PDO;`. Keep `getPdoType()` public static for back-compat.
5. [ ] **`TDbMetaData`** — Widen constructor and `getDbConnection()` to `IDataConnection`.
6. [ ] **`IMappedStatement`** — Update all five `execute*` docblock `@param` types to `IDataConnection`.
7. [ ] **`TActiveRecord`** — Update `@var $_connection`, `@param $connection`, `@return` in `getDbConnection()` to `IDataConnection`.
8. [ ] **`TTableGateway`** — Same docblock updates.
9. [ ] **PHPStan extensions** — Verify `DynamicMethodsClassReflectionExtension` does not assume `TDbConnection` for connection-typed properties.
10. [ ] **Tests** — Add `TDbDriverCapabilitiesTest`-style interface compliance tests: for each I* interface, assert that each concrete implementor satisfies the contract without triggering PDO-specific code paths.

### Phase 2 (4.4.x — after prado-mongo production experience)

11. [ ] **`IDataCollectionInfo extends IDataTableInfo`** — Add vocabulary for document collections (index metadata, nested field support).
12. [ ] **`IDataField extends IDataColumn`** — Document field contract with type system decoupled from PDO/SQL.
13. [ ] **SqlMap threading** — Thread `IDataConnection` through `TSqlMapManager` and `TSqlMapGateway`.
14. [ ] **`TActiveRecord` gateway chain** — Replace `TActiveRecordGateway`'s internal `TDbCommandBuilder` assumption with `IDataCommandBuilder`.
