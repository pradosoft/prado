# Util/TDbPluginModule

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TDbPluginModule`**

## Class Info
**Location:** `framework/Util/TDbPluginModule.php`
**Namespace:** `Prado\Util`
**Extends:** [`TPluginModule`](./TPluginModule.md)
**Implements:** `IDbModule`
**Since:** 4.2.0

## Overview
`TDbPluginModule` extends `TPluginModule` with standardised database connectivity by composing `TDbPropertiesTrait`. It enforces that `ConnectionID` cannot be changed after the module has been initialised, enabling `TParameterizeBehavior` to uniformly configure all `TDbPluginModule` instances from a single application-level parameter.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `ConnectionID` | `string` | ID of the `TDataSourceConfig` module that supplies the DB connection. Cannot be changed after `init()`. |
| `DbConnection` | [`TDbConnection`](../Data/TDbConnection.md) | The active PDO-wrapper connection instance (provided by trait). |

## Configuration

**application.xml:**
```xml
<modules>
  <module id="myplugin" class="MyVendor\MyPlugin\Module" ConnectionID="db" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'myplugin' => [
            'class' => 'MyVendor\MyPlugin\Module',
            'properties' => ['ConnectionID' => 'db'],
        ],
    ],
];
```

## See Also

- [`TPluginModule`](./TPluginModule.md) — parent class
- [`TDbModule`](./TDbModule.md) — alternative base for non-plugin DB modules
- `IDbModule` — interface defining `getDbConnection()` and `ConnectionID`
