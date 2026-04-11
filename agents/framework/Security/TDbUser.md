# Security/TDbUser

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TDbUser`**

## Class Info
**Location:** `framework/Security/TDbUser.php`
**Namespace:** `Prado\Security`

## Overview
`TDbUser` is an **abstract** base class that extends [TUser](./TUser.md) for database-backed user accounts. It is paired with [TDbUserManager](./TDbUserManager.md). Subclasses must implement the two abstract methods `validateUser()` and `createUser()`. Optionally, `createUserFromCookie()` and `saveUserToCookie()` can be overridden to support persistent "remember me" login.

## Inheritance

```
TComponent → TUser → TDbUser  (abstract)
```

## Abstract Methods (must implement in subclass)

```php
abstract public function validateUser(string $username, #[\SensitiveParameter] string $password): bool
```
Checks username/password against the database. Use `getDbConnection()` to query. The `#[SensitiveParameter]` attribute prevents the password from appearing in stack traces.

```php
abstract public function createUser(string $username): ?TDbUser
```
Loads a user record from the database by username and returns a fully initialized `TDbUser` instance (with name, roles, etc. set via `setState`). Returns `null` if the username is not found.

## Key Methods

```php
public function getDbConnection(): TDbConnection
```
Lazily retrieves the active database connection from the owning [TDbUserManager](./TDbUserManager.md). Throws [TConfigurationException](../Exceptions/TConfigurationException.md)('dbuser_dbconnection_invalid')` if no valid connection exists. Activates the connection automatically (`setActive(true)`).

```php
public function createUserFromCookie([THttpCookie](../Web/THttpCookie.md) $cookie): ?TDbUser
```
Default implementation returns `null` (auto-login not supported). Override to extract username and a secret token from the cookie and verify them against the database.

```php
public function saveUserToCookie([THttpCookie](../Web/THttpCookie.md) $cookie): void
```
Default implementation does nothing. Override to store a signed token (never the raw password) into the cookie for subsequent auto-login via `createUserFromCookie()`.

## Patterns & Gotchas

- **`createUser()` is responsible for populating all user state** — call `setName()`, `setIsGuest(false)`, `setRoles()`, and any custom `setState()` calls within this method.
- **Do not cache `TDbConnection` yourself** — `getDbConnection()` already caches it in `$_connection` and activates it. Calling `getDbConnection()` multiple times is safe.
- **Cookie-based auto-login** requires both `createUserFromCookie()` and `saveUserToCookie()` to be overridden. The [TAuthManager](./TAuthManager.md)::`setAllowAutoLogin` property must also be `true`.
- **Never store the plaintext password in the cookie.** Generate a unique HMAC token from the username + a server secret or a DB-stored token, and verify it on `createUserFromCookie()`.
- **`validateUser()` is called by `TDbUserManager::validateUser()`** — the manager delegates to the factory user instance, not to the logged-in user instance.
- The `#[\SensitiveParameter]` attribute on `$password` parameters is required on overrides for PHP 8.2+ sensitive-parameter redaction to propagate.
