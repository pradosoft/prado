# Collections/TPagedListPageChangedEventParameter

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TPagedListPageChangedEventParameter`**

## Class Info
**Location:** `framework/Collections/TPagedListPageChangedEventParameter.php`
**Namespace:** `Prado\Collections`

## Overview
Event parameter for the [TPagedList](./TPagedList.md)::OnPageIndexChanged event, raised in managed and custom paging. Provides the previous page index when the page changes.

## Properties

### OldPageIndex

```php
public function getOldPageIndex(): int
```

The page index before the change. The first page fetched in custom paging has `-1`.

## Usage

```php
$list->attachEventHandler('OnPageIndexChanged', function($sender, $param) {
    $previousPage = $param->getOldPageIndex();
    $currentPage = $list->getCurrentPageIndex();
});
```

## See Also

- [TPagedList](./TPagedList.md) - The list using this event parameter
