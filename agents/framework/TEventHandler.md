# TEventHandler

### Directories
[framework](./INDEX.md) / **`TEventHandler`**

## Class Info
**Location:** `framework/TEventHandler.php`
**Namespace:** `Prado`

## Overview
TEventHandler is a helper class for passing specific data along with an event handler. It's invokable and will pass invoked method arguments forward to the managed handler with the specified data.

## Key Features
- Supports data injection into event handlers
- Implements IPriorityProperty and IWeakRetainable interfaces
- Supports ArrayAccess and Countable interfaces
- Uses WeakReference for better PHP garbage collection
- Allows nested TEventHandlers with data combining

## Usage Example
```php
$handler = new TEventHandler([$object, 'myHandler'], ['key' => 'data']);
$handler($sender, $param); // Invokable
$component->attachEventHandler('onMyEvent', $handler, $priority);
```

## Constructor
```php
public function __construct(mixed $handler, mixed $data = null)
```
- Accepts a callable handler and optional data
- Converts objects to WeakReference except for Closure and IWeakRetainable

## Methods
- `__invoke()` - Calls the handler with specified data
- `getHandler()` - Gets the managed handler
- `getData()` - Returns the associated data
- `setData()` - Sets the data associated with the event handler
- `hasHandler()` - Checks if the managed handler is still valid
- `isSameHandler()` - Checks if the item is the same as the handler
- `getHandlerObject()` - Gets the object of the event handler if there is one
- `hasWeakObject()` - Checks if the object contains any WeakReference objects

## ArrayAccess and Countable
`resolveOffset()` (protected) resolves the offset for `offsetExists()`, `offsetGet()`, `offsetSet()`, and `offsetUnset()`. A numeric offset (int, float, or numeric string) is truncated to an integer, so `2`, `'2'`, `2.0`, and `2.9` are the same offset.

| Offset | Item | `isset` / `offsetExists()` | `offsetGet()` | `offsetSet()` / `offsetUnset()` |
|---|---|---|---|---|
| `null` | the handler | true | `getHandler()` | throws `TInvalidOperationException` |
| 0 | the handler, or element 0 of an array callable | true | the handler or element 0 | throws `TInvalidOperationException` |
| 1, array callable | element 1 of the callable | true | element 1 | throws `TInvalidOperationException` |
| 1, any other handler | none | false | null | throws `TInvalidOperationException` |
| 2 | the data | true | `getData()` | sets the data; unset sets it to null |
| any other value | none | false | throws `TInvalidDataValueException` | throws `TInvalidDataValueException` |

- "Any other value" covers numbers outside 0 to 2 after truncation, `NAN`, `INF`, non-numeric strings, and bools.
- `offsetExists()` is independent of the value: the data offset exists when the data is null, and the handler offsets exist after the WeakReference object is collected. `isset()`, `??`, and `empty()` never throw.
- `offsetGet()` returns null for the handler offsets (null, 0, 1) once the WeakReference object is collected.
- `count()` / `getCount()` is 3 for an array callable and 2 for any other handler, matching the offsets that exist besides null.

## Special Features
- When TEventHandler data is an array and the 3rd parameter $data of __invoke is also an array, an `array_replace` combines the data
- Supports nesting of TEventHandlers with children taking precedence in data combining