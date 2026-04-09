# TSqlMapGateway

`Prado\Data\SqlMap\TSqlMapGateway`

DataMapper client facade providing access to SqlMap operations.

Inherits from `Prado\TComponent`.

## Description

`TSqlMapGateway` is the DataMapper client, a facade that provides access to the rest of the DataMapper framework. It provides three core functions:
1. Execute an update query (including insert and delete)
2. Execute a select query for a single object
3. Execute a select query for a list of objects

## Usage Example

```php
$sqlmap = $manager->getSqlMapGateway();

// Single object
$product = $sqlmap->queryForObject('GetProduct', $id);

// List of objects
$products = $sqlmap->queryForList('GetProducts', $criteria);

// Paged list
$pagedProducts = $sqlmap->queryForPagedList('GetProducts', null, 10, 0);

// Map keyed by property
$productMap = $sqlmap->queryForMap('GetProducts', null, 'productId', 'productName');

// Insert
$newId = $sqlmap->insert('InsertProduct', $productData);

// Update
$affected = $sqlmap->update('UpdateProduct', $productData);

// Delete
$affected = $sqlmap->delete('DeleteProduct', $id);
```

## Query Methods

### `queryForObject($statementName, $parameter = null, $result = null)`

Executes a SELECT statement returning a single object.

### `queryForList($statementName, $parameter = null, $result = null, $skip = -1, $max = -1)`

Executes a SELECT statement returning a list of objects.

### `queryWithRowDelegate($statementName, $delegate, $parameter = null, $result = null, $skip = -1, $max = -1)`

Executes a SELECT with a row delegate handler for custom row processing.

### `queryForPagedList($statementName, $parameter = null, $pageSize = 10, $page = 0)`

Returns a `TPagedList` for automatic pagination.

### `queryForPagedListWithRowDelegate(...)`

Returns a paged list with row delegate.

### `queryForMap($statementName, $parameter = null, $keyProperty = null, $valueProperty = null, $skip = -1, $max = -1)`

Returns results as a map keyed by a property.

### `queryForMapWithRowDelegate(...)`

Returns a map with row delegate.

## Update Methods

### `insert($statementName, $parameter = null)`

Executes an INSERT statement, returns the primary key.

### `update($statementName, $parameter = null)`

Executes an UPDATE statement, returns affected rows.

### `delete($statementName, $parameter = null)`

Executes a DELETE statement, returns affected rows.

## Cache Management

### `flushCaches()`

Flushes all cached objects belonging to this SqlMap.

## Type Handler

### `registerTypeHandler($typeHandler)`

Registers a custom type handler.

## See Also

- [TSqlMapManager](./TSqlMapManager.md)
- [TSqlMapPagedList](../SqlMap/DataMapper/TSqlMapPagedList.md)

## Category

SqlMap
