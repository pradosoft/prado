# Web/Services/TPageConfiguration

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TPageConfiguration`**

## Class Info
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

## Matching and Includes

- A rule's `pages` and a page's `id` are relative to the directory of the configuration file (`Users` in `Admin/config.xml` is `Admin.Users`), match case-insensitively, and end in `*` to match a prefix. A rule without `pages`, or with `*`, applies to every page.
- Rules of a deeper directory come before those of its parents.
- A PHP page key is read as a string, so a numeric key such as `404` names its page; an empty key, like an XML `<page>` without an `id`, throws `pageserviceconf_page_invalid`.
- An include without `when` always applies; with `when`, `TPageService` evaluates the expression. A file included twice applies when either condition holds. PHP and XML read `when` the same way.

## See Also

- [TPageService](TPageService.md) - Uses TPageConfiguration for page setup
- [TAuthorizationRule](../../Security/TAuthorizationRule.md) - Authorization rules
