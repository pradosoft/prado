# TEventResults

### Directories
[framework](./INDEX.md) / **`TEventResults`**

## Class Info
**Location:** `framework/TEventResults.php`
**Namespace:** `Prado`
**Extends:** [`TEnumerable`](./TEnumerable.md)

## Overview
`TEventResults` is an enumeration of bitmask flags that control how the results of a global `fx*` event are collected and merged. It is used with `TComponent::raiseEvent()` and related infrastructure to specify which return values from listeners are considered and in what order.

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `EVENT_RESULT_FEED_FORWARD` | `1` | Each handler's return value is passed forward as the parameter to the next handler (feed-forward chain). |
| `EVENT_RESULT_FILTER` | `2` | Collects and merges all handler return values. |
| `EVENT_RESULT_ALL` | `4` | Returns all results from all handlers as an array. |
| `EVENT_REVERSE` | `8` | Calls handlers in reverse priority order. |

## See Also

- [`TComponent`](./TComponent.md) — `raiseEvent()` uses these flags for global events
- [`TEnumerable`](./TEnumerable.md) — base class
