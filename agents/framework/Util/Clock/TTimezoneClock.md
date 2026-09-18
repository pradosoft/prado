# Util/Clock/TTimezoneClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TTimezoneClock`**

## Class Info
**Location:** `framework/Util/Clock/TTimezoneClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** [`TNativeClock`](TNativeClock.md) (an [`IClock`](IClock.md))
**Since:** 4.4.0

## Overview
`TTimezoneClock` is a [`TNativeClock`](TNativeClock.md) that reports `now()` in a **fixed timezone** instead of the system default — for presenting "now" in another timezone. `timezone()` returns the configured `\DateTimeZone` from a field, and `now()` builds the current instant in it. `time()`/`microtime()` are the inherited bare system reads: a Unix timestamp has no zone, so `time()` equals `now()->getTimestamp()`.

The timezone is immutable per instance; `withTimezone()` returns a new clock for a different zone. Any value `\DateTimeZone` accepts is allowed — region (`Asia/Tokyo`), abbreviation (`PST`/`PDT`), or offset (`+05:30`). [`TUTCClock`](TUTCClock.md) is the UTC subclass.

It is a source, not a decorator: nothing is held. For a pinned instant shown in a zone use [`TMockClock`](TMockClock.md)'s `setTimezone()`; to express any held clock's `now()` in a zone use [`TTimezoneDecorator`](TTimezoneDecorator.md); to shift the instant, wrap it in a [`TOffsetClock`](TOffsetClock.md).

```php
$tokyo = new TTimezoneClock('Asia/Tokyo');
$tokyo->now()->format('H:i');     // wall-clock time in Tokyo
$tokyo->timezone()->getName();    // "Asia/Tokyo"
$tokyo->time();                   // the true Unix timestamp
$utc = $tokyo->withTimezone('UTC');
$ahead = new TOffsetClock(90, $tokyo);   // 90s ahead, still in Tokyo
```

## Methods

| Method | Description |
|--------|-------------|
| `__construct(\DateTimeZone\|string\|null $timezone = null)` | Timezone to report in; null uses the system default. |
| `now(): \DateTimeImmutable` | The current system time in this clock's timezone, built from the zone field. |
| `timezone(): \DateTimeZone` | The configured zone, a field read. |
| `time(): int` / `microtime(): float` / `sleep()` | Inherited from [`TClockTrait`](TClockTrait.md): the bare system reads and a blocking pause. |
| `withTimezone(\DateTimeZone\|string $timezone): static` | A new clock in the given timezone, leaving this one unchanged. |

## Notes
- Registered in `framework/classes.php` as `TTimezoneClock`.
- Overrides `now()` and `timezone()` together; `time()`/`microtime()`/`sleep()` are inherited from [`TClockTrait`](TClockTrait.md).
