# Util/Clock/TUTCClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TUTCClock`**

## Class Info
**Location:** `framework/Util/Clock/TUTCClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** [`TTimezoneClock`](TTimezoneClock.md) (a [`TNativeClock`](TNativeClock.md) / [`IClock`](IClock.md))
**Since:** 4.4.0

## Overview
`TUTCClock` is a [`TTimezoneClock`](TTimezoneClock.md) fixed to **UTC**: it reports `now()` in UTC regardless of the system default timezone — for storage, logging, and cross-host comparisons. `timezone()` is a field read of the UTC zone built once in the constructor, and `time()`/`microtime()` are the true Unix timestamp. Unlike `TTimezoneClock`, the zone cannot be changed: `withTimezone()` throws.

```php
$clock = new TUTCClock();
$clock->now()->getTimezone()->getName();   // "UTC"
$clock->now()->format('H:i');              // wall-clock time in UTC
$clock->time();                            // the true Unix timestamp
```

## Methods

| Method | Description |
|--------|-------------|
| `now(): \DateTimeImmutable` | The current instant in UTC (inherited). |
| `timezone(): \DateTimeZone` | The UTC zone (inherited field read). |
| `time(): int` / `microtime(): float` | The true Unix timestamp (inherited bare reads). |
| `withTimezone(\DateTimeZone\|string): static` | Throws `TInvalidOperationException` — the zone is fixed to UTC. |

## Notes
- Registered in `framework/classes.php` as `TUTCClock`.
- Error code: `utcclock_timezone_fixed`.
