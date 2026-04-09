# TAPCCache

### Directories

[./](../INDEX.md) > [Caching](./INDEX.md) > [TAPCCache](./TAPCCache.md)

**Location:** `framework/Caching/TAPCCache.php`
**Namespace:** `Prado\Caching`

## Overview

`TAPCCache` provides in-memory caching using the APCu PHP extension. Data is stored in shared memory and persists for the duration of the PHP process (or PHP-FPM request).

## Quick Start

```php
$cache = new TAPCCache();
$cache->init(null);
$cache->add('key', $value);
$value = $cache->get('key');
```

## Configuration

```xml
<module id="cache" class="Prado\Caching\TAPCCache" />
```

## Requirements

- PHP extension: `apcu`
- `apc.enabled=1` in php.ini
- For CLI: `apc.enable_cli=1`

## See Also

- [`TCache`](TCache.md) for full caching documentation
