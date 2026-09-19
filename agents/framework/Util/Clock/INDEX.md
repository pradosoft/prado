# Util/Clock

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / **`Clock`**

## Purpose

PSR-20 clock abstractions: a single, overridable source of "now" so subsystems and tests read time through one seam instead of calling the global `\time()`/`\microtime()` directly. Backed by the `psr/clock` package.

## Interfaces & Traits

- **[`IClock`](IClock.md)** — Prado's clock contract. Extends PSR-20 `Psr\Clock\ClockInterface` (so an `IClock` is usable anywhere a PSR clock is expected) and adds `time(): int`, `microtime(): float`, and `timezone(): \DateTimeZone` (consistent with `now()`, on a fast path), plus a clock-aware `sleep(float|int): void`.
- **[`TClockTrait`](TClockTrait.md)** — default `IClock` implementation for a class that *is* a clock: `now()` (system time) + `time()`/`microtime()`/`timezone()` derived from it, and `sleep()` via `\usleep()`. The using class declares `implements IClock`.
- **[`TClockAwareTrait`](TClockAwareTrait.md)** — for a class that *holds* a clock: `getClock()`/`setClock()` (an `IClock`), lazily creating the default via `createClock()` (the class named by the overridable `getClockClass()`, default `TNativeClock`). `createClock()` validates the class is an `IClock`.
- **[`TApplicationClockAwareTrait`](TApplicationClockAwareTrait.md)** — extends `TClockAwareTrait` for request-scoped holders that share the one application "now": `getClock()` returns an explicit local clock, else the `Prado::getApplication()` clock read live, else a lazily created local `TNativeClock`. The app clock is read every call (not memoized), so setting it later still propagates; `setClock(null)` resumes following the app. `TApplication` itself holds the shared clock via `TClockAwareTrait` (`DEFAULT_CLOCK_CLASS` + `setClockClass()` config seam).

## Clocks

- **[`TNativeClock`](TNativeClock.md)** — the real-time clock, the source decorators wrap by default: reads the system clock directly. `now()`/`time()`/`microtime()`/`timezone()`/`sleep()` are the `TClockTrait` system reads in the system default zone; a subclass changes the reported zone by overriding `now()` and `timezone()` together. **Cannot be pinned**; no inner clock. Static `getSystemTimezone()` gives the app default zone. The safe **default** for time-dependent classes.
- **[`TMockClock`](TMockClock.md)** — a standalone, settable clock (Symfony `MockClock` equivalent): `extends TComponent implements IClock`. A pinned instant via `setNow()` (a `\DateTimeInterface`, a string, or null), `setTime()`/`setMicrotime()` (Unix timestamps), and a fixed reported zone via `setTimezone()`; `sleep()` advances the pin instead of blocking. It does **not** extend `TNativeClock`, so it is a **sibling** of the real clocks — making controllable time a deliberate choice. Still an `IClock`, so it drops into any `TClockAwareTrait` holder.
- **[`TClockDecorator`](TClockDecorator.md)** — an `IClock` that **holds** one (`use TClockAwareTrait`) and forwards every reading to it; the base of `TOffsetClock` and `TTimezoneDecorator`. Constructor takes the clock to wrap; null wraps the `getClockClass()` default (`TNativeClock`), the seam a subclass overrides to wrap a different clock by default.
- **[`TTimezoneClock`](TTimezoneClock.md)** — a `TNativeClock` reporting `now()` in a **fixed timezone** (a field read; `now()` builds in it) via immutable `withTimezone()`. Accepts any string `\DateTimeZone` takes (region `Asia/Tokyo`, abbreviation `PST`/`PDT`, offset `+05:30`). `time()`/`microtime()` are the true epoch.
- **[`TUTCClock`](TUTCClock.md)** — a `TTimezoneClock` fixed to **UTC** (`withTimezone()` throws `utcclock_timezone_fixed`). For storage/logging/cross-host comparisons.
- **[`TOffsetClock`](TOffsetClock.md)** — a `TClockDecorator` that shifts the held clock's `now()` by an **offset of seconds** (int or float), settable via `setOffset()`. Changes the instant (`time()`/`microtime()` shift too); preserves the held timezone. Wraps a `TTimezoneClock` for a zone, or a pinned `TMockClock` for tests.
- **[`TTimezoneDecorator`](TTimezoneDecorator.md)** — a `TClockDecorator` that expresses the held clock's `now()` in a **chosen zone** (`setTimezone()` on the held instant); `time()`/`microtime()` forward unchanged. Wraps any clock (a pinned `TMockClock`, a `TOffsetClock`) to present it in a zone. `setTimezone()` changes the zone in place.

## Monotonic timer

- **[`TMonotonicClock`](TMonotonicClock.md)** — a thin reader over `\hrtime()` for measuring **elapsed time** (`nanoseconds()`/`seconds()`/`elapsed()`). Separate from the wall-clock `IClock` hierarchy: monotonic, no timezone, no epoch. For durations/timeouts.

## Design notes

- **Be a clock vs hold a clock:** a class that is a time source extends `TNativeClock` (or `implements IClock` + `use TClockTrait`, e.g. [`TCronModule`](../Cron/TCronModule.md)); a class that depends on time `use TClockAwareTrait`; a class that is both (transforms a held clock) extends `TClockDecorator`.
- **Sources vs decorators:** sources (`TNativeClock`, `TMockClock`) read time and never defer to another clock. Sources tuned to a zone (`TTimezoneClock`, `TUTCClock`) override `now()` and `timezone()` together; `time()`/`microtime()` stay the bare reads. Only `TMockClock` overrides the trait's `time()`/`microtime()`. Decorators (`TOffsetClock`, `TTimezoneDecorator`) hold a clock and transform it; every forward is a single call with no fallback branching. Decorators compose, and wrap a pinned `TMockClock` for deterministic tests (`new TOffsetClock(10, $mock)`).
- **Deliberate mutability:** the holder default is `TNativeClock`, which cannot be pinned; reaching for a pinnable `TMockClock`, a `TTimezoneClock`, or a decorator is an explicit holder `setClock()`/`getClockClass()` choice. The mock's pin is `setNow()`, so `setClock()` only ever means "hold a clock".
- **Timezone vs offset:** `TTimezoneClock` (a source) and `TTimezoneDecorator` (over a held clock) change the *displayed* zone of `now()` (same instant); `TOffsetClock` changes the *instant itself* by N seconds (preserving the held zone). A Unix timestamp has no zone, so `time()` equals `now()->getTimestamp()` for every clock.
- **Fast accessors:** `time()`/`microtime()`/`timezone()` avoid building a `\DateTimeImmutable` on the common path. The trait reads the real clock directly and `TNativeClock` inherits that; `TClockDecorator` forwards to the held clock; `TMockClock`/`TOffsetClock` override only what their `now()` transforms (the pin, the offset); `TTimezoneDecorator` forwards them unchanged. `TTimezoneClock`/`TUTCClock` return their zone from a field and inherit the bare `time()`/`microtime()`, so no clock builds a `\DateTimeImmutable` in `time()`/`microtime()`/`timezone()`.
- **Sleeping is part of the seam:** `sleep(float|int $seconds)` mirrors Symfony's `ClockInterface::sleep()`. A real clock blocks (`\usleep()`); a pinned `TMockClock` advances its instant instead, so time-dependent code under a frozen clock runs without real delay. A decorator forwards `sleep()` to the held clock.

## Conventions

- Registered in `framework/classes.php` (`IClock`, `TApplicationClockAwareTrait`, `TClockAwareTrait`, `TClockDecorator`, `TClockTrait`, `TMockClock`, `TMonotonicClock`, `TNativeClock`, `TOffsetClock`, `TTimezoneClock`, `TTimezoneDecorator`, `TUTCClock`).
- `@since 4.4.0` for all members; requires the `psr/clock` composer dependency.
