# Security/TAuthorizationRule

### Directories
[framework](../INDEX.md) / [Security](./INDEX.md) / **`TAuthorizationRule`**

## Class Info
**Location:** `framework/Security/TAuthorizationRule.php`
**Namespace:** `Prado\Security`

## Overview
Represents a single authorization rule. Rules specify which users/roles are allowed or denied access based on HTTP verb and IP address.

## Special User Identifiers

- `*` - All users (including guests)
- `?` - Guest/unauthenticated users only
- `@` - Authenticated users only

## Constructor

```php
public function __construct(
    string $action = 'allow',   // 'allow' or 'deny'
    string $users = '',          // comma-separated user list
    string $roles = '',          // comma-separated role list
    string $verb = '',           // 'get', 'post', or '' for both
    string $ipRules = '',       // comma-separated IP patterns (supports wildcards)
    ?numeric $priority = null   // lower = evaluated first
)
```

## Key Methods

| Method | Description |
|--------|-------------|
| `getAction(): string` | Returns 'allow' or 'deny' |
| `getUsers(): string[]` | List of user IDs |
| `getRoles(): string[]` | List of roles |
| `getVerb(): string` | HTTP verb ('*', 'get', or 'post') |
| `getIPRules(): array` | IP patterns |
| `getPriority(): numeric` | Rule priority |
| `isUserAllowed(IUser $user, string $verb, string $ip, mixed $extra = null): int` | Returns 1 (allow), -1 (deny), or 0 (not applicable) |

## See Also

- [TAuthorizationRuleCollection](./TAuthorizationRuleCollection.md) - Collection of rules
- [TAuthManager](./TAuthManager.md) - Evaluates rules
