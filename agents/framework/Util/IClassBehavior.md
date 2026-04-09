# IClassBehavior

### Directories

[Util](../) > IClassBehavior

**Location:** `framework/Util/IClassBehavior.php`
**Namespace:** `Prado\Util`

## Overview

Interface for stateless class-wide behaviors. Each instance can attach to multiple owners. Implements `[IBaseBehavior](IBaseBehavior.md)`.

## Extends

- `IBaseBehavior`

## Key Characteristics

- **Stateless**: One instance serves multiple owners
- **Owner injection**: Methods receive owner as first parameter
- **Dynamic events**: `dy*` methods receive `(owner, defaultReturn, ..., [TCallChain](TCallChain.md))`

## Example

```php
public function dyFilteringBehavior($owner, $defaultReturnData, $secondParam, ?[TCallChain](TCallChain.md) $chain = null)
{
    $defaultReturnData = $owner->processText($defaultReturnData, $secondParam);
    if ($chain) {
        return $chain->dyFilteringBehavior($defaultReturnData, $secondParam);
    }
    return $defaultReturnData;
}
```

## See Also

- `[TClassBehavior](TClassBehavior.md)` - Implementation
- `[IBehavior](IBehavior.md)` - Stateful per-instance behavior
- `[IBaseBehavior](IBaseBehavior.md)` - Base interface
