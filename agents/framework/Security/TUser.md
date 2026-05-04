# Security/TUser

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TUser`**

## Class Info
**Location:** `framework/Security/TUser.php`
**Namespace:** `Prado\Security`

## Overview
`TUser` is the default user implementation for a Prado application. It stores user identity (name, roles, guest status) in a serialized state dictionary that is persisted in the session. It is designed to work alongside [IUserManager](./IUserManager.md) (typically [TUserManager](./TUserManager.md) or [TDbUserManager](./TDbUserManager.md)). The class auto-listens to global (`fx`) events.

## Interfaces Implemented

- [IUser](./IUser.md) — contract for `getName()`, `getIsGuest()`, `getRoles()`
- Extends [TComponent](../TComponent.md) (full behavior/event/property system)

## Key Properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `Name` | `string` | guest name from manager | Username; stored in state |
| `IsGuest` | `bool` | `true` | Setting to `true` resets name to guest name and clears roles |
| `Roles` | `array` | `[]` | Merged with `dyDefaultRoles([])` dynamic event result on read |
| `StateChanged` | `bool` | `false` | Tracks whether any state mutation occurred (used by auth layer to re-save session) |

## Key Methods

```php
__construct(IUserManager $manager)
```
Initializes state array and sets name to the manager's guest name.

```php
getManager(): IUserManager
```
Returns the user manager that created this user.

```php
isInRole(string $role): bool
```
Case-insensitive check across `getRoles()`. Falls through to `dyIsInRole(false, $role)` dynamic event so behaviors can extend role resolution.

```php
saveToString(): string
```
Serializes `$_state` array. Used by [TAuthManager](./TAuthManager.md) to persist user to session.

```php
loadFromString(string $data): IUser
```
Unserializes previously saved state. Gracefully handles empty or corrupted data.

```php
protected getState(string $key, mixed $defaultValue = null): mixed
protected setState(string $key, mixed $value, mixed $defaultValue = null): void
```
State bag accessors for subclasses. `setState` sets `_stateChanged = true`; if `$value === $defaultValue` the key is removed (saves space in session).

## Dynamic Events (`@method`)

| Event | Signature | Purpose |
|---|---|---|
| `dyDefaultRoles` | `(string[] $defaultRoles): string[]` | Behaviors inject additional default roles. Result is merged into `getRoles()` and subtracted from `setRoles()`. |
| `dyIsInRole` | `(bool $returnValue, string $role): bool` | Behaviors can grant extra role membership. |

## Patterns & Gotchas

- **`getAutoGlobalListen()` returns `true`**, meaning `TUser` instances participate in global `fx*` event dispatch. Keep this in mind when attaching class behaviors to `IUser`.
- **Setting `IsGuest = true`** resets the username to the manager's guest name and clears all roles — do not set it directly after populating a user; use only for logout-style flows.
- **`getRoles()` always merges `dyDefaultRoles`** — the stored `Roles` state does _not_ include default roles. Conversely, `setRoles()` strips any roles that match `dyDefaultRoles` before storing, preventing duplication.
- **`StateChanged` flag** must be reset to `false` externally (by `TAuthManager`) after re-saving session; otherwise it will remain dirty indefinitely.
- **Subclasses** should use `getState()`/`setState()` to add extra persisted user data (e.g., email, display name). Do not add public fields.
- All state is serialized as a flat PHP array — avoid storing objects in state unless they are serializable.
