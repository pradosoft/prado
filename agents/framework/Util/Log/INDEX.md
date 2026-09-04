# Util/Log/INDEX.md

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Log`**

## Purpose

Application logging for the Prado framework: the in-memory logger, the router module that dispatches log entries to routes, the built-in routes, and the PSR-3 adapters. Namespace `Prado\Util\Log` since 4.4.0 (Issue #1178).

## Flow

`Prado::log()` / `Prado::debug()` … `Prado::fatal()` → [`TLogger`](TLogger.md) buffers entries → `onFlushLogs` (auto-flush, end of request, shutdown) → [`TLogRouter`](TLogRouter.md) `collectLogs()` → each [`TLogRoute`](TLogRoute.md) filters by `Levels` / `Categories`, computes timing, and calls `processLogs()`.

## Classes

- **[`TLogger`](TLogger.md)** — Core logger. Access via `Prado::getLogger()`. Methods: `log($message, $level, $category, $ctl)`, `getLogs()`, `mergeLogs()`, `deleteLogs()`. Levels: `TLogger::DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`. Profiling: `PROFILE_BEGIN` / `PROFILE_END` pairs matched by token. Flushes when the log count reaches `FlushCount` and fires `onFlushLogs`. `TraceLevel` records call traces per entry.

- **[`TLogRouter`](TLogRouter.md)** — Module that routes log entries to multiple [`TLogRoute`](TLogRoute.md) targets. Configured in `application.xml` with `<route>` children or a `ConfigFile`. Methods: `addRoute()`, `removeRoute()`, `getRoutes()`.

- **[`TLogRoute`](TLogRoute.md)** — Abstract base for log outputs. Subclass and implement `processLogs(array $logs, bool $final, array $meta)`. Properties: `Levels` (names or bit mask), `Categories`, `Enabled`, `ProcessInterval`, `PrefixCallback`, `DisplaySubSeconds`.

- **[`IOutputLogRoute`](IOutputLogRoute.md)** — Marker interface for routes that write into the response output. Forces a final flush on every collection.

### Built-in routes

| Route | Destination | Key properties |
|---|---|---|
| [`TFileLogRoute`](TFileLogRoute.md) | Rotating log files | `LogPath`, `LogFile`, `MaxFileSize`, `MaxLogFiles` |
| [`TDbLogRoute`](TDbLogRoute.md) | Database table | `ConnectionID`, `LogTableName`, `AutoCreateLogTable`, `RetainPeriod` |
| [`TEmailLogRoute`](TEmailLogRoute.md) | Email | `Emails`, `Subject`, `SentFrom` |
| [`TBrowserLogRoute`](TBrowserLogRoute.md) | Inline console in the page (`IOutputLogRoute`) | `CssClass`, `ColorizeDelta`, `AddPrefix` |
| [`TFirebugLogRoute`](TFirebugLogRoute.md) | Firebug console via `<script>` or JSON callback | |
| [`TFirePhpLogRoute`](TFirePhpLogRoute.md) | FirePHP HTTP headers (`IOutputLogRoute`) | `GroupLabel` |
| [`TStdOutLogRoute`](TStdOutLogRoute.md) | STDOUT with colorized level badges | `OnlyDevServer` |
| [`TSysLogRoute`](TSysLogRoute.md) | OS syslog | `SysLogPrefix`, `SysLogFlags`, `Facility` |
| [`TPsrLogRoute`](TPsrLogRoute.md) | Any PSR-3 `LoggerInterface` (Monolog, …) | `Logger` (instance, module ID, or class), `FormatMessage` |

### PSR-3 adapters (since 4.4.0)

- **[`TPsrLogger`](TPsrLogger.md)** — `Psr\Log\LoggerInterface` implementation writing into [`TLogger`](TLogger.md). Hand it to libraries that accept a PSR-3 logger. Maps PSR-3 level names to `TLogger` levels, interpolates `{key}` placeholders, and decodes the `CONTEXT_*` keys (`category`, `level`, `time`, `memory`, `pid`, `control`, `traces`, `exception`) into the entry.

- **[`TPsrLogRoute`](TPsrLogRoute.md)** — Route forwarding the application log to an external PSR-3 logger. Rejects a `TPsrLogger` as its target because the entries would loop back into the same `TLogger`.

Level mapping in both directions is in `TPsrLogger::toPradoLevel()` and `TPsrLogger::toPsrLevel()`: `emergency` and `critical` → `FATAL`; `FATAL` → `critical`; profile levels → `debug`.

## Related

- [`TCaptureForkLog`](../Behaviors/TCaptureForkLog.md) — behavior that merges a forked child's logs into the parent `TLogger`.
- `TEventLoggingBehavior` ([Behaviors/](../Behaviors/INDEX.md)) — behavior that logs raised events.
- [`TShellCronLogBehavior`](../Cron/TShellCronLogBehavior.md), [`TDbCronCleanLogTask`](../Cron/TDbCronCleanLogTask.md) — cron execution logging.
- `Prado\Exceptions\TLogException` — raised by routes on delivery failure.
