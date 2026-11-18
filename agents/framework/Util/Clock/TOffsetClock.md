# Util/Clock/TOffsetClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TOffsetClock`**

## Class Info
**Location:** `framework/Util/Clock/TOffsetClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** [`TClockDecorator`](TClockDecorator.md)
**Since:** 4.4.0

## Overview
`TOffsetClock` is a [`TClockDecorator`](TClockDecorator.md) that shifts the held clock's `now()` by a **fixed offset of seconds** — an integer or a float (sub-second). A positive offset runs the clock ahead, a negative one behind; the held clock's timezone is preserved (`timezone()` forwards to it). Useful to simulate clock skew, or to run a pinned [`TMockClock`](TMockClock.md) ahead in tests. To express a held clock's `now()` in another *timezone* (same instant), use [`TTimezoneDecorator`](TTimezoneDecorator.md); for a zone source, [`TTimezoneClock`](TTimezoneClock.md).

`setOffset()` changes the offset in place (a `TComponent` property, so it is configurable). A NAN or infinite offset throws `offsetclock_offset_invalid`.

The static `shift()` moves any instant by a number of seconds. It adds the whole seconds and the microseconds apart, so the result is exact to the microsecond on either side of the Unix epoch; [`TMockClock`](TMockClock.md) builds and advances its pinned instant through it.

```php
$ahead = new TOffsetClock(90);        // 90 seconds ahead of real time
$ahead->now()->getTimestamp();        // time() + 90
$ahead->setOffset(120);               // now 120 seconds ahead
$ahead->setOffset(-0.5);              // now 0.5s behind
$test = new TOffsetClock(10, $mock);  // 10 seconds ahead of a pinned clock
```

## Methods

| Method | Description |
|--------|-------------|
| `__construct(float\|int $offset = 0, ?IClock $clock = null)` | The offset in seconds and the clock to wrap (null wraps [`TNativeClock`](TNativeClock.md)). |
| `now(): \DateTimeImmutable` | The held clock's time shifted by the offset, in the held clock's zone. |
| `time(): int` / `microtime(): float` | The held clock's value shifted by the offset, consistent with `now()`. An integer offset adds to the held `time()` directly; a fractional one floors the shifted `microtime()`. No `\DateTimeImmutable` on this path. |
| `timezone()` / `sleep()` | Forward to the held clock. |
| `getOffset(): float\|int` | The configured offset in seconds. |
| `setOffset(float\|int $value): static` | Changes the offset in place; returns `$this`. Throws `offsetclock_offset_invalid` for NAN or infinity. |
| `static shift(\DateTimeInterface $instant, float\|int $seconds): \DateTimeImmutable` | The instant moved by the seconds, in its zone. Throws `offsetclock_shift_invalid` when the seconds are NAN or infinite or the result is past the range of a date. |

## Notes
- Registered in `framework/classes.php` as `TOffsetClock`.
- Overrides `now()`, `time()`, and `microtime()` together so all three report the same shifted instant.
- PHP's `U.u` format is whole seconds rounded down plus positive microseconds, so `(float) format('U.u')` and `createFromFormat('U.u', sprintf('%.6f', ...))` misread a fractional instant before the epoch. `shift()` reads `getTimestamp()` and `format('u')` and builds the result from whole seconds rounded down.
