# Shell / Actions / TPhpShellAction

### Directories
[./](../INDEX.md) > [Shell](../INDEX.md) > [Actions](./INDEX.md) > [TPhpShellAction](./TPhpShellAction.md)

**Location:** `framework/Shell/Actions/TPhpShellAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview

Starts an interactive PHP shell (REPL) with the PRADO application bootstrapped.

## Usage

```bash
php prado-cli.php shell
```

This starts [PsySH](https://psysh.org/), a PHP interactive shell, with access to:
- `$app` - The PRADO application
- All application services and modules

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'shell'` |
| `methods` | `['index']` |
| `parameters` | `[null]` |

## Requirements

Requires `psy/psysh` package.

## See Also

- [TShellAction](../TShellAction.md) - Base class