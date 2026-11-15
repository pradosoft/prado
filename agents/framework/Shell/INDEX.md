# Shell/INDEX.md

### Directories
[framework](../INDEX.md) / **`Shell`**

## Purpose

CLI application support for the Prado framework. The `prado-cli` executable (`bin/prado-cli`) runs a [`TShellApplication`](TShellApplication.md) that loads the application configuration, so command-line actions use the same modules, parameters, and path aliases as the web application.

## Classes

- **[`TShellApplication`](TShellApplication.md)** — Extends [`TApplication`](../TApplication.md) for CLI contexts. Loads the same `application.xml` as the web app; all configured modules are available. Properties: `QuietMode` (`QUIET_NONE` `0` full output to `QUIET_ERRORS` `3`), `Writer`; `writeError()` blocks go to standard error through `createStdErrStream()`. Methods: `run()`, `runService()`, `printHelp()`, `installShellActions()`, `addShellActionClass()`, `registerOption()`, `registerOptionAlias()`.

- **[`TShellAction`](TShellAction.md)** — Abstract base for CLI commands. A subclass sets `$action` (the command name), `$methods` (subcommands), `$parameters` (required), `$optional`, and `$description`, and implements `action<Method>($args)` for each entry of `$methods`. `options()` and `optionAliases()` declare per-method `--option` flags. Output goes through `getWriter()`.

- **[`TShellWriter`](TShellWriter.md)** — ANSI console output over an `ITextWriter`. Properties: `ErrorStream`, `ColorSupported` (from `NO_COLOR`, `FORCE_COLOR`, `CLICOLOR_FORCE`, and the terminal), `Width`. Methods: `write($str, $attr)`, `writeLine($str, $attr)`, `writeError($text)`, `format()`, `unformat()`, `pad()`, `tableWidget()`, `wrapText()`, cursor and screen control. Attribute constants are ANSI styles and colors: `BOLD`, `UNDERLINE`, `RED`, `GREEN`, `BLUE`, `DARK_GRAY`, `BG_RED`, and so on.

- **[`TShellLoginBehavior`](TShellLoginBehavior.md)** — Behavior for [`TAuthManager`](../Security/TAuthManager.md) that logs in a user before a shell action runs. It handles `OnAuthenticate`, takes `--user` and `--password` or prompts for them, and calls `TAuthManager::login()`.

## Subdirectory: [`Actions/`](Actions/INDEX.md)

Built-in shell commands:

| Class | Command | Purpose |
|---|---|---|
| [`THelpAction`](Actions/THelpAction.md) | `help` | List available commands, or the subcommands of one |
| [`TDbParameterAction`](Actions/TDbParameterAction.md) | `param` | List, get, and set [`TDbParameterModule`](../Util/TDbParameterModule.md) parameters |
| [`TPhpShellAction`](Actions/TPhpShellAction.md) | `shell` | Interactive PHP REPL with the application bound |
| [`TWebServerAction`](Actions/TWebServerAction.md) | `http` | `http/serve` starts PHP's built-in dev web server (`--address=host:port`) |
| [`TFlushCachesAction`](Actions/TFlushCachesAction.md) | `cache` | List, flush one, or flush all `ICache` modules |
| [`TShellCronAction`](../Util/Cron/TShellCronAction.md), [`TShellDbCronAction`](../Util/Cron/TShellDbCronAction.md) | `cron` | Run and manage cron tasks (see `Util/Cron/`) |

Modules register their own actions with `addShellActionClass()`: `perm` ([`TPermissionsManager`](../Security/Permissions/TPermissionsManager.md)), `socket` (`TSocketServerModule`, [IO/Socket](../IO/Socket/INDEX.md)), and `activerecord` (`pradosoft/prado-activerecord`).

## Patterns & Conventions

- **Action naming** — the command name is the `$action` property; `<action>/<method>` selects a subcommand, and `$defaultMethod` (index `0`) runs without one.
- **Output** — use [`TShellWriter`](TShellWriter.md) through `$this->getWriter()`.
- **Return values** — an `action<Method>()` returns `true` when it handled the command. A falsy return makes `runService()` print the general help when `QuietMode` is `0`. The process exits with status `0` unless an action throws `TExitException`.
- **User context** — attach [`TShellLoginBehavior`](TShellLoginBehavior.md) to `TAuthManager` to require a login before actions run.
- **Quiet mode** — check `$this->getApplication()->getQuietMode()` before verbose output.
