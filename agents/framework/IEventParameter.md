# IEventParameter

### Directories
[framework](./INDEX.md) / **`IEventParameter`**

## Interface Info
**Location:** `framework/IEventParameter.php`
**Namespace:** `Prado`

## Overview
`IEventParameter` defines the minimal contract for event parameter objects that expose the name of the event they accompany. Infrastructure code can implement this interface to inspect which event is in flight without depending on a concrete parameter type.

## Interface Methods

| Method | Description |
|--------|-------------|
| `getEventName(): string` | Returns the name of the event being raised. |
| `setEventName(string $value)` | Sets the name of the event. |

## See Also

- [`TEventParameter`](./TEventParameter.md) — base event parameter class
- [`IEventCycleParameter`](./IEventCycleParameter.md) — extends this interface with lifecycle control
