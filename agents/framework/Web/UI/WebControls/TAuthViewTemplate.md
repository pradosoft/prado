# Web/UI/WebControls/TAuthViewTemplate

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TAuthViewTemplate`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TAuthViewTemplate.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Extends:** [TControl](../TControl.md)

## Overview
TAuthViewTemplate is one case of a [TAuthView](./TAuthView.md): content and the conditions a user must meet for it to render. It renders no tag of its own. Its `onInit` throws `TConfigurationException` (`authviewtemplate_parent_invalid`) when its parent is not a TAuthView, so a misplaced case never shows its content to everyone.

The `Template` suffix names a case; it is a control tag, so the template parser builds its content with the page. Only `<prop:XxxTemplate>` properties are compiled into lazy `ITemplate`s.

## Conditions

| Property | Default | Matches when |
|---|---|---|
| `Users` | `@` | the user is in the list: a name, `*` everyone, `?` a guest, `@` an authenticated user; `''` restores `@` |
| `Roles` | `''` | `IUser::isInRole()` is true for any listed role; `''` is any role |
| `Permissions` | `''` | `can()` of the user is true for any listed permission; `''` is no check |

Each property is a comma-separated list. A case matches when every condition that is set matches. `Users` and `Roles` are matched by a [TAuthorizationRule](../../../Security/TAuthorizationRule.md) built from them, with its syntax and case-insensitive names.

`Permissions` requires [TPermissionsManager](../../../Security/Permissions/TPermissionsManager.md), whose [TUserPermissionsBehavior](../../../Security/Permissions/TUserPermissionsBehavior.md) gives users `can()`. A user without `can()` throws `TConfigurationException` (`authviewtemplate_permissions_unsupported`).

## Key Methods

| Method | Description |
|---|---|
| `isUserMatched(?IUser $user)` | Whether the user meets the conditions; false for null |
| `isPermissionMatched(IUser $user)` | Whether the user has a listed permission (protected) |
| `canAny(TComponent $user, array $permissions)` | Whether `can()` is true for any permission; throws without `can()` (protected) |

## Template Usage

```xml
<com:TAuthViewTemplate Roles="admin, editor" Permissions="blog.post.edit">
	<a href="...">Edit posts</a>
</com:TAuthViewTemplate>
```

## Tests

- `tests/unit/Web/UI/WebControls/TAuthViewTest.php`

## See Also

- [TAuthView](./TAuthView.md)
- [TAuthorizationRule](../../../Security/TAuthorizationRule.md)

**@since 4.4.0**
