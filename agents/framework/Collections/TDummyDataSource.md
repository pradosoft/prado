# TDummyDataSource

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TDummyDataSource](./TDummyDataSource.md)

**Location:** `framework/Collections/TDummyDataSource.php`
**Namespace:** `Prado\Collections`

## Overview

TDummyDataSource implements a dummy data source with a specified number of virtual data items. Used for testing or when you need a data source that returns a fixed number of empty items.

## Key Features

- Virtual data source with configurable item count
- Implements Iterator and Countable
- Returns null for each item iteration

## Usage

```php
$dummy = new TDummyDataSource(5);

foreach ($dummy as $index => $item) {
    echo "$index: $item\n";  // 0: null, 1: null, etc.
}

echo count($dummy);  // 5
```

## Iterator

Uses [TDummyDataSourceIterator](./TDummyDataSourceIterator.md) internally to iterate over virtual items.

## See Also

- [TDummyDataSourceIterator](./TDummyDataSourceIterator.md) - Iterator for dummy data
- [TPagedDataSource](./TPagedDataSource.md) - Real paged data source
