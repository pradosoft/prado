# TSqlMapTypeHandlerRegistry

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [DataMapper](./INDEX.md) > [TSqlMapTypeHandlerRegistry](./TSqlMapTypeHandlerRegistry.md)

`Prado\Data\SqlMap\DataMapper\TSqlMapTypeHandlerRegistry`

Registry mapping database types to type handler instances.

## Description

`TSqlMapTypeHandlerRegistry` maps JDBC type names and PHP class names to `TSqlMapTypeHandler` instances. It is used during parameter binding and result mapping.

## Key Methods

### `getDbTypeHandler($dbType)`

Returns the type handler for a given database field type.

### `getTypeHandler($class)`

Returns the type handler for a given PHP class name.

### `registerTypeHandler($handler)`

Registers a new type handler.

### `createInstanceOf($type)`

Creates a new instance of a particular class or returns default values for primitives.

### `convertToType($type, $value)`

Converts a value to a given PHP type.

## See Also

- [TSqlMapTypeHandler](./TSqlMapTypeHandler.md)

## Category

SqlMap DataMapper
