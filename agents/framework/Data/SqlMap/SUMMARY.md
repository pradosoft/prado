# SUMMARY.md

Top-level entry points for SqlMap (iBATIS-style) SQL mapping framework; separates SQL from PHP code using external XML mapping files.

## Classes

- **`TSqlMapManager`** — Central registry and runtime engine; obtain via `TSqlMapConfig` or construct directly; methods: `getDataMapper()`, `getDbConnection()`, `getMappedStatement($id)`, `getParameterMap($id)`, `getResultMap($id)`, `getCacheModel($id)`.

- **`TSqlMapGateway`** — Facade for executing mapped statements; methods: `queryForObject()`, `queryForList()`, `queryForMap()`, `queryForPagedList()`, `insert()`, `update()`, `delete()`, `beginTransaction()`, `commitTransaction()`, `rollbackTransaction()`.

- **`TSqlMapConfig`** — `TModule` subclass registered in `application.xml`; properties: `ConfigFile`, `ConnectionID`, `EnableCache`.
