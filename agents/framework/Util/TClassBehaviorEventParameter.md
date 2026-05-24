# Util/TClassBehaviorEventParameter

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TClassBehaviorEventParameter`**

## Class Info
**Location:** `framework/Util/TClassBehaviorEventParameter.php`
**Namespace:** `Prado\Util`
**Extends:** [`TEventParameter`](../TEventParameter.md)
**Since:** 3.2.3

## Overview
`TClassBehaviorEventParameter` carries the parameters for class-level behavior attachment and detachment events. It is the event parameter passed with `fxAttachClassBehavior` and `fxDetachClassBehavior` global events, allowing behaviors and listeners to inspect the class, behavior name, behavior instance, and priority involved in the operation.

## Constructor

`__construct($class, $name, $behavior, $priority)`

All four values are stored and exposed as read-only properties.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Class` | `mixed` | The class that is receiving or losing the behavior. |
| `Name` | `string` | The name under which the behavior is registered. |
| `Behavior` | `mixed` | The behavior instance being attached or detached. |
| `Priority` | `mixed` | The priority at which the behavior has been registered. |

## See Also

- [`TEventParameter`](../TEventParameter.md) — parent class
- [`TComponent`](../TComponent.md) — raises `fxAttachClassBehavior` / `fxDetachClassBehavior`
