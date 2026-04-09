# Shell / Actions / TWebServerAction

### Directories
[./](../INDEX.md) > [Shell](../INDEX.md) > [Actions](./INDEX.md) > [TWebServerAction](./TWebServerAction.md)

**Location:** `framework/Shell/Actions/TWebServerAction.php`
**Namespace:** `Prado\Shell\Actions`

## Overview

Starts PHP's built-in development web server to serve the PRADO application.

## Usage

```bash
# Start on default 127.0.0.1:8080
php prado-cli.php http/serve

# Specify address
php prado-cli.php http/serve --address=localhost:8777

# Listen on all interfaces
php prado-cli.php http/serve --all

# IPv6
php prado-cli.php http/serve --ipv6

# Multiple workers
php prado-cli.php http/serve --workers=4
```

## Options

| Option | Alias | Default | Description |
|--------|-------|---------|-------------|
| `--address` | `-a` | `127.0.0.1` | Network address |
| `--port` | `-p` | `8080` | Port number |
| `--ipv6` | `-6` | `false` | Use IPv6 |
| `--all` | `-i` | `false` | Listen on all addresses |
| `--workers` | `-w` | `1` | Number of workers |

## Action Definition

| Property | Value |
|----------|-------|
| `action` | `'http'` |
| `methods` | `['serve']` |

## Notes

- Only available in Debug mode or when `Prado:PhpWebServer` parameter is `true`
- Uses PHP's built-in web server via `proc_open()`
- Supports router scripts

## See Also

- [TShellAction](../TShellAction.md) - Base class
- PHP Manual: Built-in web server