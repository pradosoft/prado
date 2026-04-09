# SUMMARY.md

Authentication, authorization, user management, and role-based access control (RBAC) for the Prado framework.

## Classes

- **`IUser`** — User object contract: `getName()`, `getIsGuest()`, `getRoles()`.

- **`IUserManager`** — User repository contract: `getUser($username)`, `validateUser($username, $password)`.

- **`TUser`** — Default user implementation; properties: `Name`, `Roles`, `IsGuest`; state persisted in session.

- **`TUserManager`** — In-memory user store implementing `IUserManager`; supports configurable password hashing (`MD5`, `SHA1`, custom).

- **`TDbUserManager`** — Database-backed user manager; queries a factory subclass implementation of TDbUser.

- **`TDbUser`** — Extends `TUser` with database persistence; implements database functions of TDbUserManager.

- **`TAuthManager`** — Module coordinating authentication and authorization; properties: `UserManager`, `LoginPage`, `AuthExpire`, `AllowAutoLogin`; manages session-based auth, cookie auto-login.

- **`TAuthorizationRule`** — Single access control rule; matches on HTTP verb, user name, roles, IP address, page/action.

- **`TAuthorizationRuleCollection`** — Ordered collection of `TAuthorizationRule` objects; first matching rule wins.

- **`TSecurityManager`** — Hash computation (`computeHash()`), encryption/decryption (`encrypt()`/`decrypt()`), HMAC token generation and validation.

- **`TUserManagerPasswordMode`** — Enum: `Clear`, `MD5`, `SHA1`.
