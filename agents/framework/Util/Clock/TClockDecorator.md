# Util/Clock/TClockDecorator

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TClockDecorator`**

## Class Info
**Location:** `framework/Util/Clock/TClockDecorator.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** `Prado\TComponent`
**Implements:** [`IClock`](IClock.md)
**Uses:** [`TClockAwareTrait`](TClockAwareTrait.md)
**Since:** 4.4.0

## Overview
`TClockDecorator` is an [`IClock`](IClock.md) that **holds** another clock (through [`TClockAwareTrait`](TClockAwareTrait.md)) and forwards every reading to it. Each of `now()`, `time()`, `microtime()`, `timezone()`, and `sleep()` is a single forward to the held `getClock()`. A subclass transforms one or more of them and calls `parent` for the held value.

The held clock is a constructor argument. When null, `getClock()` lazily creates the default through the trait's `createClock()`, which instantiates the class named by `getClockClass()` ([`TNativeClock`](TNativeClock.md)). A subclass overrides `getClockClass()` (or `createClock()`) to change the clock it wraps by default. `setClock()` replaces the held clock.

Decorators are the one place routing exists in the clock family. Sources ([`TNativeClock`](TNativeClock.md), [`TMockClock`](TMockClock.md)) read time and never defer to another clock; the decorators [`TOffsetClock`](TOffsetClock.md) (shift by seconds) and [`TTimezoneDecorator`](TTimezoneDecorator.md) (express `now()` in a zone) transform a held clock. Zone clocks ([`TTimezoneClock`](TTimezoneClock.md), [`TUTCClock`](TUTCClock.md)) are tuned sources, not decorators; a decorator wraps one, or a pinned `TMockClock` for deterministic tests.

```php
$clock = new TClockDecorator();          // wraps a TNativeClock
$clock->now();                           // the native clock's now()
$clock = new TClockDecorator($mock);     // wraps a pinned TMockClock
$clock->now();                           // the pinned instant
$clock->sleep(60);                       // advances the pin (no real delay)
```

## Methods

| Method | Description |
|--------|-------------|
| `__construct(?IClock $clock = null)` | The clock to wrap; null wraps the `getClockClass()` default on first use. |
| `now()` / `time()` / `microtime()` / `timezone()` / `sleep()` | Forward to the held clock. |
| `getClock(): IClock` / `setClock(?IClock): static` | The held clock (from [`TClockAwareTrait`](TClockAwareTrait.md)); null recreates the default on next use. |
| `createClock(): IClock` / `getClockClass(): string` | Protected seams for the default held clock (from [`TClockAwareTrait`](TClockAwareTrait.md)); override to wrap a different clock by default. |

## Notes
- Registered in `framework/classes.php` as `TClockDecorator`.
- `setClock()` means "hold a clock" here as everywhere; the mock's pin is `setNow()`.
- Subclass settings are `TComponent` properties changed in place (`setOffset()`, `setTimezone()`); the held clock is untouched.
