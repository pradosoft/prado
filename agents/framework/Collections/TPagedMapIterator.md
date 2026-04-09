# TPagedMapIterator

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TPagedMapIterator](./TPagedMapIterator.md)

**Location:** `framework/Collections/TPagedMapIterator.php`
**Namespace:** `Prado\Collections`

## Overview

Iterator for traversing items in a paged TMap. Used by [TPagedDataSource](./TPagedDataSource.md) to iterate over a specific page of items in a map.

## Implementation

Implements PHP's Iterator interface.

## Constructor

```php
public function __construct(TMap $map, int $startIndex, int $count)
```

- `$map` - The TMap to iterate
- `$startIndex` - Starting index for this page
- `$count` - Number of items to iterate

## Iterator Methods

- `rewind()` - Reset to start of page
- `key()` - Return key of current item
- `current()` - Return item at current position
- `next()` - Advance to next item
- `valid()` - Check if still within page bounds

## Usage

```php
$iterator = new TPagedMapIterator($map, 0, 10);  // First page
foreach ($iterator as $key => $value) {
    // Iterates first 10 items
}
```

## See Also

- [TPagedDataSource](./TPagedDataSource.md) - Uses this iterator
- [TPagedListIterator](./TPagedListIterator.md) - Similar for TList
