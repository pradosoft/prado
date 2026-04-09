# IMappedStatement

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Statements](./INDEX.md) > [IMappedStatement](./IMappedStatement.md)

`Prado\Data\SqlMap\Statements\IMappedStatement`

Interface for all mapping statements.

## Description

`IMappedStatement` defines the interface that all mapping statements must implement.

## Methods

### `getID()`

Returns the name used to identify the MappedStatement.

### `getStatement()`

Returns the [`TSqlMapStatement`](../Configuration/TSqlMapStatement.md) SQL statement used by this mapped statement.

### `getManager()`

Returns the [`TSqlMapManager`](../TSqlMapManager.md) used by this mapped statement.

### `executeQueryForMap($connection, $parameter, $keyProperty, $valueProperty = null, $skip = -1, $max = -1, $delegate = null)`

Executes the SQL and returns all rows selected in a map keyed by `$keyProperty`.

### `executeUpdate($connection, $parameter)`

Executes an update statement. Also used for delete. Returns affected row count.

### `executeQueryForList($connection, $parameter, $result = null, $skip = -1, $max = -1, $delegate = null)`

Executes the SQL and returns a subset of rows.

### `executeQueryForObject($connection, $parameter, $result = null)`

Executes an SQL statement that returns a single row.

### `executeInsert($connection, $parameter)`

Executes an insert statement. Returns the generated key.

## See Also

- [TMappedStatement](./TMappedStatement.md)
- [TSelectMappedStatement](./TSelectMappedStatement.md)
- [TInsertMappedStatement](./TInsertMappedStatement.md)
- [TUpdateMappedStatement](./TUpdateMappedStatement.md)
- [TDeleteMappedStatement](./TDeleteMappedStatement.md)

## Category

SqlMap Statements
