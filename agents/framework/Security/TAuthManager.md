# Security System

**Location:** `framework/Security/`
**Namespace:** `Prado\Security`

## Overview

Prado's security layer has four main concerns: user identity, authentication (session-based + cookie), authorization (rules), and cryptography. The `Permissions/` subdirectory adds RBAC on top.

---

## User Identity

### IUser / TUser

```php
// Access anywhere:
$user = Prado::getUser();          // returns IUser (TUser or guest)

$user->getName();                  // string username
$user->getIsGuest();               // bool
$user->getRoles();                 // array of role strings
$user->isInRole('admin');          // bool (recursive with TPermissionsManager)
```

`TUser` stores state in the session. Dynamic properties (stored on the user object) persist across requests via the session.

### TUserManager / TDbUserManager

- `TUserManager` — in-memory user store; users defined in XML config.
- `TDbUserManager` — database-backed; queries a user table. Both implement `IUserManager`.

```php
$mgr->getUser($username);                        // returns IUser or null
$mgr->validateUser($username, $password);        // bool
```

Password modes: `TUserManagerPasswordMode::Clear`, `MD5`, `SHA1`.

---

## TAuthManager

Module that handles authentication and authorization flow. Registered in `application.xml`.

### Key Properties

| Property | Description |
|----------|-------------|
| `UserManager` | Module ID of the `IUserManager` to use |
| `LoginPage` | Page path to redirect to when auth fails |
| `AuthExpire` | Session timeout in seconds (0 = never) |
| `AllowAutoLogin` | Enable cookie-based remember-me |
| `CookieName` | Cookie name for auto-login token |

### Auth Flow

1. On `OnAuthentication` application event: reads session/cookie → sets `$app->setUser()`.
2. On `OnAuthorization` application event: checks `TAuthorizationRuleCollection` → redirects to `LoginPage` if denied.

### Authorization Rules

```xml
<authorization>
    <allow users="admin" />
    <allow roles="editor" />
    <deny users="?" />  <!-- deny unauthenticated -->
    <allow users="*" />
</authorization>
```

Rules evaluated in order; first match wins. Wildcards: `*` = all users, `?` = authenticated users only.

`TAuthorizationRule` properties: `Action` (allow/deny), `Users`, `Roles`, `Verb` (HTTP method), `IPRules`.

---

## TSecurityManager

Cryptographic utilities. Used internally for page-state signing and cookie token validation.

```php
$manager = $app->getSecurityManager();
$hash   = $manager->computeHash($data);           // HMAC hash
$enc    = $manager->encrypt($data);               // encrypt string
$dec    = $manager->decrypt($encData);            // decrypt string
$valid  = $manager->validateData($data, $token);  // verify HMAC
```

Properties: `ValidationKey`, `EncryptionKey`, `Algorithm` (hash algorithm).

---

## Permissions (RBAC) — TPermissionsManager

See `Security/Permissions/` for full detail. Quick summary:

```xml
<!-- application.xml -->
<module id="permissions" class="Prado\Security\Permissions\TPermissionsManager"
        DefaultRoles="Default" SuperRoles="Administrator">
    <role name="Editor" children="author,commenter" />
    <permissionrule name="blog.post.edit" action="allow" roles="Editor" />
</module>
```

- `SuperRoles` bypass all checks.
- `DefaultRoles` silently merged into every user.
- Wildcard: `blog.*` matches all blog permissions.
- Role hierarchy is recursive — `Editor` automatically passes `author` and `commenter` checks.
- Classes implementing `IPermissions` have `TPermissionsBehavior` auto-attached.

```php
// Check permission in code:
$manager->isPermissionAllowed('blog.post.edit');  // uses current user
$user->can('blog.post.edit');  // via TUserPermissionsBehavior
```

---

## Configuration Pattern

```xml
<!-- application.xml -->
<modules>
    <module id="users" class="Prado\Security\TUserManager">
        <user name="admin" password="secret" roles="Administrator" />
    </module>
    <module id="auth" class="Prado\Security\TAuthManager"
            UserManager="users" LoginPage="Login" AllowAutoLogin="true" />
    <module id="security" class="Prado\Security\TSecurityManager"
            ValidationKey="my-secret-key" />
</modules>
```

## Gotchas

- `TAuthManager` must be registered **before** `TPermissionsManager` in `application.xml`.
- `LoginPage` must be publicly accessible — exempted from authorization rules.
- Cookie auto-login requires `TSecurityManager` with a stable `ValidationKey`.
- `TDbUserManager` needs a DB connection module ID in `ConnectionID` property.
