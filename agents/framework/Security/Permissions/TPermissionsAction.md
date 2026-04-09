# TPermissionsAction

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TPermissionsAction](./TPermissionsAction.md)

**Location:** `framework/Security/Permissions/TPermissionsAction.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

Shell action for viewing and managing roles and permissions. Provides CLI commands for inspecting and editing the permission database configuration.

## Actions

| Action | Description |
|--------|-------------|
| `index` | Displays roles and permission rules |
| `role` | View/add/remove role children |
| `add-rule` | Add a permission rule to DB |
| `remove-rule` | Remove a permission rule from DB |

## Usage

```sh
prado-cli perm                    # List all roles and rules
prado-cli perm -a                 # List all (including non-DB) roles and rules
prado-cli perm role editor +author -commenter  # Add/remove children
prado-cli perm/add-rule 'blog.edit' allow '*' 'Editor' '*' '*' 1000
```

## See Also

- [TPermissionsManager](./TPermissionsManager.md) - Manages permissions
