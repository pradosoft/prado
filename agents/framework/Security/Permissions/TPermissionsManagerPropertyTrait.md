# TPermissionsManagerPropertyTrait

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TPermissionsManagerPropertyTrait](./TPermissionsManagerPropertyTrait.md)

**Location:** `framework/Security/Permissions/TPermissionsManagerPropertyTrait.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

Shared trait providing the `PermissionsManager` property accessor. Used by behaviors that need a typed reference to [TPermissionsManager](./TPermissionsManager.md).

## Methods

| Method | Description |
|--------|-------------|
| `getPermissionsManager(): ?TPermissionsManager` | Gets the permissions manager |
| `setPermissionsManager(null\|TPermissionsManager\|WeakReference $manager)` | Sets the permissions manager |
| `__wakeup()` | On wakeup, reconnects to singleton manager |

## Used By

- [TPermissionsBehavior](./TPermissionsBehavior.md)
- [TPermissionsConfigurationBehavior](./TPermissionsConfigurationBehavior.md)
- [TUserPermissionsBehavior](./TUserPermissionsBehavior.md)

## See Also

- [TPermissionsManager](./TPermissionsManager.md) - The singleton manager
