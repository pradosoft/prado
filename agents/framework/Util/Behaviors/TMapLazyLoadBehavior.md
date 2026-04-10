# Util/Behaviors/TMapLazyLoadBehavior

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Behaviors](./INDEX.md) / **`TMapLazyLoadBehavior`**

## Class Info
**Location:** `framework/Util/Behaviors/TMapLazyLoadBehavior.php`
**Namespace:** `Prado\Util\Behaviors`

## Overview
TMapLazyLoadBehavior handles Application Parameters when there is no parameter key found. This allows for lazy loading of parameters. When a key is not found in the [TMap](../../Collections/TMap.md) or [TPriorityMap](../../Collections/TPriorityMap.md), the behavior calls a handler callback to provide the value on-demand.

## Key Properties/Methods

- `__construct($handler)` - Constructor takes a callable handler that receives the key and returns the value
- `dyNoItem($value, $key, $callchain)` - Dynamic event handler for `dyNoItem`; calls the handler with the key and returns the value through the chain

## See Also

- [TMapRouteBehavior](./TMapRouteBehavior.md)
- [TMap](../../Collections/TMap.md)
