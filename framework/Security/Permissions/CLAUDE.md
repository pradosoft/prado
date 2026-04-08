# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Advanced RBAC (Role-Based Access Control) with named permissions, role hierarchies, dynamic event-level authorization, and page-level permission configuration.

## Classes

### Interfaces & Contracts

- **`IPermissions`** — Implement on any class (module, control, service) that declares its own permissions. Single method: `getPermissions($manager)` — returns an array of `TPermissionEvent` and/or calls `$manager->registerPermission()` directly.

### Manager

- **`TPermissionsManager`** — `TModule` subclass; the central permissions registry. Key capabilities:
  - Manages a **role hierarchy** (roles containing other roles/permissions, recursively resolved).
  - Manages **named permissions**, each with a set of `TAuthorizationRule` objects.
  - Auto-attaches `TPermissionsBehavior` to every class implementing `IPermissions` via the `fxAttachClassBehavior` global event.
  - Properties: `DefaultRoles` (roles automatically assigned to all users), `SuperRoles` (roles that bypass all permission checks), `PermissionsFile`, `DbParameter` (module ID for dynamic roles/permissions from `TDbParameterModule`).
  - Configure in `application.xml`:
    ```xml
    <module id="permissions" class="Prado\Security\Permissions\TPermissionsManager"
            DefaultRoles="Default" SuperRoles="Administrator">
        <role name="author" children="post_new,post_read,post_update" />
        <role name="Editor" children="author,post_delete,post_publish" />
       <permissionrule name="post_delete" action="deny" users="*" roles="author" verb="*" IPs="" />
    </module>
    ```

- **`TPermissionsManagerPropertyTrait`** — Shared trait providing the `PermissionsManager` property accessor used by controls and behaviors that need a reference to `TPermissionsManager`.

### Behaviors

- **`TPermissionsBehavior`** — Class behavior automatically attached to `IPermissions` implementors. Intercepts dynamic events listed in `TPermissionEvent::getEvents()` and calls `TPermissionsManager::isPermissionAllowed()` before the event proceeds.

- **`TPermissionsConfigurationBehavior`** — Behavior attached to `TPageConfiguration`. Allows per-page XML to declare `<permissionrule>` entries, integrating with the page authorization lifecycle.

- **`TUserPermissionsBehavior`** — Behavior attached to `IUser`. Adds permission-check methods (`can($permission)`) and caches role resolution for the current user.

### Value Objects

- **`TPermissionEvent`** — Defines one permission: `PermissionName`, `Description`, `Events` (array of `dy*` event names that trigger the permission check). Returned from `IPermissions::getPermissions()`.

- **`TUserOwnerRule`** — Special `TAuthorizationRule` subclass: grants access only if the current user "owns" the object being acted on. The ownership check is delegated to a configurable callback or a `dyIsOwner` dynamic event.

## Permission Naming Convention

Use dot (or snake) notation: `module.resource.action` — e.g., `blog.post.edit`, `admin.users.delete`. Wildcard matching is supported: `blog.*` matches all `blog.` permissions.

## Patterns & Gotchas

- **`SuperRoles`** bypass all permission checks — use only for super-admin roles.
- **`DefaultRoles`** are merged into every user's role list at check time; they do not modify the user object.
- **`TPermissionsManager` must be registered after `TAuthManager`** in `application.xml` if both are used — auth state must be established before permission checks run.
- **Role hierarchy is recursive** — a role `editor` which contains `commenter` means `editor` users also pass `commenter` permission checks. Avoid circular role definitions.
- **Dynamic role loading from `TDbParameterModule`** — roles stored in the database are merged at runtime; changes take effect on the next request.
