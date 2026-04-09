# TAuthorizationRuleCollection

### Directories

[./](../INDEX.md) > [Security](./INDEX.md) > [TAuthorizationRuleCollection](./TAuthorizationRuleCollection.md)

**Location:** `framework/Security/TAuthorizationRuleCollection.php`
**Namespace:** `Prado\Security`

## Overview

An ordered collection of [TAuthorizationRule](./TAuthorizationRule.md) objects. Evaluates rules in priority order; first matching rule wins.

Extends [TPriorityList](../Collections/TPriorityList.md) - rules are sorted by priority.

## Key Methods

| Method | Description |
|--------|-------------|
| `isUserAllowed(IUser $user, string $verb, string $ip, array $extra = null): bool` | Returns true if user is allowed based on rules |
| `insertAt(int $index, TAuthorizationRule $item)` | Adds rule at position (validates type) |

## See Also

- [TAuthorizationRule](./TAuthorizationRule.md) - Individual rule
- [TAuthManager](./TAuthManager.md) - Uses rule collections
