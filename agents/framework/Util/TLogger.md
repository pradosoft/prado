# Util/TLogger

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TLogger`**

## Class Info
**Location:** `framework/Util/TLogger.php`
**Namespace:** `Prado\Util`

## Overview
Two-part logging system: `TLogger` accumulates log entries in memory; `[TLogRouter](TLogRouter.md)` module routes them to one or more output targets at flush time (end of request, or on `flush()`).

## Logging Levels

```php
TLogger::DEBUG    = 0x01
TLogger::INFO     = 0x02
TLogger::NOTICE   = 0x04
TLogger::WARNING  = 0x08
TLogger::ERROR    = 0x10
TLogger::ALERT    = 0x20
TLogger::FATAL    = 0x40
TLogger::PROFILE  = 0x40000  // profiling
TLogger::PROFILE_BEGIN = 0x80000
TLogger::PROFILE_END   = 0x100000
```

## Logging API

```php
// Via static helper (preferred):
[Prado](../Prado.md)::log('message', TLogger::WARNING, 'MyApp.Module');

// Via logger directly:
$logger = [Prado](../Prado.md)::getLogger();
$logger->log('message', TLogger::ERROR, 'category.subcategory');

// Profiling:
$logger->log('operation start', TLogger::PROFILE_BEGIN, 'perf.db');
// ... do work ...
$logger->log('operation end', TLogger::PROFILE_END, 'perf.db');
```

Category convention: dot-separated, e.g., `'Prado.Web.THttpRequest'`.

## TLogger Methods

```php
$logger->getLogs($levels = null, $categories = null);  // filter logs
$logger->deleteLogs($levels = null, $categories = null); // remove matching
$logger->flush($final = false);                         // send to routes + clear
$logger->getLogCount();                                 // total entries stored
```

`$levels` and `$categories` are bitmask / array filters respectively.

## TLogRouter — Configuration

Register as a module; each `<route>` child defines a log destination:

```xml
<module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TFileLogRoute"
           Levels="Warning|Error" File="protected/runtime/app.log" />
    <route class="Prado\Util\TBrowserLogRoute"
           Levels="Debug|Info|Warning|Error" />
</module>
```

## Built-in Log Routes

| Route | Output |
|-------|--------|
| `TFileLogRoute` | File; properties: `File`, `MaxFileSize`, `MaxLogFiles` (rotation) |
| `TDbLogRoute` | PDO database table; `ConnectionID`, `LogTableName` |
| `TEmailLogRoute` | Email on error; `SentFrom`, `SentTo`, `Subject` |
| `TBrowserLogRoute` | Inline HTML debug panel on page |
| `TStdOutLogRoute` | stdout/stderr |
| `TFirePhpLogRoute` / `TFirebugLogRoute` | Browser extension integration |
| `TSysLogRoute` | OS syslog via `syslog()` |

## Log Route Filtering

Each route accepts `Levels` (pipe-separated or bitmask) and `Categories` (comma-separated prefix list). Only matching entries are processed.

```xml
<route class="Prado\Util\TFileLogRoute"
       Levels="Error|Fatal"
       Categories="Prado.Security,MyApp.Auth" />
```

## Patterns & Gotchas

- **`[Prado](../Prado.md)::log()` is the preferred call** — it avoids a `getLogger()` call when no logger is registered.
- **Categories enable filtering** — use consistent dot-separated namespaces matching your module hierarchy.
- **Flush on route** — routes process logs in `flush()`, called automatically by the application at end-of-request (`onEndRequest`). Manual flush via `$logger->flush()` if needed mid-request.
- **Profiling** — `PROFILE_BEGIN`/`PROFILE_END` pairs are matched by category to compute elapsed time. Displayed in `TBrowserLogRoute` with timing.
- **Auto-flush threshold** — `TLogger` auto-flushes when log count exceeds `AutoFlush` (default 10000) entries to prevent memory overflow.
