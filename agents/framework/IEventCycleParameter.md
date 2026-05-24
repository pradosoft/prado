# IEventCycleParameter

### Directories
[framework](./INDEX.md) / **`IEventCycleParameter`**

## Class Info
**Location:** `framework/IEventCycleParameter.php`
**Namespace:** `Prado`
**Extends:** [`IEventParameter`](./IEventParameter.md)
**Since:** 4.3.3

## Overview
Interface for event parameters that want to participate in the event raising lifecycle. When a parameter object implements this interface, [`TComponent::raiseEvent`](./TComponent.md) automatically calls `preRaiseEvent` before any handlers run and `postRaiseEvent` after all handlers complete.

Use cases:
- Logging or tracing event execution
- Validating or transforming event context before handlers run
- Aggregating or analyzing handler responses
- Implementing event caching or optimization strategies

## How It Works

[`TComponent::raiseEvent`](./TComponent.md) checks whether the parameter implements this interface (or `isa(IEventCycleParameter::class)` for behavior-wrapped components):

1. `setEventName($name)` is called on the parameter (from `IEventParameter`).
2. **`preRaiseEvent()`** is called with the full event context.
3. All registered event handlers execute in priority order.
4. **`postRaiseEvent()`** is called with the aggregated responses.

## Interface Methods

### `preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)`

Called immediately before any event handlers run.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$name` | `string` | Event name (lowercase) |
| `$sender` | `mixed` | Object raising the event |
| `$param` | `TEventParameter` | The parameter instance itself |
| `$responsetype` | `?int` | Response tabulation mode (see `TEventResults` constants) |
| `$postfunction` | `?callable` | Optional per-handler response filter |

### `postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)`

Called after all event handlers have completed.

| Parameter | Type | Description |
|-----------|------|-------------|
| `$responses` | `array` | Aggregated responses from all handlers |
| `$name` | `string` | Event name (lowercase) |
| `$sender` | `mixed` | Object that raised the event |
| `$param` | `TEventParameter` | The parameter instance itself |
| `$responsetype` | `?int` | Response tabulation mode used |
| `$postfunction` | `?callable` | Post-processing function that was used |

## Usage

```php
class TLoggingEventParameter extends TEventParameter implements IEventCycleParameter
{
	public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
	{
		Prado::log("Event {$name} raised by " . $sender::class, TLogger::INFO, self::class);
	}

	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
	{
		$count = is_array($responses) ? count($responses) : 0;
		Prado::log("Event {$name} completed with {$count} responses", TLogger::INFO, self::class);
	}
}
```

## Relationship to TEventParameter

[`TEventParameter`](./TEventParameter.md) provides stub implementations of `preRaiseEvent` and `postRaiseEvent` (doing nothing). Subclasses implement `IEventCycleParameter` directly when they need the lifecycle hooks.

## See Also
- [`TEventParameter`](./TEventParameter.md) — base event parameter class with stub lifecycle methods
- [`TComponent`](./TComponent.md) — calls these hooks inside `raiseEvent`
- `TEventResults` — constants controlling response tabulation
