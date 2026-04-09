# TPagedListPageChangedEventParameter

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TPagedListPageChangedEventParameter](./TPagedListPageChangedEventParameter.md)

**Location:** `framework/Collections/TPagedListPageChangedEventParameter.php`
**Namespace:** `Prado\Collections`

## Overview

Event parameter for the [TPagedList](./TPagedList.md)::OnPageChanged event. Provides the previous page index when the page changes.

## Properties

### OldPageIndex

```php
public function getOldPageIndex(): int
```

The page index before the change.

## Usage

```php
$list->attachEventHandler('OnPageChanged', function($sender, $param) {
    $previousPage = $param->getOldPageIndex();
    $currentPage = $list->getCurrentPageIndex();
});
```

## See Also

- [TPagedList](./TPagedList.md) - The list using this event parameter
