# Util/TPluginModule

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TPluginModule`**

## Class Info
**Location:** `framework/Util/TPluginModule.php`
**Namespace:** `Prado\Util`
**Extends:** [`TModule`](../TModule.md)
**Implements:** `IPluginModule`
**Since:** 4.2.0

## Overview
`TPluginModule` is the base module class for Composer-package plugins. On `init()` it auto-discovers a `Pages/` subdirectory inside the plugin package, registers it with `TPageService` via event hooks, and registers any `errorMessages.txt` found in the plugin root with Prado's exception system. Subclasses gain page-serving capability simply by placing pages under the Pages directory and declaring themselves a module in `application.xml`.

## Constants

| Constant | Value |
|----------|-------|
| `PAGES_DIRECTORY` | `'Pages'` |

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `PluginPath` | `string` | subclass file directory | Filesystem root of the Composer plugin package. |
| `RelativePagesPath` | `string` | `'Pages'` | Pages folder path relative to `PluginPath`. |
| `PluginPagesPath` | `string\|false\|null` | computed | Resolved absolute path to the Pages directory. |

## Key Methods

| Method | Description |
|--------|-------------|
| `init($config)` | Wires the page-service behavior hook and registers the plugin error-message file. |
| `getErrorFile(): ?string` | Returns the path to `errorMessages.txt` inside `PluginPath`, or `null` if absent. |

## Configuration

**application.xml:**
```xml
<modules>
  <module id="myplugin" class="MyVendor\MyPlugin\Module" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'myplugin' => [
            'class' => 'MyVendor\MyPlugin\Module',
        ],
    ],
];
```

## See Also

- [`TDbPluginModule`](./TDbPluginModule.md) — extends this with database connectivity
- `IPluginModule` — interface defining `getPluginPath()`
