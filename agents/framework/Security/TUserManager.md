# TUserManager

### Directories

[./](../INDEX.md) > [Security](./INDEX.md) > [TUserManager](./TUserManager.md)

**Location:** `framework/Security/TUserManager.php`
**Namespace:** `Prado\Security`

## Overview

`TUserManager` is a static, in-memory user store. User and role information is declared directly in the application configuration (XML or PHP) or loaded from an external file. It is the simplest [IUserManager](./IUserManager.md) implementation, suitable for small applications or demos. For production database-backed user management, use [TDbUserManager](./TDbUserManager.md) instead.

## Interfaces Implemented

- [IUserManager](./IUserManager.md)
- Extends [TModule](../TModule.md)

## Key Properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `GuestName` | `string` | `'Guest'` | Name used for unauthenticated users |
| `PasswordMode` | `TUserManagerPasswordMode` | `MD5` | How passwords are stored: `Clear`, `MD5`, `SHA1` |
| `UserFile` | `string` | `null` | Namespace-format path to external XML/PHP file with user/role data. Read-only after `init()`. |

## Key Methods

```php
public function validateUser(string $username, #[\SensitiveParameter] string $password): bool
```
Hashes the password per `PasswordMode`, then does a case-insensitive username lookup in `$_users`. Returns `true` on match.

```php
public function getUser(?string $username = null): ?TUser
```
Returns a new [TUser](./TUser.md) instance. If `$username` is `null`, returns a guest. If the username is not in `$_users`, returns `null`. Roles from both `<user roles="...">` and `<role users="...">` elements are merged and applied.

```php
public function getUsers(): array
```
Returns `['lowercased_username' => 'stored_password', ...]`.

```php
public function getRoles(): array
```
Returns `['lowercased_username' => ['Role1', 'Role2'], ...]`.

```php
public function getUserFromCookie([THttpCookie](../Web/THttpCookie.md) $cookie): ?TUser
```
Verifies a cookie containing `[username, md5(username . password)]`. Returns a user on match, `null` otherwise.

```php
public function saveUserToCookie([THttpCookie](../Web/THttpCookie.md) $cookie): void
```
Stores `serialize([username, md5(username . password)])` in the cookie for auto-login.

## Configuration (XML)

```xml
<module id="users" class="Prado\Security\TUserManager" PasswordMode="Clear">
    <user name="Joe"   password="demo" />
    <user name="John"  password="demo" />
    <user name="Jerry" password="demo" roles="Writer,Administrator" />
    <role name="Administrator" users="John" />
    <role name="Writer"        users="Joe,John" />
</module>
```

PHP array format:
```php
'users' => [
    'class' => 'Prado\Security\TUserManager',
    'properties' => ['PasswordMode' => 'Clear'],
    'users' => [
        ['name' => 'Joe',   'password' => 'demo'],
        ['name' => 'John',  'password' => 'demo'],
        ['name' => 'Jerry', 'password' => 'demo', 'roles' => 'Administrator,Writer'],
    ],
    'roles' => [
        ['name' => 'Administrator', 'users' => 'John'],
        ['name' => 'Writer',        'users' => 'Joe,John'],
    ],
],
```

External user file (set `UserFile` to a namespace path matching the config format above):
```xml
<module id="users" class="Prado\Security\TUserManager" UserFile="Application.config.users" />
```

## Patterns & Gotchas

- **Usernames are normalized to lowercase** during load and lookup — `Joe` and `joe` are the same user.
- **`UserFile` cannot be changed after `init()`** — throws [TInvalidOperationException](../Exceptions/TInvalidOperationException.md).
- **Roles can be declared on `<user>` elements AND on `<role>` elements** — both are merged into the same per-user role list.
- **Cookie auto-login token is `md5(username . storedPassword)`** — if the stored password changes, all existing cookies are immediately invalidated. This is intentional.
- **`PasswordMode::MD5` is the default** — applications that require stronger hashing should use `TDbUserManager` with bcrypt/Argon2 in the custom `TDbUser`.
- **Not suitable for large user bases** — the entire user list is loaded into memory on every request.
