# TPageConfiguration

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TPageConfiguration](./TPageConfiguration.md)

**Location:** `framework/Web/Services/TPageConfiguration.php`
**Namespace:** `Prado\Web\Services`

## Overview

TPageConfiguration represents the merged configuration for a page, loaded from per-directory `config.xml` or `config.php` files along the page path. It aggregates application configurations, authorization rules, page properties, and external configuration includes. Configurations are merged bottom-up from parent directories to the specific page.

## Key Properties/Methods

- **`__construct($pagePath)`** - Creates a configuration for a page specified by dot-connected path.
- **`getProperties()`** - Returns merged page initial property values.
- **`getRules()`** - Returns aggregated TAuthorizationRuleCollection for the page.
- **`getApplicationConfigurations()`** - Returns list of application configurations.
- **`getExternalConfigurations()`** - Returns external configuration files and their conditions.
- **`loadFromFiles($basePath)`** - Loads configuration from files along the page path.
- **`loadFromFile($fname, $configPagePath)`** - Loads a specific configuration file.
- **`loadFromPhp($config, $configPath, $configPagePath)`** - Loads PHP configuration format.
- **`loadFromXml($dom, $configPath, $configPagePath)`** - Loads XML configuration format.
- **`loadPageConfigurationFromPhp()`** - Loads page-specific properties, authorization rules, and includes from PHP config.
- **`loadPageConfigurationFromXml()`** - Loads page-specific properties, authorization rules, and includes from XML config.

## See Also

- [TPageService](../TPageService.md) - Uses TPageConfiguration for page setup
- [TAuthorizationRule](../../Security/TAuthorizationRule.md) - Authorization rules
