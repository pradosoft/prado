# TStack

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TStack](./TStack.md)

**Location:** `framework/Collections/TStack.php`
**Namespace:** `Prado\Collections`

## Overview

TStack implements a LIFO (Last-In-First-Out) stack data structure. Items are added to and removed from the top.

## Inheritance

Extends [TComponent](../TComponent.md) and implements `IteratorAggregate`, `Countable`.

## Key Features

- LIFO stack operations
- `push()` / `pop()` / `peek()`
- `contains()` to check for item existence
- Iterable via foreach

## Usage

```php
$stack = new TStack();

// Add items
$stack->push('first');
$stack->push('second');
$stack->push('third');

// Check
echo $stack->getCount();  // 3
echo $stack->peek();      // 'third' (without removing)

// Remove items
echo $stack->pop();  // 'third'
echo $stack->pop();  // 'second'

$stack->contains('first');  // true
```

## Methods

### push

```php
public function push(mixed $item): void
```

Pushes an item onto the stack.

### pop

```php
public function pop(): mixed
```

Pops and returns the top item. Throws exception if empty.

### peek

```php
public function peek(): mixed
```

Returns the top item without removing it. Throws exception if empty.

### contains

```php
public function contains(mixed $item): bool
```

Checks if an item exists in the stack.

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

- [TQueue](./TQueue.md) - FIFO queue
