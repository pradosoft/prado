# TApplicationConfiguration

**Location:** `framework/TApplicationConfiguration.php`
**Namespace:** `Prado`

## Overview

Used internally by `TApplication` to parse and represent the `application.xml` (or `application.php`) configuration file. Holds the parsed result as structured arrays for properties, aliases, modules, services, and parameters.

Not typically used directly by application code — `TApplication` uses it during `initApplication()`.

## Constants

```php
TApplicationConfiguration::COMPOSER_INSTALLED_CACHE  // 'prado:composer:installedcache'
TApplicationConfiguration::COMPOSER_EXTRA_CLASS       // 'bootstrap' — Composer extra field
```

## Internal Data Structures

After parsing, the following arrays are populated:

| Property | Content |
|----------|---------|
| `$_properties` | Application-level properties (from XML root attributes) |
| `$_aliases` | Path aliases: `['alias' => '/absolute/path']` |
| `$_usings` | Namespaces to import |
| `$_modules` | Module configs: `[id => [class, properties, raw_element]]` |
| `$_services` | Service configs: `[id => [class, properties, raw_element]]` |
| `$_parameters` | Parameters: `[id => value_or_[class, properties, raw_element]]` |
| `$_includes` | External config includes (with optional `when=` conditions) |

## Key Methods

```php
$config->loadFromFile(string $fname): void       // dispatches to loadFromXml or loadFromPhp
$config->loadFromXml(TXmlElement $dom, string $path): void
$config->loadFromPhp(array $config, string $path): void
$config->getIsEmpty(): bool                       // true if no config was parsed
```

Access the parsed data via getter methods called by `TApplication`:

```php
$config->getProperties()
$config->getAliases()
$config->getUsings()
$config->getModules()
$config->getServices()
$config->getParameters()
$config->getExternalConfigurations()
```

## PHP Config Format

Alternative to XML: return an associative array from a `.php` config file.

```php
return [
    'application' => ['Mode' => 'Debug'],
    'paths' => [
        'aliases' => ['App' => 'protected/'],
        'using'   => ['System.Web'],
    ],
    'modules' => [
        'db' => ['class' => 'Prado\Data\TDbConnection',
                 'properties' => ['ConnectionString' => 'sqlite:...']],
    ],
    'services' => [
        'page' => ['class' => 'Prado\Web\Services\TPageService',
                   'properties' => ['BasePath' => 'App.Pages']],
    ],
    'parameters' => [
        'siteName' => 'My Site',
    ],
    'includes' => [
        ['file' => 'protected/config/extra.xml', 'when' => ''],
    ],
];
```

## Composer Extension Discovery

`TApplicationConfiguration` scans installed Composer packages for Prado extensions:
- Looks for `extra.prado.bootstrap` key in each package's `composer.json`.
- Caches the discovered extension list in the application cache under `COMPOSER_INSTALLED_CACHE`.
- Bootstrap classes are instantiated and their `init()` is called during application init.

## Patterns & Gotchas

- **Module triples** — modules, services, and parameters are stored as `[class, properties_array, raw_element]`. `TApplication` uses the triple to instantiate and configure each module.
- **Conditional includes** — `<include file="..." when="PHP_expression" />` includes are only loaded when the `when` expression evaluates to true. Useful for environment-specific config.
- **Config type** — determined by `TApplication::getConfigurationType()` (returns `CONFIG_TYPE_XML` or `CONFIG_TYPE_PHP`). The type is set based on the config file extension.
- **Path resolution** — relative paths in aliases are resolved relative to the config file's directory (`$configPath`).
- **`getIsEmpty()`** — returns `true` if no meaningful config was found (e.g., an empty XML file). `TApplication` uses this to skip unnecessary processing.
