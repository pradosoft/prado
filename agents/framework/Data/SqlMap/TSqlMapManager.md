# TSqlMapManager

### Directories

[./](../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](./INDEX.md) > [TSqlMapManager](./TSqlMapManager.md)

`Prado\Data\SqlMap\TSqlMapManager`

Manages SqlMap configuration, statements, result maps, and type handlers.

Inherits from `Prado\TComponent`.

## Description

`TSqlMapManager` holds the SqlMap configuration including result maps, statements, parameter maps, cache models, and a type handler registry.

## Usage Example

```php
$conn = new TDbConnection($dsn, $dbuser, $dbpass);
$manager = new TSqlMapManager($conn);
$manager->configureXml('mydb-sqlmap.xml');
$sqlmap = $manager->getSqlMapGateway();
$result = $sqlmap->queryForObject('Products');
```

## Key Methods

### `setDbConnection($conn)`

Sets the default database connection.

### `getDbConnection()`

Returns the default database connection.

### `configureXml($file)`

Loads and parses the SqlMap configuration file.

### `getSqlmapGateway()`

Returns the `TSqlMapGateway` instance.

### `getTypeHandlers()`

Returns the `TSqlMapTypeHandlerRegistry`.

### `getCacheDependencies()`

Returns the cache dependency chain.

## Statement Management

### `getMappedStatements()`

Returns the `TMap` of mapped statements collection.

### `getMappedStatement($name)`

Gets a `MappedStatement` by name.

### `addMappedStatement($statement)`

Adds a named `MappedStatement`.

## Result Map Management

### `getResultMaps()`

Returns the `TMap` of result maps collection.

### `getResultMap($name)`

Gets a `TResultMap` by name.

### `addResultMap($result)`

Adds a named result map.

## Parameter Map Management

### `getParameterMaps()`

Returns the `TMap` of parameter maps collection.

### `getParameterMap($name)`

Gets a `TParameterMap` by name.

### `addParameterMap($parameter)`

Adds a named parameter map.

## Cache Management

### `addCacheModel($cacheModel)`

Adds a named cache model.

### `getCacheModel($name)`

Gets a cache model by name.

### `flushCacheModels()`

Flushes all cached objects.

## See Also

- [TSqlMapConfig](./TSqlMapConfig.md)
- [TSqlMapGateway](./TSqlMapGateway.md)
- [Configuration/TResultMap](../SqlMap/Configuration/TResultMap.md)
- [Configuration/TParameterMap](../SqlMap/Configuration/TParameterMap.md)
- [Statements/IMappedStatement](../SqlMap/Statements/IMappedStatement.md)

## Category

SqlMap
