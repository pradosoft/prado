# TList

### Directories
[./](../INDEX.md) > [Collections](./INDEX.md) > [TList](./TList.md)

**Location:** `framework/Collections/TList.php`
**Namespace:** `Prado\Collections`
**Extends:** [TComponent](../TComponent.md)
**Implements:** `IteratorAggregate`, `ArrayAccess`, `Countable`

## Overview

Integer-indexed ordered collection. The primary extension point is `insertAt()` and `removeAt()` — subclasses override these rather than `add()`/`remove()`. All collection classes in Prado extend `TComponent` and support the standard property/event system.

## Constructor

```php
new TList($data = null, $readOnly = null)
// $data: initial array or Iterator
// $readOnly: null = mutable, true = read-only
```

## Core Methods

| Method | Description |
|--------|-------------|
| `add($item)` | Append item; returns new index |
| `insertAt($index, $item)` | Insert at position (0-based); shift right |
| `remove($item)` | Remove first occurrence by value |
| `removeAt($index)` | Remove by index; returns removed item |
| `itemAt($index)` | Return item at index |
| `indexOf($item)` | First index of item, or -1 |
| `contains($item)` | bool |
| `clear()` | Remove all items |
| `getCount()` | Integer count |
| `toArray()` | Return PHP array copy |
| `copyFrom($data)` | Replace contents with array/iterable |
| `mergeWith($data)` | Append array/iterable to existing |

## Read-Only Mode

```php
$list->setReadOnly(true);
$list->getReadOnly();  // bool
```

Any mutating call throws `TInvalidOperationException` when read-only.

## Array-Style Access

```php
$list[] = $item;           // append
$list[$i] = $item;        // replace at index (must be 0 <= i <= count)
unset($list[$i]);          // removeAt
isset($list[$i]);          // itemAt check
foreach ($list as $i => $item) {}
count($list);
```

## Extending TList

Override `insertAt()` and `removeAt()` to intercept add/remove:
```php
class TypedList extends TList
{
    public function insertAt($index, $item): void
    {
        if (!($item instanceof MyType)) {
            throw new TInvalidDataTypeException('...');
        }
        parent::insertAt($index, $item);
    }
}
```

## Related Classes

| Class | Purpose |
|-------|---------|
| [TPriorityList](./TPriorityList.md) | TList with numeric priority ordering |
| [TWeakList](./TWeakList.md) | TList backed by WeakReferences |
| [TListItemCollection](./TListItemCollection.md) | Typed list of `TListItem` UI objects |
| [TPagedList](./TPagedList.md) | Lazily-paged list with `OnFetchData` |
| [TQueue](./TQueue.md) | FIFO using `enqueue()`/`dequeue()` |
| [TStack](./TStack.md) | LIFO using `push()`/`pop()` |
