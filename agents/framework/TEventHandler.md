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

## Special Features
- When TEventHandler data is an array and the 3rd parameter $data of __invoke is also an array, an `array_replace` combines the data
- Supports nesting of TEventHandlers with children taking precedence in data combining