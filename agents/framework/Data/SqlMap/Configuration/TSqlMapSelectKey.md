# Data/SqlMap/Configuration/TSqlMapSelectKey

### Directories
[framework](../../../INDEX.md) / [Data](../../INDEX.md) / [SqlMap](../INDEX.md) / [Configuration](./INDEX.md) / **`TSqlMapSelectKey`**

## Class Info
**Location:** `framework/Data/SqlMap/Configuration/TSqlMapSelectKey.php`
**Namespace:** `Prado\Data\SqlMap\Configuration`

## Overview
`Prado\Data\SqlMap\Configuration\TSqlMapSelectKey`

Select key configuration for retrieving generated primary keys.

## Description

`TSqlMapSelectKey` corresponds to the `<selectKey>` element nested inside an `<insert>`. It configures how to retrieve database-generated primary keys after an insert.

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `ResultClass` | `string` | The PHP type for the generated key |
| `Type` | `string` | `"pre"` or `"post"` - when to execute the select |
| `Property` | `string` | The property name to receive the key value |

## Usage

```xml
<insert id="InsertProduct" parameterClass="Product">
    <selectKey resultClass="int" type="post">
        SELECT last_insert_id()
    </selectKey>
    INSERT INTO products (name, price) VALUES (#name#, #price#)
</insert>

<!-- Or using a sequence -->
<insert id="InsertProduct" parameterClass="Product">
    <selectKey resultClass="int" type="pre" property="id">
        SELECT product_seq.nextval FROM dual
    </selectKey>
    INSERT INTO products (id, name, price) VALUES (#id#, #name#, #price#)
</insert>
```

## See Also

- [TSqlMapInsert](./TSqlMapInsert.md)

## Category

SqlMap Configuration
