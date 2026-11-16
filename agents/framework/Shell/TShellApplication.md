# Shell/TShellApplication

### Directories
[framework](../INDEX.md) / [Shell](./INDEX.md) / **`TShellApplication`**

## Class Info
**Location:** `framework/Shell/TShellApplication.php`
**Namespace:** `Prado\Shell`

## Overview
CLI application support for Prado. [TShellApplication](./TShellApplication.md) loads the full `application.xml` and makes all configured modules available to command-line scripts via the `prado-cli` binary (`bin/prado-cli`).

---

## TShellApplication

Extends [TApplication](../TApplication.md). Same configuration as the web app; all modules initialized identically. The CLI entry point is:

```bash
vendor/bin/prado-cli [-d=<folder>] [--quiet[=<level>]] <command>[/<method>] [args...]
```

`bin/prado-cli.php` finds the application configuration in the package's host project, the working directory, or its `protected/` folder; `-d=<folder>` (`--directory`) names the folder.

### Key Properties

| Property | Description |
|----------|-------------|
| `QuietMode` | `QUIET_NONE` (`0`, default) to `QUIET_ERRORS` (`3`); empty string maps to `QUIET_INFO` (`1`) |
| `Writer` | [TShellWriter](./TShellWriter.md) instance for console output |

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `SHELL_RUNTIME_PATH` | `'.runtime'` | Hidden runtime directory name used in no-config mode (@since 4.3.3) |
| `QUIET_NONE` | `0` | All output, with the greeting and the general help (@since 4.4.0) |
| `QUIET_INFO` | `1` | No greeting and no general help; a bare `--quiet` (@since 4.4.0) |
| `QUIET_OUTPUT` | `2` | Also no output from child processes an action runs (@since 4.4.0) |
| `QUIET_ERRORS` | `3` | Also no error messages from actions that check it (@since 4.4.0) |

---

## Startup Flow

`run()` is the main entry point. It:

1. Strips `$argv[0]` (script name) and stores the rest via `setArguments()`.
2. Maps `$_SERVER['LANG']` to HTTP language/charset headers (`detectShellLanguageCharset()`).
3. Creates the shell writer (`createShellWriter()` → `createStdOutWriter()`).
4. Registers the `--quiet` / `-q` application option.
5. Attaches `processArguments()` to `onConfiguration` at priority 20, which installs actions and strips global options from `$_arguments`.
6. Delegates to `parent::run()` (normal application lifecycle).
7. `runService()` dispatches the matching command action.

`initService()` is overridden to do nothing — shell applications do not start a web service (@since 4.3.3).

---

## Runtime Path Resolution (@since 4.3.3)

`resolveRuntimePath()` is overridden to handle the no-config mode (running `prado-cli` without an `application.xml`):

- **With config file** — delegates to `TApplication::resolveRuntimePath()` normally.
- **Without config file** (no-config mode) — two-level fallback:
  1. `<basePath>/.runtime` — created automatically if absent and writable (hidden dot-directory).
  2. `sys_get_temp_dir()/prado-<sha1(basePath)>/.runtime` — stable temp path keyed to the base path, so concurrent invocations of the same project share a runtime without colliding with other Prado installations.
  3. Throws `TConfigurationException` if neither location is writable.

---

## Conditional Action Registration

`installShellActions()` gates built-in commands on application state (@since 4.3.0):

| Method | Gate condition | Registered action |
|--------|---------------|-------------------|
| `hasCacheModules()` | `ICache` module(s) present | [TFlushCachesAction](./Actions/TFlushCachesAction.md) |
| always | — | [THelpAction](./Actions/THelpAction.md), [TPhpShellAction](./Actions/TPhpShellAction.md) |
| `hasDevWebServer()` | `Debug` mode or `TWebServerAction::DEV_WEBSERVER_PARAM` param truthy | [TWebServerAction](./Actions/TWebServerAction.md) |

Both gating methods (`hasCacheModules()`, `hasDevWebServer()`) are @since 4.3.3 and public — subclasses or tests can override them. `hasActiveRecordConfig()` (@since 4.3.3) is deprecated since 4.4.0: the `pradosoft/prado-activerecord` package's `TActiveRecordConfig` registers `TActiveRecordAction` from its own `init()`, so `installShellActions()` no longer gates on it.

`hasShellActionClass(string $class): bool` checks whether a given class is already registered (@since 4.3.3).

---

## Option & Argument System

Application-level CLI options (e.g., `--quiet`, `-q`) are registered before dispatch and stripped from `$_arguments` by `processArguments()`. Actions can also declare per-command options stripped by `processActionArguments()`.

```php
// Register a global option:
$app->registerOption('verbose', [$this, 'setVerbose'], 'Enable verbose output');
$app->registerOptionAlias('v', 'verbose');
```

Protected accessors (all @since 4.3.3): `getArguments()`, `setArguments()`, `getOptions()`, `getOptionAliases()`, `getOptionsData()`.

---

## Writer Factory

The writer pipeline is split into overridable factory methods (@since 4.3.3):

```
createStdOutWriter()  →  TStdOutWriter   (inner ITextWriter)
createStdErrStream()  →  TStdErrStream   (TShellWriter::ErrorStream, 4.4.0)
createShellWriter()   →  TShellWriter    (wraps the inner writer, errors to the stream)
```

Override `createStdOutWriter()` in a subclass to inject a test double or file writer. Override `createStdErrStream()` to send error blocks elsewhere, or return null to keep them in the output writer, as `TTestShellApplication` does. Override `createShellWriter()` to substitute a different `TShellWriter` subclass.

`printHelp()` shows an example line for each registered action from `TShellAction::getExample()` (4.4.0).

`getWriterDirect()` / `setWriterDirect()` access the raw field without lazy-init side effects; `setWriterDirect(null)` is used by `flushOutput(false)` to release the writer.

---

## Command Dispatch

`runService()` prints the greeting, then asks each registered action, in registration order, whether it accepts the arguments (`TShellAction::isValidAction()`). The first match has its options stripped by `processActionArguments()`, and `action<Method>($args)` is called; a `-` in the method name is dropped. A falsy return, or no matching action, prints the general help when `QuietMode` is `0`.

Writing an action is described in [TShellAction](./TShellAction.md); output formatting in [TShellWriter](./TShellWriter.md).

---

## Built-in Commands

| Command | Class | Purpose |
|---------|-------|---------|
| `help` | [THelpAction](./Actions/THelpAction.md) | List commands, or the subcommands of one |
| `shell` | [TPhpShellAction](./Actions/TPhpShellAction.md) | Interactive PHP REPL with app context |
| `http` | [TWebServerAction](./Actions/TWebServerAction.md) | `http/serve` starts PHP's built-in dev web server (Debug mode or param) |
| `cache` | [TFlushCachesAction](./Actions/TFlushCachesAction.md) | List/flush one or all `ICache` modules (requires an `ICache` module) |
| `param` | [TDbParameterAction](./Actions/TDbParameterAction.md) | Manage `TDbParameterModule` entries (registered by the module) |
| `cron` | `TShellCronAction` / `TShellDbCronAction` | Run and manage cron tasks (registered by `TCronModule`) |
| `perm` | `TPermissionsAction` | Manage permission rules (registered by `TPermissionsManager`) |
| `socket` | `TSocketServerAction` | Run the socket server (registered by `TSocketServerModule`) |

### activerecord generate / generate-all

The `activerecord` command belongs to the `pradosoft/prado-activerecord` package, whose `TActiveRecordConfig` registers `TActiveRecordAction` through `addShellActionClass()`.

---

## TShellLoginBehavior

[TShellLoginBehavior](./TShellLoginBehavior.md) attaches to [TAuthManager](../Security/TAuthManager.md) and requires a login before any action runs:

```xml
<module id="behaviors" class="Prado\Util\TBehaviorsModule">
    <behavior name="shellLogin" Class="Prado\Shell\TShellLoginBehavior" AttachTo="module:auth" />
</module>
```

It registers the `--user` (`-u`) and `--password` (`-p`) options, prompts for what is missing, and calls `TAuthManager::login()`.

---

## Patterns & Gotchas

- **Return values and exit status** — an `action<Method>()` returns `true` when it handled the command. The process exits with status `0` unless an action throws `TExitException` with another code.
- **`QuietMode` range** — valid levels are `QUIET_NONE` (`0`) to `QUIET_ERRORS` (`3`). An empty string (bare `--quiet` or `-q` flag) maps to `QUIET_INFO`. Values are clamped. Check `$this->getApplication()->getQuietMode() === TShellApplication::QUIET_NONE` before verbose output.
- **Errors go to standard error** — `writeError()` flushes standard output and writes its block to standard error (4.4.0).
- **Conditional actions** — the `cache` command only appears when a cache module is configured; `http` only in Debug mode or with the dev-webserver param. Do not assume they are always present.
- **No-config mode** — when running `prado-cli` without an `application.xml`, a hidden `.runtime` directory is created in the base path (or a temp fallback). No manual setup required.
- **Module availability** — all `application.xml` modules are available, including DB connections, cache, etc. No special setup needed.
- **Output format** — use [TShellWriter](./TShellWriter.md) rather than `echo`/`print`; it formats ANSI color when the terminal supports it. Quiet mode is checked by each action.
- **Writer extensibility** — override `createStdOutWriter()` / `createShellWriter()` in a `TShellApplication` subclass, such as the test harness `TTestShellApplication`, to capture output without touching the real TTY.
