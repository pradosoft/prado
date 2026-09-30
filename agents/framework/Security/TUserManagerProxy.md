# Security/TUserManagerProxy

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TUserManagerProxy`**

## Class Info
**Location:** `framework/Security/TUserManagerProxy.php`
**Namespace:** `Prado\Security`
**Extends:** `TModule`; implements [`IUserManager`](./IUserManager.md), `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent user manager module that delegates the five `IUserManager` methods to the module named by `BackingUserManagerId`. `TAuthManager::UserManager` names one module ID and checks `instanceof IUserManager`, so the proxy lets the configuration swap a file-based `TUserManager` for a `TDbUserManager`, or any other `IUserManager`, without touching the authentication module.

The proxy extends `TModule`, not a concrete manager, so it wraps any `IUserManager`; the backing's own properties and methods (`PasswordMode`, `UserFile`, `getUsers()`, ...) reach it through the trait's `__get`/`__set`/`__call` forwarding.

## Configuration

```xml
<module id="auth" class="Prado\Security\TAuthManager" UserManager="users" />
<module id="users" class="Prado\Security\TUserManagerProxy" BackingUserManagerId="dbUsers" />
<module id="dbUsers" class="Prado\Security\TDbUserManager" UserClass="Application.Users.DbUser" ConnectionID="db" />
```

## Properties

- `BackingUserManagerId` (string) — required by `init()` (`usermanagerproxy_backing_user_manager_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.security`) and drops the resolved backing.
- `UserManager` (read-only `IUserManager`) — resolves the backing on first call; throws `usermanagerproxy_user_manager_not_found` or `usermanagerproxy_invalid_user_manager_type`, then calls `attachProxy()`.

## Behavior

- `getGuestName()`, `getUser()`, `getUserFromCookie()`, `saveUserToCookie()`, and `validateUser()` call the backing. A `TUser` returned through the proxy reports the backing as its `Manager`.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.

## See Also
- [TUserManager](./TUserManager.md), [TDbUserManager](./TDbUserManager.md), [TAuthManager](./TAuthManager.md), [TModuleProxy](../TModuleProxy.md)
