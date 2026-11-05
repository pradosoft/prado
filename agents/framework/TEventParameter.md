# TEventParameter

### Directories
[framework](./INDEX.md) / **`TEventParameter`**

## Class Info
**Location:** `framework/TEventParameter.php`
**Namespace:** `Prado`
**Extends:** `TComponent`
**Implements:** `IEventParameter`, `ArrayAccess`

## Overview
Base class for all event parameter objects in Prado. Passed as the `$param` argument to every event handler. Tracks the event name being raised, wraps an arbitrary payload (`Parameter`), tracks whether that payload has been mutated (`ParameterChanged`), and optionally enforces immutability via `ReadOnly` mode.

Also implements `ArrayAccess` so handlers can treat the parameter as an array when `Parameter` is an array or `ArrayAccess`.

## Key Properties

| Property | Type | Since | Description |
|----------|------|-------|-------------|
| `EventName` | `string` | 4.3.0 | Lowercase name of the event being raised; set automatically by `raiseEvent` |
| `Parameter` | `mixed` | 4.3.0 | Payload data; any type |
| `ParameterChanged` | `bool` | 4.3.3 | One-way flag: true once Parameter has been modified; reset by `setEventName` or `resetParameterChanged` |
| `ReadOnly` | `bool` | 4.3.3 | When true, `setParameter`, `offsetSet`, and `offsetUnset` throw `TInvalidOperationException` |
| `ParameterIsArray` | `bool` | 4.3.3 | True if `Parameter` is an array or `ArrayAccess` |

## Constructor

```php
new TEventParameter(mixed $parameter = null, bool $readOnly = false)
```

`ReadOnly` can only be set once, via the constructor (or subclass constructor calls). Attempting to call `setReadOnly` a second time, or calling it from outside the object, throws `TInvalidOperationException`.

## ReadOnly Mode

When `ReadOnly` is `true`:
- `setParameter()` — throws `TInvalidOperationException`
- `offsetSet()` — throws `TInvalidOperationException`
- `offsetUnset()` — throws `TInvalidOperationException`
- Reads (`getParameter`, `offsetGet`, `offsetExists`) — always permitted

`setReadOnly` uses `Prado::isCallingSelf()` to enforce that only the object itself (constructor or subclass method) can set `ReadOnly`.

## ParameterChanged Flag

`ParameterChanged` is a one-way accumulating flag:
- Set to `true` automatically by `setParameter` when the value changes.
- Setting to `false` via `setParameterChanged(false)` is a no-op.
- Reset to `false` by `resetParameterChanged()` or when `setEventName` is called (i.e., at the start of a new event raise).
- Useful for mutable parameter objects (e.g., `TMap`) where the reference doesn't change but the contents do — call `setParameterChanged(true)` manually.

## ArrayAccess

When `Parameter` is an array or `ArrayAccess` instance, the `TEventParameter` itself can be used as an array:

```php
$param = new TEventParameter(['key' => 'value']);
echo $param['key'];         // 'value'
$param['key'] = 'updated';  // sets Parameter['key'], marks ParameterChanged
unset($param['key']);        // unsets, marks ParameterChanged
isset($param['key']);        // false

// If Parameter is null and you offsetSet, Parameter becomes []
$param2 = new TEventParameter();
$param2['foo'] = 'bar';     // Parameter becomes ['foo' => 'bar']
```

## Event Lifecycle Hooks

`TEventParameter` provides no-op stub implementations of `preRaiseEvent` and `postRaiseEvent`. Subclasses that implement [`IEventCycleParameter`](./IEventCycleParameter.md) override these to hook into `raiseEvent`'s lifecycle.

```php
public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction) {}
public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction) {}
```

## Usage

```php
// Simple usage — pass a string payload
$param = new TEventParameter('some data');
$this->raiseEvent('OnSomething', $this, $param);

// Read-only parameter
$param = new TEventParameter(['result' => 42], readOnly: true);

// Detecting mutation in a handler
$param->resetParameterChanged();
$this->raiseEvent('OnFilter', $this, $param);
if ($param->getParameterChanged()) {
	// a handler modified the parameter
}
```

## Subclassing

Most framework events use a dedicated subclass to add typed properties:

```php
class TMyEventParameter extends TEventParameter
{
	private string $_detail = '';

	public function getDetail(): string { return $this->_detail; }
	public function setDetail(string $v): void { $this->_detail = $v; }
}
```

## See Also
- [`IEventCycleParameter`](./IEventCycleParameter.md) — interface for lifecycle hooks
- [`TComponent`](./TComponent.md) — calls `setEventName`, `preRaiseEvent`, `postRaiseEvent` from `raiseEvent`
