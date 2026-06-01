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
php vendor/bin/prado-cli /path/to/protected [command] [args...]
```

### Key Properties

| Property | Description |
|----------|-------------|
| `QuietMode` | `0` = normal output (default); `1`–`3` = progressively quieter; empty string maps to `1` |
| `Writer` | [TShellWriter](./TShellWriter.md) instance for console output |

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `SHELL_RUNTIME_PATH` | `'.runtime'` | Hidden runtime directory name used in no-config mode (@since 4.3.3) |

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
  2. `sys_get_temp_dir()/prado-<md5(basePath)>/.runtime` — stable temp path keyed to the base path, so concurrent invocations of the same project share a runtime without colliding with other Prado installations.
  3. Throws `TConfigurationException` if neither location is writable.

---

## Conditional Action Registration

`installShellActions()` gates built-in commands on application state (@since 4.3.0):

| Method | Gate condition | Registered action |
|--------|---------------|-------------------|
| `hasCacheModules()` | `ICache` module(s) present | [TFlushCachesAction](./Actions/TFlushCachesAction.md) |
| always | — | [THelpAction](./Actions/THelpAction.md), [TPhpShellAction](./Actions/TPhpShellAction.md) |
| `hasActiveRecordConfig()` | `TActiveRecordConfig` module present | [TActiveRecordAction](./Actions/TActiveRecordAction.md) |
| `hasDevWebServer()` | `Debug` mode or `TWebServerAction::DEV_WEBSERVER_PARAM` param truthy | [TWebServerAction](./Actions/TWebServerAction.md) |

All three gating methods (`hasCacheModules()`, `hasActiveRecordConfig()`, `hasDevWebServer()`) are @since 4.3.3 and public — subclasses or tests can override them.

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
createShellWriter()   →  TShellWriter    (wraps the inner writer)
```

Override `createStdOutWriter()` in a subclass to inject a test double or file writer. Override `createShellWriter()` to substitute a different `TShellWriter` subclass.

`getWriterDirect()` / `setWriterDirect()` access the raw field without lazy-init side effects; `setWriterDirect(null)` is used by `flushOutput(false)` to release the writer.

---

## TShellAction (Abstract Base)

Extend to create custom CLI commands:

```php
class MyDeployAction extends TShellAction
{
    protected $action = 'deploy';
    protected $parameters = ['environment'];     // required
    protected $optional = ['version', 'force'];  // optional

    public function run(array $args, array $opts): int
    {
        $env = $args[0];
        $this->getWriter()->writeLine("Deploying to {$env}...", TShellWriter::INFO);
        // ... do work ...
        return 0;  // 0 = success, non-zero = error
    }
}
```

### Properties to Override

| Property | Purpose |
|----------|---------|
| `$action` | Command name (e.g., `'deploy'`) |
| `$parameters` | Required positional arguments |
| `$optional` | Optional positional arguments |
| `$methods` | Subcommand names |

### Methods

```php
$this->getWriter();                    // TShellWriter instance
$this->getApplication();               // TShellApplication instance
$this->outError($message);             // Write to stderr
```

---

## TShellWriter

Formatted console output. Access via `$this->getWriter()` inside actions.

[TShellWriter](./TShellWriter.md)

```php
$writer = $this->getWriter();

// Text output:
$writer->write('text');                            // no newline
$writer->writeLine('text');                        // with newline
$writer->writeLine('Success!', TShellWriter::SUCCESS);
$writer->writeLine('Warning!', TShellWriter::WARNING);
$writer->writeLine('Error!',   TShellWriter::ERROR);
$writer->writeLine('Info',     TShellWriter::INFO);
$writer->writeLine('Debug',    TShellWriter::DEBUG);

// Tables:
$writer->table(['ID', 'Name', 'Status'], [
    [1, 'Alice', 'active'],
    [2, 'Bob',   'inactive'],
]);

// Section header:
$writer->writeHeader('Deployment Report');

// Block comment:
$writer->writeBlock('Note: this may take a while.', TShellWriter::WARNING);
```

Format constants: `NORMAL`, `INFO`, `SUCCESS`, `WARNING`, `ERROR`, `DEBUG`.

---

## Built-in Commands

| Command | Class | Purpose |
|---------|-------|---------|
| `help` | [THelpAction](./Actions/THelpAction.md) | List commands and usage |
| `activerecord` | [TActiveRecordAction](./Actions/TActiveRecordAction.md) | Generate AR class skeletons from DB tables (requires `TActiveRecordConfig` module) |
| `param` | [TDbParameterAction](./Actions/TDbParameterAction.md) | Manage `TDbParameterModule` entries |
| `shell` | [TPhpShellAction](./Actions/TPhpShellAction.md) | Interactive PHP REPL with app context |
| `serve` | [TWebServerAction](./Actions/TWebServerAction.md) | Start PHP's built-in dev web server (Debug mode or param) |
| `cache` | [TFlushCachesAction](./Actions/TFlushCachesAction.md) | List/flush one or all `ICache` modules (requires `ICache` module) |
| `cron` | `TShellCronAction` | Run pending cron tasks |
| `db-cron` | `TShellDbCronAction` | Manage database-backed cron tasks |

### activerecord generate / generate-all

```bash
# Generate one AR class:
php prado-cli.php /app activerecord generate users --connectionID=db

# Generate all tables:
php prado-cli.php /app activerecord generate-all --connectionID=db --directory=app/Records
```

Supported drivers: MySQL, PostgreSQL, SQLite, MSSQL/sqlsrv/dblib, Oracle, IBM DB2, Firebird.

---

## TShellLoginBehavior

Attach to [TShellApplication](./TShellApplication.md) to require credentials before any action runs:

[TShellLoginBehavior](./TShellLoginBehavior.md)

```xml
<modules>
    <module id="shell" class="Prado\Shell\TShellApplication" />
    <module id="behaviors" class="Prado\Util\TBehaviorsModule">
        <behavior class="Prado\Shell\TShellLoginBehavior" UserManager="users" AttachTo="module:shell" />
    </module>
</modules>
```

Prompts for username/password; calls `IUserManager::validateUser()`.

---

## Patterns & Gotchas

- **Exit codes** — return `0` from `run()` for success, non-zero for error. The shell respects the return value.
- **`QuietMode` range** — valid levels are `0`–`3`. An empty string (bare `--quiet` or `-q` flag) maps to `1`. Values are clamped. Check `$this->getApplication()->getQuietMode() === 0` before verbose output.
- **Conditional actions** — `activerecord` and `cache` commands only appear when the corresponding module types are configured; `serve` only in Debug mode or with the dev-webserver param. Do not assume they are always present.
- **No-config mode** — when running `prado-cli` without an `application.xml`, a hidden `.runtime` directory is created in the base path (or a temp fallback). No manual setup required.
- **Module availability** — all `application.xml` modules are available, including DB connections, cache, etc. No special setup needed.
- **Output format** — always use [TShellWriter](./TShellWriter.md) rather than `echo`/`print`; it supports color, structured formatting, and quiet-mode suppression.
- **Writer extensibility** — override `createStdOutWriter()` / `createShellWriter()` in `TTestApplication` subclasses to capture output in tests without touching the real TTY.
