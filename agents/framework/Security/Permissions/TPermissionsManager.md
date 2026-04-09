# TPermissionsManager

### Directories

[./](../INDEX.md) > [Security](../INDEX.md) > [Permissions](./INDEX.md) > [TPermissionsManager](./TPermissionsManager.md)

**Location:** `framework/Security/Permissions/TPermissionsManager.php`
**Namespace:** `Prado\Security\Permissions`

## Overview

`TPermissionsManager` is the central RBAC (Role-Based Access Control) module for Prado. It manages:

- A **role hierarchy** (roles with recursive child roles/permissions).
- **Named permissions**, each with an ordered set of [TAuthorizationRule](../TAuthorizationRule.md) objects.
- Automatic attachment of [TPermissionsBehavior](./TPermissionsBehavior.md) to all [IPermissions](./IPermissions.md) implementors.
- Automatic attachment of [TUserPermissionsBehavior](./TUserPermissionsBehavior.md) to all [IUser](../IUser.md) instances (adding `can()` method).
- Automatic attachment of [TPermissionsConfigurationBehavior](./TPermissionsConfigurationBehavior.md) to `TPageConfiguration` (page-level rules).
- Optional runtime role/rule management backed by a [TDbParameterModule](../../Util/TDbParameterModule.md).

It also implements [IPermissions](./IPermissions.md) itself, registering three built-in permissions for shell access and runtime management.

## Interfaces Implemented

- [IPermissions](./IPermissions.md) (declares own shell/manage permissions)
- Extends [TModule](../../TModule.md)

## Constants

| Constant | Value | Purpose |
|---|---|---|
| `PERMISSIONS_BEHAVIOR` | `'permissions'` | Name used when attaching `TPermissionsBehavior` to `IPermissions` |
| `USER_PERMISSIONS_BEHAVIOR` | `'usercan'` | Name used when attaching `TUserPermissionsBehavior` to `IUser` |
| `PERMISSIONS_CONFIG_BEHAVIOR` | `'permissionsConfig'` | Name used when attaching to `TPageConfiguration` |
| `PERM_PERMISSIONS_SHELL` | `'permissions_shell'` | Permission: activate shell commands |
| `PERM_PERMISSIONS_MANAGE_ROLES` | `'permissions_manage_roles'` | Permission: add/remove DB role children |
| `PERM_PERMISSIONS_MANAGE_RULES` | `'permissions_manage_rules'` | Permission: add/remove DB permission rules |

## Key Properties

| Property | Type | Default | Notes |
|---|---|---|---|
| `SuperRoles` | `string\|string[]` | `[]` | Roles that receive the special `all` child (bypassing all permission checks). Read-only after `init()`. |
| `DefaultRoles` | `string\|string[]` | `[]` | Roles automatically merged into every user. Read-only after `init()`. |
| `PermissionFile` | `string` | `null` | Namespace-format path to an external roles/rules config file. Read-only after `init()`. |
| `AutoRulePriority` | `numeric` | `5` | Priority for auto-added "allow with permission" rules and preset rules. Read-only after `init()`. |
| `AutoAllowWithPermission` | `bool` | `true` | Automatically adds a rule allowing any user whose role hierarchy includes the permission. Read-only after `init()`. |
| `AutoPresetRules` | `bool` | `true` | Automatically adds preset rules supplied by `TPermissionEvent::getRules()` at registration. Read-only after `init()`. |
| `AutoDenyAll` | `bool` | `true` | Adds a final deny-all rule on the `*` wildcard when the first permission is registered. Read-only after `init()`. |
| `AutoDenyAllPriority` | `numeric` | `1000000` | Priority of the auto deny-all rule. Read-only after `init()`. |
| `DbParameter` | `TDbParameterModule\|string` | `null` | Module ID or instance providing runtime role/rule persistence. Read-only after `init()`. |
| `LoadParameter` | `string` | `'configuration:TPermissionsManager:runtime'` | Key used to load/save runtime data from `TDbParameterModule`. Read-only after `init()`. |

## Key Methods

### Static

```php
public static function getManager(): ?TPermissionsManager
```
Retrieves the first registered `TPermissionsManager` module from the application. Safe to call statically from anywhere.

### Instance

```php
public function registerPermission(string $permissionName, string $description, ?TAuthorizationRule[] $rules = null): void
```
Registers a new named permission. Adds it to the `all` role in the hierarchy. Applies auto rules (allow-with-permission, preset rules, wildcard matches). Throws [TInvalidOperationException](../../Exceptions/TInvalidOperationException.md) on duplicate permission names.

```php
public function getPermissionDescription(string $permissionName): string
```
Returns the short description for a registered permission.

```php
public function isInHierarchy(string|string[] $roles, string $permission, array &$checked = []): bool
```
Recursively checks whether `$permission` is reachable from `$roles` through the role hierarchy. Gracefully handles circular hierarchies via `$checked`.

```php
public function getHierarchyRoles(): string[]
```
Returns all role names defined in the hierarchy (keys of `$_hierarchy`).

```php
public function getHierarchyRoleChildren(?string $role): ?array
```
Returns children for a specific role, or the entire hierarchy array if `$role` is falsy.

```php
public function getPermissionRules(?string $permission): TAuthorizationRuleCollection|array|null
```
Returns the rule collection for one permission (string), or all permission rules (null).

```php
public function loadPermissionsData(array|TXmlElement $config): void
```
Parses role hierarchy and permission rules from XML or PHP array. Used internally by `init()` and can be called by `TPermissionsConfigurationBehavior` for per-page rules.

```php
public function addRoleChildren(string $role, string|string[] $children): bool
public function removeRoleChildren(string $role, string|string[] $children): bool
```
Runtime role hierarchy mutations. Require `DbParameter` to be set. Persist via [TDbParameterModule](../../Util/TDbParameterModule.md). Protected by `dyAddRoleChildren` / `dyRemoveRoleChildren` permission checks.

```php
public function addPermissionRule(string $permission, TAuthorizationRule $rule): bool
public function removePermissionRule(string $permission, TAuthorizationRule $rule): bool
```
Runtime permission rule mutations. Require `DbParameter`. Protected by `dyAddPermissionRule` / `dyRemovePermissionRule` permission checks.

```php
public function getDbConfigRoles(): array
public function getDbConfigPermissionRules(): array
```
Read-only inspection of the runtime role/rule data currently stored in [TDbParameterModule](../../Util/TDbParameterModule.md).

```php
public function registerShellAction(object $sender, mixed $param): void
```
Event handler on `onAuthenticationComplete`. Registers the [TPermissionsAction](./TPermissionsAction.md) shell command when the app is a [TShellApplication](../../Shell/TShellApplication.md), subject to `dyRegisterShellAction` permission.

```php
public function getPermissions(TPermissionsManager $manager): TPermissionEvent[]
```
`IPermissions` implementation — registers the three built-in permissions.

## Dynamic Events (`@method`)

| Event | Purpose |
|---|---|
| `dyRegisterShellAction(bool $return): bool` | Behaviors can prevent shell action registration |
| `dyAddRoleChildren(bool $return, string $role, string[] $children): bool` | Behaviors can veto/override `addRoleChildren()` |
| `dyRemoveRoleChildren(bool $return, string $role, string[] $children): bool` | Behaviors can veto/override `removeRoleChildren()` |
| `dyAddPermissionRule(bool $return, string $permission, TAuthorizationRule $rule): bool` | Behaviors can veto/override `addPermissionRule()` |
| `dyRemovePermissionRule(bool $return, string $permission, TAuthorizationRule $rule): bool` | Behaviors can veto/override `removePermissionRule()` |

## Configuration (XML)

```xml
<module id="permissions"
    class="Prado\Security\Permissions\TPermissionsManager"
    DefaultRoles="Default"
    SuperRoles="Administrator">

    <!-- Role hierarchy -->
    <role name="Editor" children="author,commenter" />
    <role name="Default" children="register_user,blog_read_posts" />

    <!-- Permission rules (first-match wins within each permission's sorted list) -->
    <permissionrule name="blog_edit"   action="allow" roles="Editor" />
    <permissionrule name="register_user" action="allow" users="?" />
    <permissionrule name="blog_*"      action="allow" users="admin" />
    <permissionrule name="*"           action="deny"  priority="1000" />
</module>
```

PHP format:
```php
'permissions' => [
    'class' => 'Prado\Security\Permissions\TPermissionsManager',
    'properties' => ['DefaultRoles' => 'Default', 'SuperRoles' => 'Administrator'],
    'roles' => [
        'Editor'  => ['author', 'commenter'],
        'Default' => ['register_user', 'blog_read_posts'],
    ],
    'permissionrules' => [
        ['name' => 'blog_edit',      'action' => 'allow', 'roles' => 'Editor'],
        ['name' => 'register_user',  'action' => 'allow', 'users' => '?'],
        ['name' => '*',              'action' => 'deny',  'priority' => 1000],
    ],
],
```

## Related Classes

| Class | Role |
|---|---|
| [TPermissionEvent](./TPermissionEvent.md) | Data container linking a permission name to dynamic events and preset rules |
| [TPermissionsBehavior](./TPermissionsBehavior.md) | Class behavior intercepting `dy*` events to enforce permission checks |
| [TUserPermissionsBehavior](./TUserPermissionsBehavior.md) | Behavior adding `can(string $permission): bool` to [IUser](../IUser.md) |
| [TPermissionsConfigurationBehavior](./TPermissionsConfigurationBehavior.md) | Behavior enabling per-page `<permissionrule>` in page config XML |
| [TAuthorizationRule](../TAuthorizationRule.md) | Single access-control rule (action, users, roles, verb, IPs, priority) |
| [TAuthorizationRuleCollection](../TAuthorizationRuleCollection.md) | Ordered priority collection of rules; first match wins |
| [IPermissions](./IPermissions.md) | Interface for classes declaring their own permissions |
| [TPermissionsAction](./TPermissionsAction.md) | Shell action for listing/debugging roles and permissions | |

## Patterns & Gotchas

- **`TPermissionsManager` must be registered after `TAuthManager`** in `application.xml` — auth state must be established before behavior attachment and permission checks can run.
- **All permission and role names are normalized to lowercase** — `Blog_Edit` and `blog_edit` are the same.
- **`SuperRoles` receive the `all` virtual role** — this makes them pass every permission check. Use only for true super-admin scenarios.
- **`DefaultRoles` are not stored on the user** — they are injected at check time via [TUserPermissionsBehavior](./TUserPermissionsBehavior.md). Changing `DefaultRoles` in config takes effect immediately.
- **The special `all` role** is auto-created and contains every registered permission. Giving a role `all` as a child is equivalent to making it a super role.
- **Auto deny-all is lazy** — the `*` deny rule is only injected when the first `registerPermission()` call is made, not during `init()`.
- **Wildcard `permissionrule` names** (e.g., `blog_*`) apply to all matching registered permissions AND are stored in `$_autoRules` to apply to future registrations.
- **Rules propagate down the hierarchy** — a rule on a parent role/permission is pushed to all children recursively when `addPermissionRuleInternal()` is called.
- **Circular hierarchy** — `isInHierarchy()` and `addPermissionRuleInternal()` both guard against infinite loops via a visited-set.
- **`AutoAllowWithPermission`** — by default, if a user's roles include the permission name itself (because the role hierarchy includes it), they are automatically allowed. Disable with `AutoAllowWithPermission="false"` if you want fully explicit rules.
- **All config properties are read-only after `init()`** — setting them post-init throws [TInvalidOperationException](../../Exceptions/TInvalidOperationException.md).
- **`__destruct()` detaches all three class behaviors** — this is important in long-running CLI processes where the module may be destroyed and re-created.
- **`TDbParameterModule` runtime data** is loaded during `init()` and merged on top of static config — runtime changes via `addRoleChildren()` / `addPermissionRule()` take effect immediately in the current request.

---

## TPermissionEvent

**Location:** `framework/Security/Permissions/TPermissionEvent.php`

A simple data-container [TComponent](../../TComponent.md) that links a permission name to one or more dynamic events and optional preset [TAuthorizationRule](../TAuthorizationRule.md) objects.

### Constructor

```php
public function __construct(
    string $permissionName = '',
    string $description = '',
    string|string[] $events = [],
    ?TAuthorizationRule[] $rules = null
)
```

### Properties

| Property | Type | Notes |
|---|---|---|
| `Name` | `string` | Forced lowercase. The permission identifier. |
| `Description` | `string` | Short human-readable description. |
| `Events` | `string[]` | Dynamic event names (e.g., `'dyPermissionAction'`). Accepts comma-separated string or array. All forced lowercase. |
| `Rules` | `TAuthorizationRule[]` | Preset rules applied when the permission is registered (subject to `AutoPresetRules`). Single rule is auto-wrapped in array. |

### Usage Pattern

```php
public function getPermissions($manager) {
    return [
        new TPermissionEvent(
            'blog_post_edit',
            'Edit blog posts.',
            ['dyEditPost', 'dyDeletePost'],
            [new TAuthorizationRule('allow', '*', 'blog_editor')]
        ),
    ];
}
```

---

## TPermissionsBehavior

**Location:** `framework/Security/Permissions/TPermissionsBehavior.php`

A class behavior automatically attached (by `TPermissionsManager::init()`) to every class implementing [IPermissions](./IPermissions.md). It intercepts dynamic events and enforces permission checks.

### Interfaces Implemented

- `IDynamicMethods` (implements `__dycall`)
- Extends [TBehavior](../../Util/TBehavior.md)
- Uses [TPermissionsManagerPropertyTrait](./TPermissionsManagerPropertyTrait.md)

### Key Behavior

**`attach($owner)`** — on attachment, calls `$owner->getPermissions($manager)`, registers each returned [TPermissionEvent](./TPermissionEvent.md) with the manager, and builds an internal map of `[dynamic_event => [permission_names]]`.

**`__dycall(string $method, array $args)`** — intercepts all `dy*` calls on the owner. If the event name is in the permission map:
1. Calls `$user->can($permission, $extra)` for each mapped permission.
2. If the user lacks permission, returns `true` (blocks the action) OR propagates the call chain result.
3. If the user has permission, the call chain continues normally.

The `$extra` data is extracted from the last argument if it is an array with an `'extra'` key.

**`dyLogPermissionFailed($permission, $action, $callchain)`** — logs permission failures via `Prado::log()` (WARNING level). In [TShellApplication](../../Shell/TShellApplication.md), also writes to the shell writer. In non-Debug mode, hides the exact permission name from shell output for security.

### Key Method

```php
public function getPermissionEvents(): TPermissionEvent[]
```
Returns the [TPermissionEvent](./TPermissionEvent.md) array that was provided by the owner's `getPermissions()` call.

### Pattern: Implementing a Permission-Gated Method

```php
// In IPermissions class:
public function getPermissions($manager) {
    return [new TPermissionEvent('my_action', 'Do my action.', 'dyMyAction')];
}

public function doMyAction($param) {
    // First argument to dy* call is the "default return value" = false (not blocked)
    if ($this->dyMyAction(false, $param) === true) {
        return; // permission denied — behavior returned true to block
    }
    // ... proceed with action
}
```

### Gotchas

- `__dycall` only fires for events listed in [TPermissionEvent](./TPermissionEvent.md)::`getEvents()`. Unregistered events pass through unchanged.
- The [TCallChain](../../Util/TCallChain.md) must be the last argument in `$args` — if it is missing, the behavior returns `$args[0]` unchanged (no-op).
- Permission check uses `$user->can()` from [TUserPermissionsBehavior](./TUserPermissionsBehavior.md) — if that behavior is not attached (no `TPermissionsManager`), `can()` does not exist and the check is skipped.
- Attachment happens only if `TPermissionsManager` is resolvable via `getPermissionsManager()` (from the trait) at behavior-attach time.
