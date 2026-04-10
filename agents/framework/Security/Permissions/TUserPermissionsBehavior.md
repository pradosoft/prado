# Security/Permissions/TUserPermissionsBehavior

### Directories
[framework](../../INDEX.md) / [Security](../INDEX.md) / [Permissions](./INDEX.md) / **`TUserPermissionsBehavior`**

## Class Info
**Location:** `framework/Security/Permissions/TUserPermissionsBehavior.php`
**Namespace:** `Prado\Security\Permissions`

## Overview
Behavior attached to [IUser](../IUser.md) (via `TUserPermissionsBehavior`). Adds permission checking via `can()` method and delegates role hierarchy checks to [TPermissionsManager](./TPermissionsManager.md).

## Interfaces Implemented

- Extends [TBehavior](../../Util/TBehavior.md)
- Uses [TPermissionsManagerPropertyTrait](./TPermissionsManagerPropertyTrait.md)

## Key Methods

| Method | Description |
|--------|-------------|
| `can(string $permission, mixed $extraData = null): bool` | Checks if user has permission |
| `dyDefaultRoles(array $roles, TCallChain $chain)` | Merges default roles from manager |
| `dyIsInRole(bool $return, string $role, TCallChain $chain)` | Checks role hierarchy |

## Usage

```php
$user->can('blog.post.edit');          // Check permission
$user->can('blog.post.edit', ['username' => 'owner']);  // With extra data
```

## See Also

- [TPermissionsManager](./TPermissionsManager.md) - Central permission manager
- [TUser](../TUser.md) - User class this attaches to
