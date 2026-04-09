# TSqlMapInsert

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [Configuration](./INDEX.md) > [TSqlMapInsert](./TSqlMapInsert.md)

`Prado\Data\SqlMap\Configuration\TSqlMapInsert`

Insert statement configuration.

Inherits from [`TSqlMapStatement`](./TSqlMapStatement.md).

## Description

`TSqlMapInsert` corresponds to the `<insert>` element. It extends the base statement with support for returning primary keys via `<selectKey>` elements.

## Key Properties

### `SelectKey`

The [`TSqlMapSelectKey`](./TSqlMapSelectKey.md) configuration for retrieving generated keys.

## Usage

```xml
<insert id="InsertProduct" parameterClass="Product">
    INSERT INTO products (name, price) VALUES (#name#, #price#)
    <selectKey resultClass="int" type="post">
        SELECT last_insert_id()
    </selectKey>
</insert>
```

## See Also

- [TSqlMapStatement](./TSqlMapStatement.md)
- [TSqlMapSelectKey](./TSqlMapSelectKey.md)

## Category

SqlMap Configuration
