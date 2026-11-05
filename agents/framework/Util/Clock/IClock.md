# Util/Clock/IClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`IClock`**

## Class Info
**Location:** `framework/Util/Clock/IClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** `Psr\Clock\ClockInterface` (composer: `psr/clock`)
**Since:** 4.4.0

## Overview
`IClock` is Prado's clock contract. It extends PSR-20 `Psr\Clock\ClockInterface` — so an `IClock` is usable anywhere a PSR clock is expected — and adds accessors consistent with `now()`: the integer/float seconds and the timezone (`timezone()` equals `now()->getTimezone()`, though implementations compute it on a fast path). It also adds `sleep()`, a clock-aware pause (the same idea as Symfony's `ClockInterface::sleep()`). Prado clock classes implement `IClock`; [`TClockTrait`](TClockTrait.md) provides all of them.

## Methods

| Method | Description |
|--------|-------------|
| `now(): \DateTimeImmutable` | (from PSR-20) the current moment. |
| `time(): int` | the current Unix timestamp in whole seconds, from `now()`. |
| `microtime(): float` | the current Unix timestamp with microseconds, from `now()`. |
| `timezone(): \DateTimeZone` | the timezone `now()` reports in (equals `now()->getTimezone()`). |
| `sleep(float\|int $seconds): void` | Pause for `$seconds`. A real clock blocks; a pinned clock advances its instant instead. |

## Implemented By
Sources [`TNativeClock`](TNativeClock.md), its tuned [`TTimezoneClock`](TTimezoneClock.md)/[`TUTCClock`](TUTCClock.md), and [`TMockClock`](TMockClock.md) (via [`TClockTrait`](TClockTrait.md)); [`TClockDecorator`](TClockDecorator.md) and its subclasses [`TOffsetClock`](TOffsetClock.md) and [`TTimezoneDecorator`](TTimezoneDecorator.md). Also [`TCronModule`](../Cron/TCronModule.md).
