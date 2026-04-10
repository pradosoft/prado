# Util/Helpers/SUMMARY.md

Static utility classes for common low-level operations; all classes contain only static methods.

## Classes

- **`TArrayHelper`** — Array utility methods: deep merging, flattening, and manipulation helpers beyond PHP's built-in array functions.

- **`TBitHelper`** — Bitwise and numeric conversion utilities: color bit shifting, floating-point format conversions (FP16, BF16, FP8), bit mirroring, endian conversion, bit counting.

- **`TEscCharsetConverter`** — Character encoding conversion with escape/unescape sequence handling.

- **`TProcessHelper`** — All-static process utility class; capabilities: `fork()`, `sendSignal()`, `kill()`, `isRunning()`, `getProcessPriority()`, `setProcessPriority()`, `filterCommand()`, OS detection methods.

- **`TProcessWindowsPriority`** — Enum for Windows process priority levels (Idle, BelowNormal, Normal, AboveNormal, High, Realtime).

- **`TProcessWindowsPriorityName`** — Maps enum values to Windows priority name strings.
