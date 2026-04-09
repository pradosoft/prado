# TPagedListIterator

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TPagedListIterator](./TPagedListIterator.md)

**Location:** `framework/Collections/TPagedListIterator.php`
**Namespace:** `Prado\Collections`

## Overview

Iterator for traversing items in a paged TList. Used by [TPagedDataSource](./TPagedDataSource.md) to iterate over a specific page of items.

## Implementation

Implements PHP's Iterator interface.

## Constructor

```php
public function __construct(TList $list, int $startIndex, int $count)
```

- `$list` - The TList to iterate
- `$startIndex` - Starting index for this page
- `$count` - Number of items to iterate

## Iterator Methods

- `rewind()` - Reset to start of page
- `key()` - Return position within page (0-based)
- `current()` - Return item at current position
- `next()` - Advance to next item
- `valid()` - Check if still within page bounds

## Usage

```php
$iterator = new TPagedListIterator($list, 10, 10);  // Page 2
foreach ($iterator as $item) {
    // Iterates items 10-19
}
```

## See Also

- [TPagedDataSource](./TPagedDataSource.md) - Uses this iterator
- [TPagedMapIterator](./TPagedMapIterator.md) - Similar for TMap
