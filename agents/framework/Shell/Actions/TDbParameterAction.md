# Shell / Actions / TDbParameterAction

### Directories
[./](../INDEX.md) > [Shell](../INDEX.md) > [Actions](./INDEX.md) > [TDbParameterAction](./TDbParameterAction.md)

**Location:** `framework/Shell/Actions/TDbParameterAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview

Manages `TDbParameterModule` database parameters from the command line.

## Usage

```bash
# List all parameters
php prado-cli.php param

# List all including non-DB
php prado-cli.php param --all

# Get specific parameter
php prado-cli.php param/get mykey

# Set parameter
php prado-cli.php param/set mykey myvalue
```

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'param'` |
| `methods` | `['index', 'get', 'set']` |

## Subcommands

### `param` (index)
Lists all parameters from `TDbParameterModule`.

### `param/get <param-key>`
Displays a specific parameter's value.

### `param/set <param-key> <param-value>`
Sets a parameter value in the database.

## Options

| Option | Alias | Description |
|--------|-------|-------------|
| `--all` | `-a` | Show all parameters, not just DB ones |

## See Also

- [TShellAction](../TShellAction.md) - Base class
- [TDbParameterModule](../../Util/TDbParameterModule.md) - Parameter storage module