# TLogRoute (and built-in routes)

**Location:** `framework/Util/TLogRoute.php`
**Namespace:** `Prado\Util`

## Overview

`TLogRoute` is the abstract base for all log output destinations. A route receives a filtered subset of log entries and sends them somewhere (file, database, email, browser, stdout, etc.). Routes are registered with `TLogRouter`.

Subclass `TLogRoute` and implement `processLogs()` to create a custom route.

## Abstract Method

```php
abstract protected function processLogs(array $logs, bool $final, array $meta): void;
```

`$logs` is an array of filtered log entries. `$final` is true on the last flush (end-of-request). `$meta` contains aggregate info: `['total' => totalTime, 'counts' => [...]]`.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Levels` | int\|string | null (all) | Bitmask or pipe-separated level names to include |
| `Categories` | string | '' (all) | Comma-separated category prefixes; prefix with `!` or `~` to exclude |
| `Enabled` | bool\|callable | true | Toggle route on/off; callable receives `($route, $logs)` |
| `ProcessInterval` | int | 1000 | Batch logs before calling `processLogs()` |
| `PrefixCallback` | callable | null | Custom log prefix formatter |
| `DisplaySubSeconds` | bool | false | Include sub-second precision in timestamps |
| `MaxDelta` | float | 0 | Maximum time delta to include (0 = unlimited) |

## Key Methods

```php
$route->collectLogs(?TLogger $logger, bool $final): void  // called by TLogRouter
$route->getLogCount(): int
$route->getLevels(): ?int
$route->setLevels(int|string $levels): static   // 'Debug|Warning|Error' or bitmask
$route->getCategories(): ?array
$route->setCategories(string $categories): static // 'Prado.Web,!Prado.Web.UI'
$route->getEnabled(): bool
$route->setEnabled(bool|callable $value): static
$route->getLogPrefix(array $log): string         // formats log entry prefix
```

## Level Constants (from TLogger)

```php
TLogger::DEBUG   = 0x01    TLogger::INFO    = 0x02    TLogger::NOTICE  = 0x04
TLogger::WARNING = 0x08    TLogger::ERROR   = 0x10    TLogger::ALERT   = 0x20
TLogger::FATAL   = 0x40
TLogger::PROFILE = 0x40000  TLogger::PROFILE_BEGIN = 0x80000  TLogger::PROFILE_END = 0x100000
```

## Built-in Log Routes

### TFileLogRoute

```xml
<route class="Prado\Util\TFileLogRoute"
       File="protected/runtime/app.log"
       MaxFileSize="1024"
       MaxLogFiles="5"
       Levels="Warning|Error" />
```

| Property | Description |
|----------|-------------|
| `File` | Log file path (default: `runtime/prado.log`) |
| `MaxFileSize` | Max file size in KB before rotation (default: 1024) |
| `MaxLogFiles` | Number of rotated files to keep (default: 5) |

### TDbLogRoute

```xml
<route class="Prado\Util\TDbLogRoute"
       ConnectionID="db"
       LogTableName="prado_log" />
```

| Property | Description |
|----------|-------------|
| `ConnectionID` | ID of `TDbConnection` module |
| `LogTableName` | Database table name (default: `prado_log`) |
| `AutoCreateLogTable` | Auto-create table if missing (default: true) |

### TEmailLogRoute

```xml
<route class="Prado\Util\TEmailLogRoute"
       SentFrom="app@example.com"
       SentTo="admin@example.com"
       Subject="Application Error"
       Levels="Error|Fatal" />
```

| Property | Description |
|----------|-------------|
| `SentFrom` | From email address |
| `SentTo` | Comma-separated recipient addresses |
| `Subject` | Email subject |

### TBrowserLogRoute

Injects an HTML debug panel into the page output.

```xml
<route class="Prado\Util\TBrowserLogRoute" Levels="Debug|Info|Warning|Error" />
```

Includes profiling timers when `PROFILE_BEGIN`/`PROFILE_END` pairs are present.

### TStdOutLogRoute

Writes to stdout/stderr. Used in CLI context.

```xml
<route class="Prado\Util\TStdOutLogRoute" />
```

### TFirePhpLogRoute / TFirebugLogRoute

Send logs to browser extension (FirePHP / Firebug). Requires the corresponding browser extension.

### TSysLogRoute

Sends to the OS `syslog()`. Level mapping: `DEBUG`→`LOG_DEBUG`, `INFO`→`LOG_INFO`, etc.

```xml
<route class="Prado\Util\TSysLogRoute" Levels="Error|Fatal" />
```

## Patterns & Gotchas

- **`processLogs()` only called on flush** — logs accumulate in `$_logs` until `ProcessInterval` is reached or `$final=true`. Do not assume immediate delivery.
- **Category exclusion** — prefix with `!` or `~` to exclude: `'Prado.Web,!Prado.Web.UI'` includes all `Prado.Web.*` but not `Prado.Web.UI.*`.
- **`Enabled` as callable** — useful for conditional routing: `function($route, $logs) { return php_sapi_name() !== 'cli'; }`.
- **Custom prefix** — `setPrefixCallback(callable $fn)` where `$fn(array $log): string`; receives the raw log entry array.
- **`IOutputLogRoute`** — routes that output directly to the HTTP response (e.g., `TBrowserLogRoute`) implement this interface so `TLogRouter` can call them at the right time.
