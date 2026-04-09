# TResultMap

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TResultMap](./TResultMap.md)

`Prado\Data\SqlMap\Configuration\TResultMap`

Defines how result set columns are mapped to object properties.

Inherits from `Prado\TComponent`.

## Description

`TResultMap` lets you control how data is extracted from query results and how columns are mapped to object properties. A ResultMap can describe column types, null value replacements, and complex property mappings including collections.

## Key Properties

### `ID`

A unique identifier for the resultMap.

### `Class`

The PHP class name or array instance to populate.

### `Columns`

Returns the `TMap` of result columns ([`TResultProperty`](./TResultProperty.md)).

### `Extends`

ID of another resultMap to extend (inherit properties from).

### `GroupBy`

Comma-separated list of properties for group-by optimization.

### `Discriminator`

The [`TDiscriminator`](./TDiscriminator.md) for polymorphic result mapping.

## Key Methods

### `addResultProperty($property)`

Adds a `TResultProperty` to the result mapping.

### `createInstanceOfResult($registry)`

Creates a new instance of the result class.

### `resolveSubMap($registry, $row)`

Resolves the appropriate sub-map using the discriminator column.

## Example XML

```xml
<resultMap id="ProductResult" class="Product">
    <result property="id" column="product_id"/>
    <result property="name" column="product_name"/>
    <result property="category" column="category_id" 
            select="GetCategory" resultMap="CategoryResult"/>
</resultMap>
```

## See Also

- [TResultProperty](./TResultProperty.md)
- [TDiscriminator](./TDiscriminator.md)
- [TSubMap](./TSubMap.md)
- [TSqlMapStatement](./TSqlMapStatement.md)

## Category

SqlMap Configuration
