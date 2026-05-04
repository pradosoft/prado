# Shell/Actions/THelpAction

### Directories
[framework](../../INDEX.md) / [Shell](../INDEX.md) / [Actions](./INDEX.md) / **`THelpAction`**

## Class Info
**Location:** `framework/Shell/Actions/THelpAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview
Displays help information for shell commands.

## Usage

```bash
# List all commands
php prado-cli.php help

# Get help for specific command
php prado-cli.php help cache
php prado-cli.php help activerecord/generate
```

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'help'` |
| `methods` | `['index']` |
| `parameters` | `[null]` |
| `optional` | `['command']` |

## See Also

- [TShellAction](../TShellAction.md) - Base class
- [TShellApplication](../TShellApplication.md) - Registers this action automatically