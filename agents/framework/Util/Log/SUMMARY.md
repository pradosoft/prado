# Util/Log/SUMMARY.md

Application logging: in-memory `TLogger`, the `TLogRouter` module, `TLogRoute` outputs, and PSR-3 adapters. Namespace `Prado\Util\Log` since 4.4.0.

## Classes

- **`TLogger`** — Core logger; `log($message, $level, $category, $ctl)`; levels `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`, plus `PROFILE_BEGIN` / `PROFILE_END`; fires `onFlushLogs`.

- **`TLogRouter`** — Module that routes log entries to multiple `TLogRoute` targets; `addRoute()`, `removeRoute()`.

- **`TLogRoute`** — Abstract base for log outputs; subclass and implement `processLogs()`; filters by `Levels` and `Categories`.

- **`IOutputLogRoute`** — Marker interface for routes that write into the response output.

- **`TFileLogRoute`**, **`TDbLogRoute`**, **`TEmailLogRoute`**, **`TBrowserLogRoute`**, **`TFirebugLogRoute`**, **`TFirePhpLogRoute`**, **`TStdOutLogRoute`**, **`TSysLogRoute`** — Built-in routes to files, a database table, email, the page, Firebug, FirePHP, STDOUT, and syslog.

- **`TPsrLogger`** — PSR-3 `LoggerInterface` writing into `TLogger`; `ISingleton` via `singleton()`; `{key}` interpolation; context keys `category`, `control`, `exception`. Since 4.4.0.

- **`TPsrLogRoute`** — Route forwarding the application log to an external PSR-3 logger; `Logger` accepts an instance, a module ID, or a class name. Since 4.4.0.
