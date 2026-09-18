# Util/Clock/TClockTrait

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TClockTrait`**

## Class Info
**Location:** `framework/Util/Clock/TClockTrait.php`
**Namespace:** `Prado\Util\Clock`
**Since:** 4.4.0

## Overview
`TClockTrait` is the default implementation of [`IClock`](IClock.md) for a class that *is* a clock. It provides `now()` (the current system time) and the fast real-clock `time()`/`microtime()`/`timezone()`, plus `sleep()` (wraps `\usleep()`). `time()`/`microtime()`/`timezone()` read the global clock / system default zone directly rather than building from `now()`, keeping them cheap on hot paths (`timezone()` returns a cached `\DateTimeZone`, rebuilt only when the default zone name changes); a clock whose `now()` is transformed (a pin) overrides them to stay consistent (see [`TMockClock`](TMockClock.md)); decorators do not use the trait and forward to a held clock instead. The using class declares `implements IClock`; this trait satisfies it.

```php
class MyClock extends \Prado\TComponent implements \Prado\Util\Clock\IClock
{
    use \Prado\Util\Clock\TClockTrait;
}
```

## Methods

| Method | Description |
|--------|-------------|
| `now(): \DateTimeImmutable` | The current system time (override to change the source). |
| `time(): int` | Fast `\time()` (real clock; not built from `now()`, to avoid a `\DateTimeImmutable` on hot paths). |
| `microtime(): float` | Fast `\microtime(true)` (real clock; not built from `now()`). |
| `timezone(): \DateTimeZone` | The system default zone from `systemTimezone()` (fast; not built from `now()`). |
| `systemTimezone(): \DateTimeZone` | Protected. The system default zone as a cached `\DateTimeZone`, rebuilt only when `date_default_timezone_get()` reports a new name; no allocation on repeated calls. |
| `sleep(float\|int $seconds): void` | Blocks with `\usleep()`; a non-positive value returns immediately. |

## Used By
[`TNativeClock`](TNativeClock.md) (the trait as a component, overriding nothing; [`TTimezoneClock`](TTimezoneClock.md)/[`TUTCClock`](TUTCClock.md) override `now()` and `timezone()` together for a fixed zone), the standalone [`TMockClock`](TMockClock.md) (overrides the accessors to follow its pin), and [`TCronModule`](../Cron/TCronModule.md) (a module that is itself a clock). `TCronModule` uses the trait's accessors as-is — its `now()` is system time in the system zone, so they stay consistent.
