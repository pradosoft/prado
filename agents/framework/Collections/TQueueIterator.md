# Collections/TQueueIterator

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TQueueIterator`**

## Class Info
**Location:** `framework/Collections/TQueueIterator.php`
**Namespace:** `Prado\Collections`

## Overview


Iterator that traverses an array by reference. [TQueue](./TQueue.md) returns an `\ArrayIterator` and does not use this class.

## Implementation

Implements PHP's Iterator interface with array reference passthrough.

## Constructor

```php
public function __construct(array &$data)
```

Takes the array to traverse by reference.

## Iterator Methods

- `rewind()` - Reset to beginning
- `key()` - Return current index
- `current()` - Return item at current position
- `next()` - Advance index
- `valid()` - Check if still within bounds

## Usage

```php
$data = ['a', 'b'];
foreach (new TQueueIterator($data) as $item) {
    echo $item;
}
```

## See Also

- [TQueue](./TQueue.md) - FIFO queue; iterates with `\ArrayIterator`
