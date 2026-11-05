# Util/Clock/TNativeClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TNativeClock`**

## Class Info
**Location:** `framework/Util/Clock/TNativeClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** `Prado\TComponent`
**Implements:** [`IClock`](IClock.md)
**Since:** 4.4.0

## Overview
`TNativeClock` is the framework's real-time clock, the source the [`TClockDecorator`](TClockDecorator.md) family wraps by default. It is [`TClockTrait`](TClockTrait.md) as a component: `now()`/`time()`/`microtime()`/`timezone()`/`sleep()` are the trait's direct system reads (`new \DateTimeImmutable()` in the system zone; the fast `\time()`/`\microtime()`/system-zone/`\usleep()` path with no `\DateTimeImmutable` built). It cannot be pinned. It is the safe **default** for classes that read time ([`TClockAwareTrait`](TClockAwareTrait.md)), so a settable clock is a deliberate choice — inject a [`TMockClock`](TMockClock.md) for controllable time, a [`TTimezoneClock`](TTimezoneClock.md) for a fixed timezone, or a [`TUTCClock`](TUTCClock.md). It is a *sibling* of `TMockClock`, so a settable clock can never be mistaken (`instanceof`) for the real one.

`now()` reports in `timezone()`. A subclass that changes the reported zone overrides both together; `time()`/`microtime()` stay the trait's bare reads because a Unix timestamp has no zone. A subclass that changes the time source overrides `now()`/`time()`/`microtime()` together. [`TTimezoneClock`](TTimezoneClock.md) is the subclass tuned to a fixed zone and [`TUTCClock`](TUTCClock.md) its UTC form. Shifting the instant of a held clock is a decorator: [`TOffsetClock`](TOffsetClock.md).

There is no inner clock: a `TNativeClock` never routes to another clock. Holding a clock is the concern of [`TClockAwareTrait`](TClockAwareTrait.md) and [`TClockDecorator`](TClockDecorator.md); a test that needs a fixed instant injects a [`TMockClock`](TMockClock.md) there.

```php
$clock = new TNativeClock();
$clock->now();                       // \DateTimeImmutable, real time in the system zone
$clock->time();                      // \time()
$clock->timezone()->getName();       // the default timezone (fast, via TClockTrait — no DateTimeImmutable)
TNativeClock::getSystemTimezone();   // the application/system default timezone (static)
```

## Methods

| Method | Description |
|--------|-------------|
| `now(): \DateTimeImmutable` | The current system time in the system zone (inherited). The overridable time source. |
| `time(): int` / `microtime(): float` / `timezone(): \DateTimeZone` / `sleep(float\|int)` | From [`TClockTrait`](TClockTrait.md): the fast real-time/system-zone path, blocking `\usleep()`. |
| `static getSystemTimezone(): \DateTimeZone` | The application default timezone, from `date_default_timezone_get()` — the system default regardless of the zone a subclass instance reports. |

## Notes
- Registered in `framework/classes.php` as `TNativeClock`.
- Overriding `now()` alone leaves `time()`/`microtime()` on the real clock; a subclass that transforms the instant overrides all three.
- For freezable time use [`TMockClock`](TMockClock.md); for a fixed display timezone use [`TTimezoneClock`](TTimezoneClock.md) or [`TUTCClock`](TUTCClock.md); to shift the instant by seconds use [`TOffsetClock`](TOffsetClock.md).
