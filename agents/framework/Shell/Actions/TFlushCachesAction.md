# Shell/Actions/TFlushCachesAction

### Directories
[framework](../../INDEX.md) / [Shell](../INDEX.md) / [Actions](./INDEX.md) / **`TFlushCachesAction`**

## Class Info
**Location:** `framework/Shell/Actions/TFlushCachesAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview
Lists and flushes application cache modules implementing `ICache`.

## Usage

```bash
# List available caches
php prado-cli.php cache

# Flush specific cache
php prado-cli.php cache/flush cacheid

# Flush all caches
php prado-cli.php cache/flush-all
```

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'cache'` |
| `methods` | `['index', 'flush', 'flush-all']` |
| `parameters` | `[null, 'module', null]` |

## Subcommands

### `cache` (index)
Lists all available cache modules by ID and class name.

### `cache/flush <module>`
Flushes a specific cache module by ID.

### `cache/flush-all`
Flushes all registered cache modules.

## See Also

- [TShellAction](../TShellAction.md) - Base class
- [ICache](../../Caching/ICache.md) - Cache interface