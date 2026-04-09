# TParameterMap

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TParameterMap](./TParameterMap.md)

`Prado\Data\SqlMap\Configuration\TParameterMap`

Defines how object properties are mapped to SQL statement parameters.

Inherits from `Prado\TComponent`.

## Description

`TParameterMap` holds one or more parameter elements that map object properties to placeholders in a SQL statement. It defines an ordered list of values that match up with the placeholders of a parameterized query statement.

## Key Properties

### `ID`

A unique identifier for the parameterMap.

### `Properties`

Returns a `TList` of [`TParameterProperty`](./TParameterProperty.md) objects.

### `Extends`

Name of another parameterMap to extend.

## Key Methods

### `addProperty($property)`

Adds a `TParameterProperty` to the map.

### `insertProperty($index, $property)`

Inserts a property at a specific index.

### `getProperty($index)`

Gets a property by name (string) or index (int).

### `getPropertyNames()`

Returns an array of all property names.

### `getPropertyValue($registry, $property, $parameterValue)`

Gets the value of a property from the parameter object, applying type handling and null value conversion.

## Example XML

```xml
<parameterMap id="ProductParam">
    <parameter property="name" column="product_name"/>
    <parameter property="price" column="product_price"/>
</parameterMap>

<insert id="InsertProduct" parameterMap="ProductParam">
    INSERT INTO products (product_name, product_price) VALUES (?, ?)
</insert>
```

## See Also

- [TParameterProperty](./TParameterProperty.md)
- [TSqlMapStatement](./TSqlMapStatement.md)

## Category

SqlMap Configuration
