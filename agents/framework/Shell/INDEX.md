# Shell/INDEX.md

### Directories
[framework](./INDEX.md) / **`Shell/INDEX.md`**

## Purpose

CLI application support for the Prado framework. Allows command-line scripts to access the full application configuration, modules, and services via the `prado-cli` executable (`bin/prado-cli`).

## Classes

- **[`TShellApplication`](TShellApplication.md)** — Extends [`TApplication`](TApplication.md) for CLI contexts. Loads the same `application.xml` as the web app; all configured modules are available. Properties: `QuietMode` (0=normal, 1=quiet, 2=silent), `OutputWriter`. Methods: `run()`, `renderException()`, `processRequest()`. Registers application shell actions on startup.

- **[`TShellAction`](TShellAction.md)** — Abstract base for CLI commands. Subclass and implement `run(array $args)`. Define `Action` (command name), `Parameters` (required), `Optional` (optional params), `Methods` (subcommands). Use `getWriter()` for output. `options()` method provides parameter definitions.

- **[`TShellWriter`](TShellWriter.md)** — Formatted console output. Methods: `write($text, $format)`, `writeLine($text, $format)`. Format constants: `NORMAL`, `INFO`, `SUCCESS`, `WARNING`, `ERROR`, `DEBUG`. Supports tables, progress indicators, block comments, and section headers.

- **[`TShellLoginBehavior`](TShellLoginBehavior.md)** — Behavior that prompts for user credentials at CLI startup. Integrates with [`TUserManager`](TUserManager.md); sets `Prado::getUser()` for the authenticated context.

## Subdirectory: [`Actions/`](Actions/INDEX.md)

Built-in shell commands:

| Class | Command | Purpose |
|---|---|---|
| [`THelpAction`](Actions/THelpAction.md) | `help` | List available commands and usage |
| [`TActiveRecordAction`](Actions/TActiveRecordAction.md) | `activerecord` | Generate AR class skeletons from DB tables (`generate`, `generate-all`) |
| [`TDbParameterAction`](Actions/TDbParameterAction.md) | `param` | Manage [`TDbParameterModule`](../Util/TDbParameterModule.md) entries (list, get, set, delete) |
| [`TPhpShellAction`](Actions/TPhpShellAction.md) | `shell` | Interactive PHP REPL with bootstrapped app context |
| [`TWebServerAction`](Actions/TWebServerAction.md) | `serve` | Start PHP's built-in dev web server (`--address=host:port`) |
| [`TFlushCachesAction`](Actions/TFlushCachesAction.md) | `cache` | List, flush one, or flush all `ICache` modules |
| [`TShellCronAction`](Actions/TShellCronAction.md), [`TShellDbCronAction`](Actions/TShellDbCronAction.md) | `cron` | Manually trigger cron tasks (see `Util/Cron/`) |

## Patterns & Conventions

- **Action naming** — Command name is derived from class name: `THelpAction` → `help`.
- **Output** — Always use [`TShellWriter`](TShellWriter.md) for consistent formatting. Access via `$this->getWriter()`.
- **Exit codes** — Return non-zero from `run()` to signal error.
- **User context** — Attach [`TShellLoginBehavior`](TShellLoginBehavior.md) to require authentication before action runs.
- **Quiet mode** — Check `$this->getApplication()->getQuietMode()` before verbose output.
