# IBehavior

### Directories

[Util](../) > IBehavior

**Location:** `framework/Util/IBehavior.php`
**Namespace:** `Prado\Util`

## Overview

Interface for stateful per-instance behaviors. Each instance attaches to exactly one owner component. Implements `[IBaseBehavior](IBaseBehavior.md)`.

## Extends

- `IBaseBehavior`

## Key Methods

| Method | Description |
|--------|-------------|
| `getOwner(): ?object` | Returns the owning component (WeakReference dereferenced) |

## Differences from IClassBehavior

- **Stateful**: Each instance has one owner and stores per-instance state
- **No owner injection**: Methods called on owner don't receive owner as first parameter
- **Dynamic events**: `dy*` methods receive `[TCallChain](TCallChain.md)` as last parameter

## Example

```php
public function dyFilteringBehavior($defaultReturnData, $secondParam, ?[TCallChain](TCallChain.md) $chain = null)
{
    $defaultReturnData = $this->getOwner()->processText($defaultReturnData, $secondParam);
    if ($chain) {
        return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
    }
    return $defaultReturnData;
}
```

## See Also

- `[TBehavior](TBehavior.md)` - Implementation
- `[IClassBehavior](IClassBehavior.md)` - Stateless class-wide behavior
- `[IBaseBehavior](IBaseBehavior.md)` - Base interface
