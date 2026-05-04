# Security/Permissions/TPermissionEvent

### Directories
[framework](../../INDEX.md) / [Security](../INDEX.md) / [Permissions](./INDEX.md) / **`TPermissionEvent`**

## Class Info
**Location:** `framework/Security/Permissions/TPermissionEvent.php`
**Namespace:** `Prado\Security\Permissions`

## Overview
Data container linking a permission name to dynamic events and optional preset [TAuthorizationRule](../TAuthorizationRule.md) objects.

## Constructor

```php
public function __construct(
    string $permissionName = '',
    string $description = '',
    string|string[] $events = [],
    ?TAuthorizationRule[] $rules = null
)
```

## Key Properties

| Property | Type | Description |
|----------|------|-------------|
| `Name` | `string` | Permission name (forced lowercase) |
| `Description` | `string` | Short human-readable description |
| `Events` | `string[]` | Dynamic event names that trigger permission check |
| `Rules` | `TAuthorizationRule[]` | Preset rules for this permission |

## See Also

- [TPermissionsBehavior](./TPermissionsBehavior.md) - Uses TPermissionEvent to enforce permissions
- [TPermissionsManager](./TPermissionsManager.md) - Registers permissions
