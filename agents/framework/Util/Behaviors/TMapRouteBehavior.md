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
- `isRoutedKey($key)` - Whether a map key is the routed parameter; keys match by string form, so `'10'` matches the key `10` and not `'1e1'` (4.4.0)

## Handler calls

| Parameter | Change to a key | Call |
|---|---|---|
| key `P` | `P` added | `handler($value)` |
| key `P` | `P` removed | `handler(null)` |
| null | any key added, `''` included | `handler($key, $value)` |
| null | any key removed | `handler($key, null)` |

A change to a final key of [`TApplicationParameters`](../../TApplicationParameters.md) throws before `dyAddItem` or `dyRemoveItem`; the handler is not called.

## See Also

- [TMapLazyLoadBehavior](./TMapLazyLoadBehavior.md)
- [TMap](../../Collections/TMap.md)
