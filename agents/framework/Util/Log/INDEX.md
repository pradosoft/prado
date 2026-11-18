# Util/Log/INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Log`**

## Purpose

PSR-3 adapters between the Prado application log and PSR-3 loggers. The Prado logging classes themselves ([`TLogger`](../TLogger.md), [`TLogRouter`](../TLogRouter.md), [`TLogRoute`](../TLogRoute.md) and its routes) live in `Prado\Util`; the adapters import them from there.

On Prado 4.3 both adapters require the `psr/log` package, version 2 or 3, which composer suggests. They keep the `Prado\Util\Log` namespace that Prado 4.4 uses for all logging classes, so code written against them carries over unchanged.

## Classes

- **[`TPsrLogger`](TPsrLogger.md)** — Adapts [`TLogger`](../TLogger.md) to `Psr\Log\LoggerInterface`. A library that accepts a PSR-3 logger writes into the application log. `TPsrLogger::singleton()` returns the shared adapter of the application logger. Maps PSR-3 levels to `TLogger` levels, interpolates `{key}` placeholders, and decodes the `CONTEXT_*` keys into the log entry. **Since 4.3.3.**

- **[`TPsrLogRoute`](TPsrLogRoute.md)** — [`TLogRoute`](../TLogRoute.md) that forwards the application log to an external PSR-3 logger such as Monolog. Property `Logger` takes an instance, a module ID, or a class name; `FormatMessage` sends the formatted log line. Rejects a `TPsrLogger` as its `Logger`. **Since 4.3.3.**

## Patterns & Gotchas

- `TPsrLogRoute` emits the context keys that `TPsrLogger` decodes, so an entry round-trips through both adapters into an equivalent `TLogger` entry.
- Timestamps come from `microtime(true)`; Prado 4.3 has no clock service.
