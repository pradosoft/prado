# TPermissionsConfigurationBehavior

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TPermissionsConfigurationBehavior](./TPermissionsConfigurationBehavior.md)

**Location:** `framework/Security/Permissions/TPermissionsConfigurationBehavior.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

Behavior for [TPageConfiguration](../../Web/Services/TPageConfiguration.md) that reads permissions role hierarchy and permission rules from page configuration files (PHP or XML).

## Key Methods

| Method | Description |
|--------|-------------|
| `dyLoadPageConfigurationFromPhp(...)` | Reads `<permissions>` from PHP config |
| `dyLoadPageConfigurationFromXml(...)` | Reads `<permissions>` from XML config |
| `dyApplyConfiguration(...)` | Applies parsed permissions to `TPermissionsManager` |

## Configuration Example

```xml
<permissions>
    <role name="pageRole" children="otherRole, permission_name" />
    <permissionrule name="permission_name" action="allow" roles="manager"/>
</permissions>
```

## See Also

- [TPermissionsManager](./TPermissionsManager.md) - Applies the loaded permissions
- [TPageConfiguration](../../Web/Services/TPageConfiguration.md) - Page configuration
