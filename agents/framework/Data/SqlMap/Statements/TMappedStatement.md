# TMappedStatement

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Statements](./INDEX.md) > [TMappedStatement](./TMappedStatement.md)

`Prado\Data\SqlMap\Statements\TMappedStatement`

Base class for executing SQL mapped statements.

Inherits from `Prado\TComponent`.

## Description

`TMappedStatement` is the base class that executes SQL mapped statements. Mapped statements can hold any SQL statement and use Parameter Maps and Result Maps for input and output.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `QUERY_FOR_LIST` | `0` | Select is to query for list |
| `QUERY_FOR_ARRAY` | `1` | Select is to query for array |
| `QUERY_FOR_OBJECT` | `2` | Select is to query for object |

## Key Methods

See `IMappedStatement` interface for the main execution methods.

## See Also

- [IMappedStatement](./IMappedStatement.md)
- [TSelectMappedStatement](./TSelectMappedStatement.md)
- [TInsertMappedStatement](./TInsertMappedStatement.md)
- [TUpdateMappedStatement](./TUpdateMappedStatement.md)
- [TDeleteMappedStatement](./TDeleteMappedStatement.md)

## Category

SqlMap Statements
