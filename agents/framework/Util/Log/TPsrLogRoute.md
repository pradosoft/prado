# Util/Log/TPsrLogRoute

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TPsrLogRoute`**

## Class Info
**Location:** `framework/Util/Log/TPsrLogRoute.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Since:** 4.4.0

## Overview
`TPsrLogRoute` forwards the application log to an external PSR-3 `LoggerInterface` such as Monolog. Each entry is sent with `LoggerInterface::log()` at the PSR-3 level from [`TPsrLogger::toPsrLevel()`](./TPsrLogger.md). Filtering, timing, and batching come from [`TLogRoute`](./TLogRoute.md).

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Logger` | `LoggerInterface\|string\|null` | `null` | The PSR-3 logger: an instance, the ID of an application module implementing `LoggerInterface`, or a class name with a no-argument constructor. A module ID or class name is resolved on first use, module ID first. Required. |
| `FormatMessage` | `bool` | `false` | Send [`TLogRoute::formatLogMessage()`](./TLogRoute.md) output (time, prefix, level, category) as the PSR-3 message instead of the raw message. |

## PSR-3 Message and Context

The message is the raw log message. An exception message is its `getMessage()`; a non-string token is dumped with `TVarDumper`. The context carries the remaining fields under the [`TPsrLogger::CONTEXT_*`](./TPsrLogger.md) keys, so [`TPsrLogger`](./TPsrLogger.md) can decode the context back into an equivalent entry:

| Key | Value |
|---|---|
| `category` | log category |
| `level` | `TLogger` level integer |
| `time` | `microtime(true)` timestamp |
| `memory` | memory usage in bytes |
| `pid` | process ID |
| `prefix` | [`TLogRoute::getLogPrefix()`](./TLogRoute.md) (IP, user, session) |
| `control` | control client ID, when set |
| `traces` | call traces, when `TLogger::TraceLevel` is set |
| `delta`, `total` | timing computed by `TLogRoute::filterLogs()` |
| `exception` | the `Throwable` when the log message is an exception |

## Exceptions

| Code | Condition |
|---|---|
| `psrlogroute_logger_required` | `getLogger()` with no `Logger` set |
| `psrlogroute_logger_invalid` | `Logger` string resolves to neither a module nor a class implementing `LoggerInterface` |
| `psrlogroute_logger_recursive` | `Logger` is a [`TPsrLogger`](./TPsrLogger.md); it would write the entries back into the same `TLogger` |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module. The `Logger` here is a module registered in the same configuration.

**application.xml:**
```xml
<modules>
  <module id="psrlog" class="App\Logging\MonologModule" />
  <module id="log" class="Prado\Util\Log\TLogRouter">
    <route class="Prado\Util\Log\TPsrLogRoute" Logger="psrlog" Levels="Warning, Error, Fatal" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'psrlog' => ['class' => 'App\Logging\MonologModule'],
        'log' => [
            'class' => 'Prado\Util\Log\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\Log\TPsrLogRoute', 'properties' => ['Logger' => 'psrlog', 'Levels' => 'Warning, Error, Fatal']],
            ],
        ],
    ],
];
```

**Programmatic:**
```php
$route = new TPsrLogRoute();
$route->setLogger(new \Monolog\Logger('prado'));
$app->getModule('log')->addRoute($route);
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TPsrLogger`](./TPsrLogger.md) — reverse adapter: PSR-3 calls → `TLogger`
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
