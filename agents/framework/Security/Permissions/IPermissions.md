# Security/Permissions/IPermissions

### Directories
[framework](./INDEX.md) / [Security](./Security/INDEX.md) / [Permissions](./Security/Permissions/INDEX.md) / **`IPermissions`**

**Location:** `framework/Security/Permissions/IPermissions.php`
**Namespace:** `Prado\Security\Permissions`

## Overview
Interface for classes that declare their own permissions. Classes implementing this interface will have [TPermissionsBehavior](./TPermissionsBehavior.md) automatically attached by [TPermissionsManager](./TPermissionsManager.md).

## Methods

| Method | Description |
|--------|-------------|
| `getPermissions(TPermissionsManager $manager): TPermissionEvent[]` | Returns array of permission events |

## See Also

- [TPermissionsManager](./TPermissionsManager.md) - Attaches behavior to implementors
- [TPermissionsBehavior](./TPermissionsBehavior.md) - Attached behavior
