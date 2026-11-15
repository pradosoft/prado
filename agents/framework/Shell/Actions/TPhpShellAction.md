# Shell/Actions/TPhpShellAction

### Directories
[framework](../../INDEX.md) / [Shell](../INDEX.md) / [Actions](./INDEX.md) / **`TPhpShellAction`**

## Class Info
**Location:** `framework/Shell/Actions/TPhpShellAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview
Starts an interactive PHP shell (REPL) with the PRADO application bootstrapped.

## Usage

```bash
php prado-cli.php shell
```

This starts [PsySH](https://psysh.org/), a PHP interactive shell, with the PRADO application bound as `$this`, so `$this->getModule('cache')` and the other application methods work at the prompt.

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