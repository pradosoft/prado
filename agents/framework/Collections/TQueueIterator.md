# Collections/TQueueIterator

### Directories
[framework](../INDEX.md) / [Collections](./INDEX.md) / **`TQueueIterator`**

## Class Info
**Location:** `framework/Collections/TQueueIterator.php`
**Namespace:** `Prado\Collections`

## Overview


Iterator for traversing items in a TQueue. Used internally by [TQueue](./TQueue.md).

## Implementation

Implements PHP's Iterator interface with array reference passthrough.

## Constructor

```php
public function __construct(array &$data)
```

Takes an array reference to the queue's internal data.

## Iterator Methods

- `rewind()` - Reset to beginning
- `key()` - Return current index
- `current()` - Return item at current position
- `next()` - Advance index
- `valid()` - Check if still within bounds

## Usage

Typically not used directly:

```php
$queue = new TQueue();
$queue->enqueue('a');
$queue->enqueue('b');

foreach ($queue as $item) {  // Uses TQueueIterator internally
    echo $item;
}
```

## See Also

- [TQueue](./TQueue.md) - The queue using this iterator
