# Util/Behaviors/TMapRouteBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TMapRouteBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TMapRouteBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TMapRouteBehavior routes changes to Application Parameters to actual functions to affect change. When a specific parameter changes (or any parameter if no specific key is set), the behavior calls a handler callback. Useful for reacting to parameter changes in real-time.

## Key Properties/Methods

- `__construct($parameter, $handler)` - Constructor takes optional parameter key and a callable handler
- `dyAddItem($key, $value, $callchain)` - Dynamic event for `dyAddItem`; calls handler when parameter matches
- `dyRemoveItem($key, $value, $callchain)` - Dynamic event for `dyRemoveItem`; calls handler with null when parameter is removed
- `getParameter()` / `setParameter($param)` - Gets/sets the parameter key to route
- `routeChange($key, $value)` - Calls the handler for a change; shared by the `dy` methods and the key index (4.4.0)
- `getIsIndexable()` - True unless a subclass overrides `dyAddItem` or `dyRemoveItem` (4.4.0)
- `isRoutedKey($key)` - Whether a map key is the routed parameter; keys match by string form, so `'10'` matches the key `10` and not `'1e1'` (4.4.0)

## Handler calls

| Parameter | Change to a key | Call |
|---|---|---|
| key `P` | `P` added | `handler($value)` |
| key `P` | `P` removed | `handler(null)` |
| null | any key added, `''` included | `handler($key, $value)` |
| null | any key removed | `handler($key, null)` |

A change to a final key of [`TApplicationParameters`](../../TApplicationParameters.md) throws before `dyAddItem` or `dyRemoveItem`; the handler is not called.

## Key index

On a plain `TMap`, every `dyAddItem`/`dyRemoveItem` builds a `TCallChain` of every route on the map, and each route checks the key itself. The cost of a change grows with all routes, about 0.85 µs each, and each route adds about 7 stack frames: Xdebug's 512-frame limit stops a write at 72 routes.

A map that implements `IMapRouteIndex` (4.4.0; [`TApplicationParameters`](../../TApplicationParameters.md)) indexes routes by key:

| Event | Index action |
|---|---|
| `attach()` | `attachKeyRoute($this)` when `getIsIndexable()` |
| `setParameter()` while attached | `attachKeyRoute($this)` moves the route |
| `detach()` | `detachKeyRoute($this)` |

The map leaves indexed routes out of its `dyAddItem` and `dyRemoveItem` chains and calls `routeChange()` on the enabled routes for the changed key, then on the all-key routes, each in attach order, after the chain. Measured with `XDEBUG_MODE=off`, a write to an unwatched key:

| Routes | `TMap` | `TApplicationParameters` |
|---|---|---|
| 0 | 0.22 µs | 0.36 µs |
| 10 | 9.4 µs | 0.56 µs |
| 100 | 86 µs | 0.54 µs |
| 2000 | 1840 µs | 0.54 µs |

- **Ordering** — indexed routes run after the remaining `dy` chain, not interleaved by behavior priority.
- **Subclasses** — a subclass that overrides `dyAddItem` or `dyRemoveItem` stays in the `dy` chain.
- **Clone and unserialize** — the map resets its index; each behavior registers again as it re-attaches.

## See Also

- [TMapLazyLoadBehavior](./TMapLazyLoadBehavior.md)
- [TMap](../../Collections/TMap.md)
