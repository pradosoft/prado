# Security/Permissions/TPermissionsManagerProxy

### Directories
[framework](../../INDEX.md) / [Security](../INDEX.md) / [Permissions](./INDEX.md) / **`TPermissionsManagerProxy`**

## Class Info
**Location:** `framework/Security/Permissions/TPermissionsManagerProxy.php`
**Namespace:** `Prado\Security\Permissions`
**Extends:** [`TPermissionsManager`](./TPermissionsManager.md); implements `IModuleDependency`, [`IProxy`](../../IProxy.md); uses [`TComponentProxyTrait`](../../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent permissions module that delegates every `TPermissionsManager` operation to the module named by `BackingPermissionsManagerId`. Lets the permissions slot be swapped in configuration without touching consumers. The proxy holds no rules, hierarchy, descriptions, or auto-rule state; the backing owns all of it.

## Configuration

```xml
<modules>
    <module id="permissions" class="Prado\Security\Permissions\TPermissionsManagerProxy" BackingPermissionsManagerId="realPermissions" />
    <module id="realPermissions" class="Prado\Security\Permissions\TPermissionsManager" SuperRoles="Administrator">
        <role name="Default" children="register_user" />
        <permissionrule name="*" action="deny" priority="1000" />
    </module>
</modules>
```

Rule data on the proxy is rejected (`permissionsmanagerproxy_rules_not_allowed`): a `<role>` or `<permissionrule>` element, a `roles` or `permissionrules` key in PHP configuration, or a `PermissionFile` attribute.

## Properties

- `BackingPermissionsManagerId` (string) — required by `init()` (`permissionsmanagerproxy_backing_permissions_manager_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.security.permissions`), detaches the event forwarders, and drops the resolved backing.
- `PermissionsManager` (read-only `TPermissionsManager`) — resolves the backing on first call; throws `permissionsmanagerproxy_permissions_manager_not_found` or `permissionsmanagerproxy_invalid_permissions_manager_type`, then calls `attachProxy()`.
- Every `TPermissionsManager` property (`SuperRoles`, `DefaultRoles`, `PermissionFile`, `AutoRulePriority`, `AutoAllowWithPermission`, `AutoPresetRules`, `AutoDenyAll`, `AutoDenyAllPriority`, `DbParameter`, `LoadParameter`) reads and writes the backing. A write after the backing is initialized throws the backing's `initialized_property_unchangeable`. `setPermissionFile()` always throws `permissionsmanagerproxy_rules_not_allowed`.

## Behavior

- Every public `TPermissionsManager` method is overridden to call the backing's public method (`registerPermission`, `getPermissionDescription`, `loadPermissionsData`, `registerShellAction`, `isInHierarchy`, `getDbConfigRoles`, `getDbConfigPermissionRules`, `addRoleChildren`, `removeRoleChildren`, `addPermissionRule`, `removePermissionRule`, `getHierarchyRoles`, `getHierarchyRoleChildren`, `getPermissionRules`). Base-class methods are not reached by `__call`, so each needs its own override.
- `init()` → guards `permissions_init_once`, requires the ID, rejects rule configuration, raises `dyInit` through `TModule::init()`, and marks the proxy initialized. It does not load rules, attach the three permissions class behaviors, or attach the `onAuthenticationComplete` shell handler; the backing's `init()` does.
- `getPermissions()` → `[]`. The backing attaches `TPermissionsBehavior` to every `IPermissions` instance, the proxy included; the behavior's `attach()` registers whatever `getPermissions()` returns, so delegating would register the built-in permissions a second time (`permissions_duplicate_permission`).
- `__destruct()` → calls `TComponent::__destruct()` only. `TPermissionsManager::__destruct()` detaches the class behaviors globally, which belong to the backing.
- `getManager()` (static, not overridden) → resolves to the backing: `TApplication::getModulesByType()` drops an `IProxy` whose backing is also listed.
- `getModuleDependencies()` → the backing ID, in both the `dyPreInit` and `init()` passes, so the backing initializes first.
- Other property reads and writes, method calls, and `on` events go through `TComponentProxyTrait`; `dy` and `fx` names are never forwarded.
- Serialization drops the resolved backing and the forwarder list; an empty ID is dropped too. A clone re-resolves on first use.

## Testing note
One initialized `TPermissionsManager` owns the class behaviors at a time. Constructing a second `IPermissions` instance (another manager) while one is initialized registers the built-in permissions again. `tests/unit/Security/Permissions/TPermissionsManagerProxyTest.php` destructs the current backing before building another and destructs every proxy in `tearDown()` while the application is alive, because `clearBehaviors()` on a `TPermissionsBehavior` owner needs the application.

## See Also
- [TPermissionsManager](./TPermissionsManager.md), [TCacheProxy](../../Caching/TCacheProxy.md), [TModuleProxy](../../TModuleProxy.md)
