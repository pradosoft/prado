# TDummyDataSourceIterator

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TDummyDataSourceIterator](./TDummyDataSourceIterator.md)

**Location:** `framework/Collections/TDummyDataSourceIterator.php`
**Namespace:** `Prado\Collections`

## Overview

TDummyDataSourceIterator implements the PHP Iterator interface for traversing dummy/virtual data items. Used internally by [TDummyDataSource](./TDummyDataSource.md).

## Key Features

- Returns null for each item
- Tracks internal index
- Valid count determines iteration length

## Implementation

Implements standard Iterator methods:
- `rewind()` - Reset to beginning
- `key()` - Return current index
- `current()` - Return null
- `next()` - Advance index
- `valid()` - Check if still within count

## See Also

- [TDummyDataSource](./TDummyDataSource.md) - Parent data source
