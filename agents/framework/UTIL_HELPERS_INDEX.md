# Util/Helpers/INDEX.md - UTIL_HELPERS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Static utility classes for common low-level operations.

## Classes

- **`TArrayHelper`** — Array utility methods: deep merging, flattening, and manipulation helpers beyond PHP's built-in array functions.

- **`TBitHelper`** — Bitwise and numeric conversion utilities:
  - Color bit shifting
  - Floating-point format conversions: FP16 (half-precision), BF16 (bfloat16), FP8
  - Bit mirroring and endian conversion
  - Bit counting
  - Negative-zero detection
  - All methods are static.

- **`TEscCharsetConverter`** — Character encoding conversion with escape/unescape sequence handling. Multi-byte character support.

- **`TProcessHelper`** — All-static process utility class. Key capabilities:
  - **Forking**: `fork(bool $captureForkLog = false): int` — wraps `pcntl_fork()`, fires global `fxPrepareForFork` / `fxRestoreAfterFork` events before/after forking.
  - **Signals**: `sendSignal(int $signal, ?int $pid)`, `kill(int $pid)`, `isRunning(int $pid)`.
  - **Priority**: `getProcessPriority(?int $pid)`, `setProcessPriority(int $priority, ?int $pid)`.
  - **Commands**: `filterCommand(string $cmd)` replaces `@php` placeholder with `PHP_BINARY`; `escapeShellArg()`, `exitStatus()`.
  - **OS detection**: `isSystemWindows()`, `isSystemMacOS()`, `isSystemLinux()`, `isForkable()`.
  - Constants: `PHP_COMMAND = '@php'`, `FX_PREPARE_FOR_FORK`, `FX_RESTORE_AFTER_FORK`, `WINDOWS_*_PRIORITY`.

- **`TProcessWindowsPriority`** — Enum for Windows process priority levels (Idle, BelowNormal, Normal, AboveNormal, High, Realtime).

- **`TProcessWindowsPriorityName`** — Maps enum values to Windows priority name strings.

## Conventions

- All classes in this directory contain only **static methods** — do not instantiate them.
- `TBitHelper` is designed for low-level numeric manipulation (e.g., image processing, ML tensor helpers).
- `TProcessHelper::fork()` fires `fxPrepareForFork` / `fxRestoreAfterFork` global events — implement these in any module that holds resources (DB connections, file handles) that must be re-initialized per-process after a fork.
- Always call `TProcessHelper::isForkable()` before forking; returns `false` on Windows.
