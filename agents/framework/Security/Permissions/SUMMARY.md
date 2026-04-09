# SUMMARY.md

Advanced RBAC with named permissions, role hierarchies, dynamic event-level authorization, and page-level permission configuration.

## Classes

- **`IPermissions`** — Implement on any class that declares its own permissions; method: `getPermissions($manager)` returns array of `TPermissionEvent`.

- **`TPermissionsManager`** — `TModule` subclass managing role hierarchy and named permissions; auto-attaches `TPermissionsBehavior` to `IPermissions` classes; properties: `DefaultRoles`, `SuperRoles`, `PermissionsFile`, `DbParameter`.

- **`TPermissionsManagerPropertyTrait`** — Shared trait providing `PermissionsManager` property accessor.

- **`TPermissionsBehavior`** — Class behavior automatically attached to `IPermissions` implementors; intercepts dynamic events and calls `TPermissionsManager::isPermissionAllowed()`.

- **`TPermissionsConfigurationBehavior`** — Behavior attached to `TPageConfiguration`; allows per-page XML to declare `<permissionrule>` entries.

- **`TUserPermissionsBehavior`** — Behavior attached to `IUser`; adds permission-check methods (`can($permission)`) and caches role resolution.

- **`TPermissionEvent`** — Defines a permission: `PermissionName`, `Description`, `Events` (array of `dy*` event names triggering permission check).

- **`TUserOwnerRule`** — Special `TAuthorizationRule` subclass; grants access only if current user "owns" the object being acted on.

- **`TPermissionsAction`** — Shell action: list roles and permissions, debug permission checks.
