# Security/INDEX.md - SECURITY_INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`Permissions/`](SECURITY_PERMISSIONS_INDEX.md)] | Advanced RBAC (Role-Based Access Control) with named permissions, role hierarchies, dynamic event-level authorization, and page-level permission configuration. |

## Purpose

Authentication, authorization, user management, and role-based access control (RBAC) for the Prado framework.

## Classes

### Interfaces

- **`IUser`** — User object contract: `getName()`, `getIsGuest()`, `getRoles()`.
- **`IUserManager`** — User repository contract: `getUser($username)`, `validateUser($username, $password)`.

### User & User Management

- **`TUser`** — Default user implementation. Properties: `Name`, `Roles` (array), `IsGuest`. State is persisted in the session. Supports dynamic properties for custom user data.

- **`TUserManager`** — In-memory user store implementing `IUserManager`. Supports configurable password hashing (`MD5`, `SHA1`, custom). Register in `application.xml` as a module.

- **`TDbUserManager`** — Database-backed user manager. Queries a factory subclass implementation of TDbUser.

- **`TDbUser`** — Extends `TUser` with database persistence. Implements the database functions of TDbUserManager.

### Authentication & Authorization

- **`TAuthManager`** — Module that coordinates authentication and authorization. Properties: `UserManager`, `LoginPage`, `AuthExpire`, `AllowAutoLogin`. Manages session-based auth, cookie auto-login, login/logout events, and redirect-to-login on auth failure. Enforces `TAuthorizationRule` collection.

- **`TAuthorizationRule`** — Single access control rule. Matches on: HTTP verb, user name (or `*`/`?` wildcards), roles, IP address, page/action. Priority-ordered.

- **`TAuthorizationRuleCollection`** — Ordered collection of `TAuthorizationRule` objects; first matching rule wins.

### Cryptography

- **`TSecurityManager`** — Hash computation (`computeHash()`), encryption/decryption (`encrypt()` / `decrypt()`), HMAC token generation and validation. Used by `TPage` for page-state signing.

- **`TUserManagerPasswordMode`** — Enum: `Clear`, `MD5`, `SHA1`.

## Subdirectory: [Permissions/](SECURITY_PERMISSIONS_INDEX.md)

Advanced RBAC with permission hierarchies and dynamic authorization.

- **`IPermissions`** — Interface for classes that declare their own permissions. Method: `getPermissions()` returns array of `TPermissionEvent`. Attach `TPermissionsBehavior` automatically when registered with `TPermissionsManager`.

- **`TPermissionsManager`** — `TModule` subclass. Manages role hierarchy (roles containing other roles, recursively resolved) and named permissions. `SuperRoles` bypass all checks; `DefaultRoles` are silently merged into every user. Supports wildcard matching (`blog.*`, `*`). Loads dynamic roles/permissions from `TDbParameterModule`. Configure in `application.xml`.

- **`TPermissionsManagerPropertyTrait`** — Shared trait providing the `PermissionsManager` property accessor; used by controls and behaviors that need a typed reference to `TPermissionsManager`.

- **`TPermissionsBehavior`** — Behavior automatically attached to `IPermissions` classes. Intercepts specified dynamic events to enforce permissions.

- **`TPermissionsConfigurationBehavior`** — Integrates per-page permission configuration with the page authorization lifecycle.

- **`TPermissionEvent`** — Defines a permission: `PermissionName`, `Roles`, `Rules`.

- **`TUserPermissionsBehavior`** / **`TUserOwnerRule`** — Behavior and rule for matching object ownership (e.g., "the user who created this record").

- **`TPermissionsAction`** — Shell action: list roles and permissions, debug permission checks.

## Conventions

- **User access via** `Prado::getUser()` — always returns an `IUser` (guest if unauthenticated).
- **Role hierarchy** — Roles can contain other roles; membership is recursive.
- **Permission naming** — Use dot notation: `blog.post.create`, `admin.users.delete`.
- **Wildcard rules** — `blog.*` matches all `blog.` permissions; priority determines evaluation order (lower number = evaluated first).
- **Authorization rule wildcards** — `*` = all users, `?` = authenticated users only.
- **`TAuthManager`** must be registered as a module before `TPermissionsManager` if both are used.

## Gotchas

- `TAuthManager` redirects unauthenticated requests to `LoginPage` — ensure that page is publicly accessible (exempt from authorization rules).
- Cookie auto-login stores a signed token; `TSecurityManager` must be configured for it to work securely.
- `TPermissionsManager` uses `TDbParameterModule` for dynamic roles — that module must be initialized first.
