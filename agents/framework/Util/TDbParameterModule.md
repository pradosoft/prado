# TDbParameterModule

### Directories

[Util](../) > TDbParameterModule

**Location:** `framework/Util/TDbParameterModule.php`
**Namespace:** `Prado\Util`

## Overview

Database-backed parameter store. Extends `[TModule](TModule.md)` (not `[TParameterModule](TParameterModule.md)`) and implements `[IDbModule](IDbModule.md)` and `IPermissions`. Stores named key-value pairs in a database table, with optional in-memory capture of changes made to `[TApplication](../TApplication.md)::getParameters()` during the request.

Supports WordPress-style `option_name`/`option_value` schema (configurable), auto-table creation, and custom serialization.

## Constants

```php
TDbParameterModule::SERIALIZE_PHP  = 'php'   // PHP serialize/unserialize (default)
TDbParameterModule::SERIALIZE_JSON = 'json'  // JSON encode/decode
TDbParameterModule::PERM_PARAM_SHELL = 'param_shell'  // shell action permission

TDbParameterModule::APP_PARAMETER_LAZY_BEHAVIOR = 'lazyTDbParameter'
TDbParameterModule::APP_PARAMETER_SET_BEHAVIOR  = 'setTDbParameter'
```

## Configuration

```xml
<module id="dbparams" class="Prado\Util\TDbParameterModule"
        ConnectionID="db"
        TableName="prado_params"
        KeyField="param_key"
        ValueField="param_value"
        AutoLoadField="auto_load"
        AutoLoadValue="1"
        AutoCreateParamTable="true"
        CaptureParameterChanges="true"
        Serializer="php" />
```

Default SQLite database is used if `ConnectionID` is not specified.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `ConnectionID` | string | null | `[TDbConnection](../Data/TDbConnection.md)` module ID (defaults to SQLite) |
| `TableName` | string | `prado_params` | Database table name |
| `KeyField` | string | `param_key` | Column name for parameter key |
| `ValueField` | string | `param_value` | Column name for parameter value |
| `AutoLoadField` | string | `auto_load` | Column name for auto-load flag |
| `AutoLoadValue` | string | `1` | Value in `AutoLoadField` that means "load on init" |
| `AutoLoadValueFalse` | string | `0` | Value meaning "do not auto-load" |
| `AutoCreateParamTable` | bool | true | Create table if it doesn't exist |
| `CaptureParameterChanges` | bool | false | Auto-write changes to `$app->getParameters()` back to DB |
| `Serializer` | string\|callable | `'php'` | Serialization: `'php'`, `'json'`, or `callable($data, $encode)` |

## Key Methods

```php
$module->get(string $key, bool $checkParameter = true, bool $setParameter = true): mixed
$module->set(string $key, mixed $value, bool $autoLoad = true, bool $setParameter = true): void
$module->exists(string $key): bool
$module->remove(string $key): void
$module->getDbConnection(): [TDbConnection](../Data/TDbConnection.md)
$module->getPermissions($manager): array   // IPermissions: registers PERM_PARAM_SHELL
```

## IPermissions Integration

Registers the `param_shell` permission with `[TPermissionsManager](../Security/Permissions/TPermissionsManager.md)`, controlling access to the CLI shell action:

```xml
<module id="permissions" class="Prado\Security\Permissions\TPermissionsManager">
    <permission name="param_shell" roles="admin" />
</module>
```

## Lazy Loading Behavior

`TDbParameterModule` attaches `[TMapLazyLoadBehavior](Behaviors/TMapLazyLoadBehavior.md)` (as `APP_PARAMETER_LAZY_BEHAVIOR`) to the application parameter map, enabling parameters to be loaded from the database on first access rather than all at startup.

## Capture Behavior

When `CaptureParameterChanges=true`, the module attaches `APP_PARAMETER_SET_BEHAVIOR` to the parameter map. Any `$params['key'] = $value` assignment is automatically persisted to the database.

## WordPress-Compatible Schema

To use a WordPress `wp_options` table:

```xml
<module id="dbparams" class="Prado\Util\TDbParameterModule"
        ConnectionID="wp"
        TableName="wp_options"
        KeyField="option_name"
        ValueField="option_value"
        AutoLoadField="autoload"
        AutoLoadValue="yes"
        AutoLoadValueFalse="no"
        Serializer="php" />
```

## CLI Shell Action

```bash
php prado-cli.php /path/to/app param list
php prado-cli.php /path/to/app param get <key>
php prado-cli.php /path/to/app param set <key> <value>
php prado-cli.php /path/to/app param delete <key>
```

## Patterns & Gotchas

- **Must be declared before modules that depend on it** — e.g., `[TPermissionsManager](../Security/Permissions/TPermissionsManager.md)` reads dynamic roles from this module on init.
- **Default SQLite** — if `ConnectionID` is omitted, a local SQLite file in `runtime/` is used. Not appropriate for multi-server deployments.
- **`CaptureParameterChanges` and performance** — enabling this attaches a behavior that intercepts every parameter write. Avoid in high-throughput scenarios.
- **Serializer callable signature** — `function(mixed $data, bool $encode): mixed` — `$encode=true` for serialization, `false` for deserialization.
- **`AutoLoadValue`** — only rows with `AutoLoadField = AutoLoadValue` are loaded into `$app->getParameters()` on init. Others are loaded lazily via the lazy behavior.
