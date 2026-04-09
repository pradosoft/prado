# TMapRouteBehavior

### Directories

[Util](../) > [Behaviors](Behaviors/) > TMapRouteBehavior

**Location:** `framework/Util/Behaviors/TMapRouteBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview

TMapRouteBehavior routes changes to Application Parameters to actual functions to affect change. When a specific parameter changes (or any parameter if no specific key is set), the behavior calls a handler callback. Useful for reacting to parameter changes in real-time.

## Key Properties/Methods

- `__construct($parameter, $handler)` - Constructor takes optional parameter key and a callable handler
- `dyAddItem($key, $value, $callchain)` - Dynamic event for `dyAddItem`; calls handler when parameter matches
- `dyRemoveItem($key, $value, $callchain)` - Dynamic event for `dyRemoveItem`; calls handler with null when parameter is removed
- `getParameter()` / `setParameter($param)` - Gets/sets the parameter key to route

## See Also

- [TMapLazyLoadBehavior](./TMapLazyLoadBehavior.md)
- [TMap](../../Collections/TMap.md)
