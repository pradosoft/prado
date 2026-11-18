# Util/Log/SUMMARY.md

PSR-3 adapters between the Prado application log and PSR-3 loggers; they require the `psr/log` package on Prado 4.3.

## Classes

- **`TPsrLogger`** — Adapts `TLogger` to `Psr\Log\LoggerInterface`; maps levels, interpolates placeholders, and decodes context keys into the log entry.

- **`TPsrLogRoute`** — Log route that forwards the application log to an external PSR-3 logger; property `Logger` takes an instance, module ID, or class name.
