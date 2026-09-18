# Util/Clock/TMonotonicClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TMonotonicClock`**

## Class Info
**Location:** `framework/Util/Clock/TMonotonicClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** `Prado\TComponent`
**Since:** 4.4.0

## Overview
`TMonotonicClock` is a thin reader over the system monotonic timer (`\hrtime()`). It measures **elapsed time**, not wall-clock time: values count from an arbitrary boot-relative origin, always move forward, and are immune to wall-clock changes (NTP steps, DST, manual clock changes). Use it for durations and timeouts; use the [`IClock`](IClock.md) clocks for "what time is it".

It is intentionally **separate** from the wall-clock [`IClock`](IClock.md) hierarchy — a monotonic counter has no timezone, no epoch meaning, and only differences between readings are meaningful (Prado's analogue of a Symfony stopwatch source).

```php
$mono = new TMonotonicClock();
$start = $mono->nanoseconds();
// ... work ...
$seconds = $mono->elapsed($start);   // float seconds since $start
```

## Methods

| Method | Description |
|--------|-------------|
| `nanoseconds(): int` | The current monotonic time in nanoseconds (arbitrary origin). |
| `seconds(): float` | The current monotonic time in seconds. |
| `elapsed(int $sinceNanoseconds): float` | Seconds elapsed since an earlier `nanoseconds()` reading. |
| `hrtime(): int` | Protected. Reads `\hrtime(true)`; the single overridable seam — override to control elapsed time in tests. |

## Notes
- Registered in `framework/classes.php` as `TMonotonicClock`.
- Not an `IClock`: it has no `now()`/`timezone()`. Don't mix monotonic nanoseconds with epoch timestamps.
- 64-bit builds return `int` nanoseconds; on 32-bit PHP `\hrtime(true)` is a float — the seam can be overridden if that matters.
