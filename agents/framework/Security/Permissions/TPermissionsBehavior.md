# TPermissionsBehavior

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TPermissionsBehavior](./TPermissionsBehavior.md)

**Location:** `framework/Security/Permissions/TPermissionsBehavior.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

Class behavior automatically attached to [IPermissions](./IPermissions.md) implementors. Intercepts dynamic events to enforce permission checks on behalf of [TPermissionsManager](./TPermissionsManager.md).

## Interfaces Implemented

- `IDynamicMethods` (implements `__dycall`)
- Extends [TBehavior](../../Util/TBehavior.md)
- Uses [TPermissionsManagerPropertyTrait](./TPermissionsManagerPropertyTrait.md)

## Key Methods

| Method | Description |
|--------|-------------|
| `attach(TComponent $owner)` | On attach, calls `getPermissions()` and registers with manager |
| `__dycall(string $method, array $args)` | Intercepts `dy*` calls to check permissions |
| `getPermissionEvents(): TPermissionEvent[]` | Returns registered permission events |
| `dyLogPermissionFailed(...)` | Logs permission failures |

## Pattern

```php
// In IPermissions class:
public function doProtectedAction($param) {
    if ($this->dyProtectedAction(false, $param) === true) {
        return; // blocked
    }
    // ... proceed
}
```

## See Also

- [IPermissions](./IPermissions.md) - Interface for permission declarers
- [TUserPermissionsBehavior](./TUserPermissionsBehavior.md) - User-level permission checks
