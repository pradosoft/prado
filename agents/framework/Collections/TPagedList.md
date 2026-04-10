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

### OnPageChanged

Raised when the page index changes.

## See Also

- [TPagedDataSource](./TPagedDataSource.md) - Data source with paging
- [TPagedListFetchDataEventParameter](./TPagedListFetchDataEventParameter.md) - Event parameter for OnFetchData
