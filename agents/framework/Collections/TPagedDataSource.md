# Collections/TPagedDataSource

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPagedDataSource`**

## Class Info
**Location:** `framework/Collections/TPagedDataSource.php`
**Namespace:** `Prado\Collections`

## Overview
TPagedDataSource implements a paged data source for data-bound controls. It wraps a data source and provides pagination functionality for controls like [TDataGrid](../Web/UI/WebControls/TDataGrid.md) and [TDataList](../Web/UI/WebControls/TDataList.md).

## Key Features

- Paginates data from any iterable source
- Supports custom paging mode
- Implements Iterator and Countable

## Properties

### DataSource

```php
public function getDataSource(): mixed
public function setDataSource(mixed $value): void
```

The underlying data source (array, [TList](./TList.md), [TMap](./TMap.md), or Traversable).

### PageSize

```php
public function getPageSize(): int
public function setPageSize(int $value): void
```

Number of items per page. Default is 10.

### CurrentPageIndex

```php
public function getCurrentPageIndex(): int
public function setCurrentPageIndex(int $value): void
```

Zero-based index of the current page. A negative index is 0. The index has no upper limit because the data source may be set later. A page past the last page is empty: `Count` is 0, iteration yields nothing, and `IsLastPage` is true. `TDataBoundControl::dataBind()` moves such an index to the last page once the data is attached.

With paging on, `Count` matches iteration. Under custom paging the data source holds only the current page, so a short page counts its own items.

### AllowPaging

```php
public function getAllowPaging(): bool
public function setAllowPaging(bool $value): void
```

Whether paging is enabled. With paging off, `IsFirstPage` and `IsLastPage` are both true.

## Usage

```php
$pagedSource = new TPagedDataSource();
$pagedSource->setDataSource($myArray);
$pagedSource->setAllowPaging(true);
$pagedSource->setPageSize(10);
$pagedSource->setCurrentPageIndex(2);

foreach ($pagedSource as $item) {
    // Iterates only items on page 3
}
```

## See Also

- [TPagedList](./TPagedList.md) - Self-paging list implementation
- [TPagedListIterator](./TPagedListIterator.md) - Iterator for paged lists
- [TPagedMapIterator](./TPagedMapIterator.md) - Iterator for paged maps
