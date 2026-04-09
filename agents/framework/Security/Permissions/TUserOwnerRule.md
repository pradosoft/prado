# TUserOwnerRule

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TUserOwnerRule](./TUserOwnerRule.md)

**Location:** `framework/Security/Permissions/TUserOwnerRule.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

Extends [TAuthorizationRule](../TAuthorizationRule.md) to check object ownership. Allows access only when the current user matches the username in the `extra` data.

## Key Method

```php
public function isUserAllowed(IUser $user, string $verb, string $ip, array $extra = null): int
```

Returns 1 (allow) if parent rule matches AND `$extra['username']` equals current user name.

## See Also

- [TAuthorizationRule](../TAuthorizationRule.md) - Parent class
