# TQueue

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TQueue](./TQueue.md)

**Location:** `framework/Collections/TQueue.php`
**Namespace:** `Prado\Collections`

## Overview

TQueue implements a FIFO (First-In-First-Out) queue data structure. Items are added to the end and removed from the front.

## Inheritance

Extends [TComponent](../TComponent.md) and implements `IteratorAggregate`, `Countable`.

## Key Features

- FIFO queue operations
- `enqueue()` / `dequeue()` / `peek()`
- `contains()` to check for item existence
- Iterable via foreach

## Usage

```php
$queue = new TQueue();

// Add items
$queue->enqueue('first');
$queue->enqueue('second');
$queue->enqueue('third');

// Check
echo $queue->getCount();  // 3
echo $queue->peek();      // 'first' (without removing)

// Remove items
echo $queue->dequeue();  // 'first'
echo $queue->dequeue();  // 'second'

$queue->contains('third');  // true
```

## Methods

### enqueue

```php
public function enqueue(mixed $item): void
```

Adds an item to the back of the queue.

### dequeue

```php
public function dequeue(): mixed
```

Removes and returns the item at the front. Throws exception if empty.

### peek

```php
public function peek(): mixed
```

Returns the front item without removing it. Throws exception if empty.

### contains

```php
public function contains(mixed $item): bool
```

Checks if an item exists in the queue.

### clear

```php
public function clear(): void
```

Removes all items.

### toArray

```php
public function toArray(): array
```

Returns all items as an array.

## See Also

- [TQueueIterator](./TQueueIterator.md) - Iterator for the queue
- [TStack](./TStack.md) - LIFO stack
