# IUser

### Directories

[./](../INDEX.md) > [Security](./INDEX.md) > [IUser](./IUser.md)

**Location:** `framework/Security/IUser.php`
**Namespace:** `Prado\Security`

## Overview

Contract for user objects. All user objects in Prado must implement this interface.

## Methods

| Method | Description |
|--------|-------------|
| `getName(): string` | Returns the username |
| `setName(string $value)` | Sets the username |
| `getIsGuest(): bool` | Returns true if the user is a guest (unauthenticated) |
| `setIsGuest(bool $value)` | Sets whether the user is a guest |
| `getRoles(): array` | Returns array of role names the user belongs to |
| `setRoles(array\|string $value)` | Sets roles (accepts array or comma-separated string) |
| `isInRole(string $role): bool` | Tests if user has the specified role |
| `saveToString(): string` | Serializes user data for session storage |
| `loadFromString(string $string): IUser` | Restores user from serialized session data |

## See Also

- [TUser](./TUser.md) - Default implementation
- [IUserManager](./IUserManager.md) - User repository interface
