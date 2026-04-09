# TSessionIterator

### Directories
[./](../INDEX.md) > [Web](../INDEX.md) > [TSessionIterator](./TSessionIterator.md)

**Location:** `framework/Web/TSessionIterator.php`
**Namespace:** `Prado\Web`

## Overview

TSessionIterator implements the `\Iterator` interface and is used by [THttpSession](./THttpSession.md) to enable iteration over session variables. It allows THttpSession to return a new iterator for traversing session data.

## Key Properties/Methods

- `rewind()` - Rewinds the internal array pointer
- `key()` - Returns the key of the current array element
- `current()` - Returns the current array element
- `next()` - Moves the internal pointer to the next element
- `valid()` - Returns whether there is an element at the current position

## See Also

- [THttpSession](./THttpSession.md)
