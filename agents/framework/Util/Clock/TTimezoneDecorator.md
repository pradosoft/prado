# Util/Clock/TTimezoneDecorator

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TTimezoneDecorator`**

## Class Info
**Location:** `framework/Util/Clock/TTimezoneDecorator.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** [`TClockDecorator`](TClockDecorator.md) (an [`IClock`](IClock.md) holding one)
**Since:** 4.4.0

## Overview
`TTimezoneDecorator` expresses the held clock's `now()` in a **chosen timezone**. `timezone()` returns the chosen zone and `now()` is the held instant re-expressed in it via `setTimezone()`. The instant is unchanged: `time()`/`microtime()`/`sleep()` are the base decorator's forwards, so `time()` equals `now()->getTimestamp()`. DST in the chosen zone is PHP's job in `setTimezone()`; nothing is cached.

It wraps any `IClock`: a pinned [`TMockClock`](TMockClock.md), a [`TOffsetClock`](TOffsetClock.md), or an injected clock, without touching it. For a source that builds `now()` in a zone directly (one allocation instead of two), use [`TTimezoneClock`](TTimezoneClock.md). The two decorators compose in either order.

```php
$tokyo = new TTimezoneDecorator('Asia/Tokyo');          // wraps a TNativeClock
$tokyo->now()->format('H:i');                           // wall-clock time in Tokyo
$tokyo->time();                                         // the true Unix timestamp
$test = new TTimezoneDecorator('Europe/London', $mock); // a pinned clock seen from London
$test->setTimezone('America/New_York');                 // change the zone in place
```

## Methods

| Method | Description |
|--------|-------------|
| `__construct(\DateTimeZone\|string\|null $timezone = null, ?IClock $clock = null)` | The zone (null: the system default) and the clock to wrap (null wraps [`TNativeClock`](TNativeClock.md)). |
| `now(): \DateTimeImmutable` | The held instant expressed in the chosen zone. |
| `timezone(): \DateTimeZone` | The chosen zone, a field read. |
| `time()` / `microtime()` / `sleep()` | Forward to the held clock (inherited). |
| `getTimezone(): \DateTimeZone` / `setTimezone(\DateTimeZone\|string): static` | The chosen zone as a `TComponent` property; the setter returns `$this`. |

## Notes
- Registered in `framework/classes.php` as `TTimezoneDecorator`.
- Overrides `now()` and `timezone()` together, like the zone sources; never `time()`/`microtime()`.
