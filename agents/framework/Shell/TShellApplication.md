# Shell / CLI System

### Directories
[./](../INDEX.md) > [Shell](./INDEX.md) > [TShellApplication](./TShellApplication.md)

**Location:** `framework/Shell/`
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
| `QuietMode` | `0` = normal, `1` = quiet (less output), `2` = silent (no output) |
| `OutputWriter` | [TShellWriter](./TShellWriter.md) instance for console output |

---

## TShellAction (Abstract Base)

Extend to create custom CLI commands:

```php
class MyDeployAction extends [TShellAction](./TShellAction.md)
{
    protected $action = 'deploy';
    protected $parameters = ['environment'];     // required
    protected $optional = ['version', 'force'];  // optional

    public function run(array $args, array $opts): int
    {
        $env = $args[0];
        $this->getWriter()->writeLine("Deploying to {$env}...", [TShellWriter](./TShellWriter.md)::INFO);
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
$this->getWriter();                    // [TShellWriter](./TShellWriter.md) instance
$this->getApplication();               // [TShellApplication](./TShellApplication.md)
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
$writer->writeLine('Success!', [TShellWriter](./TShellWriter.md)::SUCCESS);
$writer->writeLine('Warning!', [TShellWriter](./TShellWriter.md)::WARNING);
$writer->writeLine('Error!',   [TShellWriter](./TShellWriter.md)::ERROR);
$writer->writeLine('Info',     [TShellWriter](./TShellWriter.md)::INFO);
$writer->writeLine('Debug',    [TShellWriter](./TShellWriter.md)::DEBUG);

// Tables:
$writer->table(['ID', 'Name', 'Status'], [
    [1, 'Alice', 'active'],
    [2, 'Bob',   'inactive'],
]);

// Section header:
$writer->writeHeader('Deployment Report');

// Block comment:
$writer->writeBlock('Note: this may take a while.', [TShellWriter](./TShellWriter.md)::WARNING);
```

Format constants: `NORMAL`, `INFO`, `SUCCESS`, `WARNING`, `ERROR`, `DEBUG`.

---

## Built-in Commands

| Command | Class | Purpose |
|---------|-------|---------|
| `help` | [THelpAction](./Actions/THelpAction.md) | List commands and usage |
| `activerecord` | [TActiveRecordAction](./Actions/TActiveRecordAction.md) | Generate AR class skeletons from DB tables |
| `param` | [TDbParameterAction](./Actions/TDbParameterAction.md) | Manage `TDbParameterModule` entries |
| `shell` | [TPhpShellAction](./Actions/TPhpShellAction.md) | Interactive PHP REPL with app context |
| `serve` | [TWebServerAction](./Actions/TWebServerAction.md) | Start PHP's built-in dev web server |
| `cache` | [TFlushCachesAction](./Actions/TFlushCachesAction.md) | List/flush one or all `ICache` modules |
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
<module id="shell" class="Prado\Shell\TShellApplication" />
<module id="behaviors" class="Prado\Util\TBehaviorsModule">
    <behavior class="Prado\Shell\TShellLoginBehavior" UserManager="users" AttachTo="module:shell" />
</module>
```

[TShellLoginBehavior](./TShellLoginBehavior.md)

Prompts for username/password; calls `IUserManager::validateUser()`.

---

## Patterns & Gotchas

- **Exit codes** — return `0` from `run()` for success, non-zero for error. The shell respects the return value.
- **`QuietMode`** — check `$this->getApplication()->getQuietMode()` before verbose output; respect `2` (silent) mode.
- **Module availability** — all `application.xml` modules are available, including DB connections, cache, etc. No special setup needed.
- **Output format** — always use [TShellWriter](./TShellWriter.md) rather than `echo`/`print`; it supports color, structured formatting, and quiet-mode suppression.
