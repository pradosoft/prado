# Util/TSignalParameter

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TSignalParameter`**

## Class Info
**Location:** `framework/Util/TSignalParameter.php`
**Namespace:** `Prado\Util`
**Extends:** [`TEventParameter`](../TEventParameter.md)
**Since:** 4.3.0

## Overview
`TSignalParameter` carries the data associated with a POSIX signal event. It is passed to signal handlers registered through Prado's process-signal infrastructure and exposes the signal number, exit state, exit code, alarm time, and the additional `siginfo` fields (error number, code, status, PID, UID) that the OS populates for certain signals. All properties are fluent-settable via `static` return types.

## Constructor

`__construct(int $signal = 0, bool $isExiting = false, int $exitCode = 0, mixed $parameter = null)`

The `$parameter` argument carries the raw `siginfo` array when available; its sub-fields are exposed through the `getParameter*()` accessors.

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `Signal` | `int` | The signal number being raised. |
| `IsExiting` | `bool` | Whether the signal handler should exit the process. |
| `ExitCode` | `int` | The exit code to use when exiting. |
| `AlarmTime` | `?int` | The alarm time associated with a `SIGALRM` signal, or `null`. |
| `ParameterErrorNumber` | `?int` | The `si_errno` field from siginfo, or `null` (read-only). |
| `ParameterCode` | `?int` | The `si_code` field from siginfo, or `null` (read-only). |
| `ParameterStatus` | `?int` | The `si_status` field from siginfo, or `null` (read-only). |
| `ParameterPID` | `?int` | The `si_pid` field from siginfo, or `null` (read-only). |
| `ParameterUID` | `?int` | The `si_uid` field from siginfo, or `null` (read-only). |

## See Also

- [`TEventParameter`](../TEventParameter.md) — parent class
- [`TProcessHelper`](./Helpers/TProcessHelper.md) — registers signal handlers that use this parameter
