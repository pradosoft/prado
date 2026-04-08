# Shell/INDEX.md - SHELL_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

CLI application support for the Prado framework. Allows command-line scripts to access the full application configuration, modules, and services via the `prado-cli` executable (`bin/prado-cli`).

## Classes

- **`TShellApplication`** — Extends `TApplication` for CLI contexts. Loads the same `application.xml` as the web app; all configured modules are available. Properties: `QuietMode` (0=normal, 1=quiet, 2=silent), `OutputWriter`. Methods: `run()`, `renderException()`, `processRequest()`. Registers application shell actions on startup.

- **`TShellAction`** — Abstract base for CLI commands. Subclass and implement `run(array $args)`. Define `Action` (command name), `Parameters` (required), `Optional` (optional params), `Methods` (subcommands). Use `getWriter()` for output. `options()` method provides parameter definitions.

- **`TShellWriter`** — Formatted console output. Methods: `write($text, $format)`, `writeLine($text, $format)`. Format constants: `NORMAL`, `INFO`, `SUCCESS`, `WARNING`, `ERROR`, `DEBUG`. Supports tables, progress indicators, block comments, and section headers.

- **`TShellLoginBehavior`** — Behavior that prompts for user credentials at CLI startup. Integrates with `TUserManager`; sets `Prado::getUser()` for the authenticated context.

## Subdirectory: [`Actions/`](SHELL_ACTIONS_INDEX.md)

Built-in shell commands:

| Class | Command | Purpose |
|---|---|---|
| `THelpAction` | `help` | List available commands and usage |
| `TActiveRecordAction` | `activerecord` | Generate AR class skeletons from DB tables (`generate`, `generate-all`) |
| `TDbParameterAction` | `param` | Manage `TDbParameterModule` entries (list, get, set, delete) |
| `TPhpShellAction` | `shell` | Interactive PHP REPL with bootstrapped app context |
| `TWebServerAction` | `serve` | Start PHP's built-in dev web server (`--address=host:port`) |
| `TFlushCachesAction` | `cache` | List, flush one, or flush all `ICache` modules |
| `TShellCronAction`, `TShellDbCronAction` | `cron` | Manually trigger cron tasks (see `Util/Cron/`) |

## Patterns & Conventions

- **Action naming** — Command name is derived from class name: `THelpAction` → `help`.
- **Output** — Always use `TShellWriter` for consistent formatting. Access via `$this->getWriter()`.
- **Exit codes** — Return non-zero from `run()` to signal error.
- **User context** — Attach `TShellLoginBehavior` to require authentication before action runs.
- **Quiet mode** — Check `$this->getApplication()->getQuietMode()` before verbose output.
