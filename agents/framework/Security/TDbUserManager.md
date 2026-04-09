# TDbUserManager

### Directories

[./](../INDEX.md) > [Security](./INDEX.md) > [TDbUserManager](./TDbUserManager.md)

**Location:** `framework/Security/TDbUserManager.php`
**Namespace:** `Prado\Security`

## Overview

`TDbUserManager` is a database-backed user manager that delegates all user data operations to a developer-supplied [TDbUser](./TDbUser.md) subclass. The manager itself holds no user records — it acts as a factory and connection provider. It is designed for production use alongside [TAuthManager](./TAuthManager.md).

## Interfaces Implemented

- [IUserManager](./IUserManager.md)
- [IDbModule](../Util/IDbModule.md) (provides `getDbConnection()` for use by the framework's DB-aware modules)
- Extends [TModule](../TModule.md)

## Key Properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `UserClass` | `string` | `''` | **Required.** Namespace-format class name of the `TDbUser` subclass. |
| `GuestName` | `string` | `'Guest'` | Name for unauthenticated users. |
| `ConnectionID` | `string` | `''` | **Required.** Module ID of a `TDataSourceConfig` that provides the DB connection. |

## Key Methods

```php
public function validateUser(string $username, #[\SensitiveParameter] string $password): bool
```
Delegates to `$_userFactory->validateUser($username, $password)`. The factory is the user instance created at `init()` time.

```php
public function getUser(?string $username = null): ?TUser
```
- `null` → creates a fresh [TDbUser](./TDbUser.md) instance and sets it as guest.
- Non-null → calls `$_userFactory->createUser($username)`. Returns `null` if user not found.

```php
public function getDbConnection(): TDbConnection
```
Lazily resolves `ConnectionID` to a [TDataSourceConfig](../Data/TDataSourceConfig.md) module, retrieves its [TDbConnection](../Data/TDbConnection.md), activates it, and caches it. Throws [TConfigurationException](../Exceptions/TConfigurationException.md) if `ConnectionID` is empty or invalid.

```php
public function getUserFromCookie([THttpCookie](../Web/THttpCookie.md) $cookie): ?TDbUser
```
Delegates to `$_userFactory->createUserFromCookie($cookie)`.

```php
public function saveUserToCookie([THttpCookie](../Web/THttpCookie.md) $cookie): void
```
If the current application user is a [TDbUser](./TDbUser.md), delegates to `$user->saveUserToCookie($cookie)`.

## Configuration (XML)

```xml
<module id="db"
    class="Prado\Data\TDataSourceConfig"
    ConnectionString="mysql:host=localhost;dbname=myapp"
    Username="dbuser" Password="dbpass" />

<module id="users"
    class="Prado\Security\TDbUserManager"
    UserClass="Application.Security.MyUser"
    ConnectionID="db" />

<module id="auth"
    class="Prado\Security\TAuthManager"
    UserManager="users"
    LoginPage="Pages.Login" />
```

## Initialization Behavior

During `init()`:
1. Validates that `UserClass` is set; throws `TConfigurationException('dbusermanager_userclass_required')` otherwise.
2. Creates a prototype instance of `UserClass` via `Prado::createComponent($userClass, $this)`.
3. Validates the prototype is a [TDbUser](./TDbUser.md); throws [TInvalidDataTypeException](../Exceptions/TInvalidDataTypeException.md)('dbusermanager_userclass_invalid')` otherwise.
4. This prototype (`$_userFactory`) is reused for all `validateUser()` and `createUser()` calls.

## Patterns & Gotchas

- **`UserClass` is required** — the module will not initialize without it.
- **`ConnectionID` is required** — an empty value throws `TConfigurationException('dbusermanager_connectionid_required')`.
- **The factory pattern** — `$_userFactory` is a single reused prototype. It is passed `$this` (the manager) in its constructor, so `getDbConnection()` works inside `TDbUser` methods.
- **Cookie auto-login** requires the `TDbUser` subclass to override both `createUserFromCookie()` and `saveUserToCookie()`. The default implementations are no-ops.
- **`IDbModule` conformance** — `TDbUserManager` registers itself as a DB module, which means other framework components (e.g., `TDbParameterModule`) can discover it as a connection source.
- **Connection is shared** — the same `TDbConnection` instance (cached in `$_conn`) is returned to all callers including the `TDbUser` instances.
