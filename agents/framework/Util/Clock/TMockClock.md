# Util/Clock/TMockClock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Clock](./INDEX.md) / **`TMockClock`**

## Class Info
**Location:** `framework/Util/Clock/TMockClock.php`
**Namespace:** `Prado\Util\Clock`
**Extends:** `Prado\TComponent`
**Implements:** [`IClock`](IClock.md)
**Since:** 4.4.0

## Overview
`TMockClock` is a standalone **settable** clock, the Prado equivalent of Symfony's `MockClock`. While `Now` is null it reports the real system time; once `setNow()` pins an instant, `now()` returns that fixed instant and `sleep()` advances it instead of blocking. Use it to freeze or control time — primarily in tests, or anywhere deterministic time is needed. Unlike [`TNativeClock`](TNativeClock.md) (the safe default that cannot be pinned), reaching for `TMockClock` is a deliberate choice.

It does **not** extend [`TNativeClock`](TNativeClock.md), so it is a *sibling* of the real clocks. It still implements [`IClock`](IClock.md), so it drops into any [`TClockAwareTrait`](TClockAwareTrait.md) holder and any [`TClockDecorator`](TClockDecorator.md) wraps it (`new TOffsetClock(10, $mock)`). `time()`/`microtime()` read the pinned instant when set and otherwise fall through to the fast [`TClockTrait`](TClockTrait.md) path, so the unpinned clock stays cheap on hot paths.

Each reported value has a setter. `setNow()` pins the instant from a `\DateTimeInterface`, a string, an int (whole-second Unix timestamp) or a float (fractional); `setTime()`/`setMicrotime()` are the typed forms of the numeric pins (built in `timezone()`); `setTimezone()` fixes the reported zone. `timezone()` reports the pinned instant's zone, else the fixed zone, else the system default. The pin is named `Now`, not `Clock`, so `setClock()` only ever means "hold a clock" ([`TClockAwareTrait`](TClockAwareTrait.md)).

```php
$clock = new TMockClock();
$clock->now();                       // real system time
$clock->setNow('@1700000000');
$clock->now()->getTimestamp();       // 1700000000
$clock->time();                      // 1700000000 (reads the pin)
$clock->sleep(60);                   // advances the pin to 1700000060 (no real delay)
$clock->setNow(1700000000);          // pin by int timestamp (setTime() is the typed form)
$clock->setNow(1700000000.25);       // pin by float timestamp (setMicrotime() is the typed form)
$clock->setTimezone('Asia/Tokyo');   // the pin is now expressed in Tokyo (same instant)
$clock->setNow(null);                // back to the system clock, still reporting in Tokyo
```

## Methods

| Method | Description |
|--------|-------------|
| `now(): \DateTimeImmutable` | The pinned instant when set, otherwise the real system time in `timezone()`. |
| `time(): int` / `microtime(): float` | The pinned timestamp when set, otherwise the fast real-time path (overrides [`TClockTrait`](TClockTrait.md)). |
| `timezone(): \DateTimeZone` | The pinned instant's zone, else the fixed zone from `setTimezone()`, else the cached system default (the trait's `systemTimezone()`). |
| `sleep(float\|int $seconds): void` | When pinned, advances the instant by `$seconds` (microsecond fractions honored, timezone preserved, negative rewinds) instead of blocking; when not pinned, a real `\usleep()` pause. |
| `getNow(): ?\DateTimeImmutable` | The pinned instant, or null when following the system clock. |
| `setNow(\DateTimeInterface\|string\|int\|float\|null $value): static` | Pin to a fixed instant — a `\DateTime` is converted to immutable, a string is parsed by `\DateTimeImmutable` (e.g. `'2024-01-01 12:00'`, `'@1700000000'`; a string without a zone is parsed in the fixed zone when one is set), an int or float is a Unix timestamp built in `timezone()`, null releases. With a fixed zone the pin is re-expressed in it. Returns `$this`. |
| `setTime(?int $time): static` | `setNow()` typed for a whole-second Unix timestamp; null releases. |
| `setMicrotime(?float $microtime): static` | `setNow()` typed for a fractional Unix timestamp; null releases. |
| `getTimezone(): ?\DateTimeZone` | The fixed reported zone, or null when none is set. |
| `setTimezone(\DateTimeZone\|string\|null $timezone): static` | Fix the reported zone. A pinned instant is re-expressed in it (same instant); an unpinned clock reports real time in it. Null clears the fixed zone; a pinned instant keeps the zone it already has. |

## Notes
- Registered in `framework/classes.php` as `TMockClock`.
- Pairs with [`TClockAwareTrait`](TClockAwareTrait.md): inject a `TMockClock` via the holder's `setClock()`, then pin it with the mock's `setNow()` (or `setTime()`/`setMicrotime()`) to make a clock-dependent class deterministic. The holder default is [`TNativeClock`](TNativeClock.md), not `TMockClock`.
- Accessor pattern: `getNow()`/`getTimezone()` return the raw settings (nullable); `now()`/`time()`/`microtime()`/`timezone()` return the reported values.
