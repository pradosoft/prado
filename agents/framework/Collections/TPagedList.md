# Collections/TPagedList

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPagedList`**

## Class Info
**Location:** `framework/Collections/TPagedList.php`
**Namespace:** `Prado\Collections`

## Overview
TPagedList implements a list with built-in paging functionality. Supports both managed paging (all data in memory) and custom paging (lazy loading).

## Inheritance

Extends [TList](./TList.md)

## Key Features

- Built-in pagination
- Managed or custom paging modes
- `OnFetchData` event for custom paging
- `OnPageIndexChanged` event carrying the previous page index

## Properties

### PageSize

```php
public function setPageSize(int $value): void
```

Number of items per page.

### CurrentPageIndex

```php
public function setCurrentPageIndex(int $value): void
public function getCurrentPageIndex(): int
```

Zero-based current page index.

| Mode | Before any page is selected |
|---|---|
| Managed (`CustomPaging` false) | `0`; `Count`, `toArray()`, iteration, and `itemAt()` read page 0 |
| Custom (`CustomPaging` true) | `-1`; the first `gotoPage(0)` or `nextPage()` raises `OnFetchData` |

`gotoPage()` with the current page index returns it without raising an event or fetching data.

### PageCount

```php
public function getPageCount(): int
```

Total number of pages.

### CustomPaging

```php
public function setCustomPaging(bool $value): void
```

Enable custom paging for lazy loading.

## Managed Paging Mode

```php
$list = new TPagedList($allData);
$list->setPageSize(10);
$list->setCurrentPageIndex(2);  // Go to page 3
```

## Custom Paging Mode

```php
$list = new TPagedList();
$list->setCustomPaging(true);
$list->AttachEventHandler('OnFetchData', function($sender, $param) {
    $offset = $param->Offset;
    $limit = $param->Limit;
    $param->Data = fetchDataFromDatabase($offset, $limit);
});
```

## Events

### OnFetchData

Raised when custom paging is enabled and page changes. Handler receives [TPagedListFetchDataEventParameter](./TPagedListFetchDataEventParameter.md).

### OnPageIndexChanged

Raised when the page index changes, in both modes. Handler receives [TPagedListPageChangedEventParameter](./TPagedListPageChangedEventParameter.md) with `OldPageIndex`. The first custom-paging fetch has an `OldPageIndex` of `-1`.

## See Also

- [TPagedDataSource](./TPagedDataSource.md) - Data source with paging
- [TPagedListFetchDataEventParameter](./TPagedListFetchDataEventParameter.md) - Event parameter for OnFetchData
- [TPagedListPageChangedEventParameter](./TPagedListPageChangedEventParameter.md) - Event parameter for OnPageIndexChanged
