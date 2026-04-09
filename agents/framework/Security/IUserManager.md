# IUserManager

### Directories

[./](../INDEX.md) > [Security](./INDEX.md) > [IUserManager](./IUserManager.md)

**Location:** `framework/Security/IUserManager.php`
**Namespace:** `Prado\Security`

## Overview

Interface for user manager classes. Must be implemented by any user manager that works with [TAuthManager](./TAuthManager.md) and [TUser](./TUser.md).

## Methods

| Method | Description |
|--------|-------------|
| `getGuestName(): string` | Returns the name for guest users |
| `getUser(?string $username): TUser` | Returns user instance by name, or null if not found |
| `getUserFromCookie([THttpCookie](../Web/THttpCookie.md) $cookie): TUser` | Returns user based on auth cookie data, or null if invalid |
| `saveUserToCookie([THttpCookie](../Web/THttpCookie.md) $cookie)` | Saves user auth data to cookie |
| `validateUser(string $username, string $password): bool` | Validates username/password credentials |

## See Also

- [TUserManager](./TUserManager.md) - In-memory implementation
- [TDbUserManager](./TDbUserManager.md) - Database-backed implementation
