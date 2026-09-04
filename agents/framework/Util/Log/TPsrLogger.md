# Util/Log/TPsrLogger

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TPsrLogger`**

## Class Info
**Location:** `framework/Util/Log/TPsrLogger.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TComponent`](../../TComponent.md)
**Implements:** `Psr\Log\LoggerInterface` (uses `Psr\Log\LoggerTrait`)
**Since:** 4.4.0

## Overview
`TPsrLogger` adapts [`TLogger`](./TLogger.md) to the PSR-3 `LoggerInterface`. A library that accepts a PSR-3 logger writes into the application log through this class, and [`TLogRouter`](./TLogRouter.md) routes the entries with the rest of the application log. `TLogger` cannot implement PSR-3 directly because its `log()` parameter order and its integer bit-flag levels conflict with the PSR-3 signature.

```php
$logger = new TPsrLogger();
$logger->warning('Disk {disk} is at {percent}% capacity', ['disk' => '/dev/sda1', 'percent' => 92]);
$library->setLogger($logger);
```

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `Logger` | `?TLogger` | `null` | Receiving logger; `null` uses `Prado::getLogger()`. |
| `Category` | `?string` | `null` | Default log category; `null` uses the class calling the logger. Overridden per call by the `category` context key. |

Both are also constructor arguments: `new TPsrLogger(?TLogger $logger = null, ?string $category = null)`.

## Level Mapping

| PSR-3 → TLogger | TLogger → PSR-3 |
|---|---|
| `emergency`, `critical` → `FATAL` | `FATAL` → `critical` |
| `alert` → `ALERT` | `ALERT` → `alert` |
| `error` → `ERROR` | `ERROR` → `error` |
| `warning` → `WARNING` | `WARNING` → `warning` |
| `notice` → `NOTICE` | `NOTICE` → `notice` |
| `info` → `INFO` | `INFO` → `info` |
| `debug` → `DEBUG` | `DEBUG`, `PROFILE*`, unknown → `debug` |

- `toPradoLevel(mixed $level): int` — accepts a PSR-3 name (case-insensitive) or a `TLogger` integer level including `PROFILE_BEGIN` / `PROFILE_END`; throws `Psr\Log\InvalidArgumentException` otherwise, as PSR-3 requires. Prado level names such as `fatal` are not accepted.
- `toPsrLevel(int $level): string` — the reverse mapping; used by [`TPsrLogRoute`](./TPsrLogRoute.md).

## Context Keys

Each key has a `CONTEXT_*` constant. [`TPsrLogRoute`](./TPsrLogRoute.md) emits the same keys, so a route context round-trips into an equivalent `TLogger` entry.

| Key | Constant | Decoded as |
|---|---|---|
| `category` | `CONTEXT_CATEGORY` | Log category; default `Category` property, then the calling class. |
| `level` | `CONTEXT_LEVEL` | `TLogger` level; overrides the PSR-3 `$level` argument. Validated by `toPradoLevel()`. |
| `time` | `CONTEXT_TIME` | Entry timestamp (`microtime(true)` float). |
| `memory` | `CONTEXT_MEMORY` | Memory usage in bytes. |
| `pid` | `CONTEXT_PID` | Process ID. |
| `control` | `CONTEXT_CONTROL` | `TControl` (its client ID is stored) or client ID string; other types are ignored. |
| `traces` | `CONTEXT_TRACES` | Call traces array. |
| `exception` | `CONTEXT_EXCEPTION` | A `Throwable`. When the message equals its `getMessage()` the exception itself is the log token; otherwise its string form is appended to the message on a new line. |
| `prefix` | `CONTEXT_PREFIX` | Route-computed; not stored. |
| `delta`, `total` | `CONTEXT_DELTA`, `CONTEXT_TOTAL` | Route-computed timing; not stored. |

When `time`, `memory`, `pid`, or `traces` is present the entry is built from the context and merged through `TLogger::mergeLogs()`; missing or mistyped values fall back to the current time, memory, process, or `null` traces. Otherwise `TLogger::log()` records the entry and captures traces per `TraceLevel`.

Every context value, reserved keys included, is available to `{key}` interpolation.

## Interpolation

`interpolate(string $message, array $context): string` replaces `{key}` placeholders:

| Value | Replacement |
|---|---|
| `null`, scalar, `Stringable` | `(string)` cast (`null` and `false` become empty) |
| `DateTimeInterface` | ATOM format |
| array | JSON |
| other object | `[object ClassName]` |
| missing key | placeholder left as is |

## Behavior Notes

- `log($level, $message, $context)` is the single entry point; the eight level methods come from `LoggerTrait`.
- The default category is resolved by walking the backtrace to the first class outside `TPsrLogger`, so entries from a library method are categorized by that library class.
- Do not set a `TPsrLogger` as the `Logger` of [`TPsrLogRoute`](./TPsrLogRoute.md); the route rejects it to prevent a loop through the same `TLogger`.

## See Also

- [`TLogger`](./TLogger.md) — the receiving logger
- [`TPsrLogRoute`](./TPsrLogRoute.md) — reverse adapter: application log → external PSR-3 logger
