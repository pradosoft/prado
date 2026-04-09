# TLazyLoadList

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [DataMapper](./INDEX.md) > [TLazyLoadList](./TLazyLoadList.md)

`Prado\Data\SqlMap\DataMapper\TLazyLoadList`

Proxy list that defers loading of nested collections until first access.

## Description

`TLazyLoadList` is a transparent proxy list that defers loading of a nested collection until first access. It holds the statement ID, parameters, and target object/property.

Implements `ArrayAccess` and `Countable`.

## Usage

```php
// In XML mapping
<result property="orders" column="customer_id" 
        select="GetOrdersByCustomer" resultMap="OrderResult"/>
```

The `orders` property returns a `TLazyLoadList` that loads the orders only when accessed.

## Key Methods

See `ArrayAccess` and `Countable` interfaces.

## See Also

- [TObjectProxy](./TObjectProxy.md)

## Category

SqlMap DataMapper
