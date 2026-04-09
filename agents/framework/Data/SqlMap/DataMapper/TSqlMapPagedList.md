# TSqlMapPagedList

### Directories

[./](../../INDEX.md) > [Data](../../INDEX.md) > [SqlMap](../INDEX.md) > [DataMapper](./INDEX.md) > [TSqlMapPagedList](./TSqlMapPagedList.md)

`Prado\Data\SqlMap\DataMapper\TSqlMapPagedList`

Paged result list for SqlMap queries.

Inherits from `TPagedList`.

## Description

`TSqlMapPagedList` implements a list with paging functionality that retrieves data from a SqlMap statement. It fetches the current, previous, and next pages at once (3x page size) to enable navigation.

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `PageSize` | `int` | Number of records per page |
| `PageIndex` | `int` | Current page number (0-based) |
| `PageCount` | `int` | Total number of pages |

## Usage

```php
$pagedProducts = $sqlmap->queryForPagedList('GetProducts', null, 10, 0);

foreach ($pagedProducts as $product) {
    // ...
}

// Navigate pages
$pagedProducts->gotoPage(2);
```

## See Also

- [TSqlMapGateway](../TSqlMapGateway.md)
- `Prado\Collections\TPagedList`

## Category

SqlMap DataMapper
