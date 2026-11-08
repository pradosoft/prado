# Util/Cron/TTimeScheduler

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Cron](./INDEX.md) / **`TTimeScheduler`**

## Class Info
**Location:** `framework/Util/Cron/TTimeScheduler.php`
**Namespace:** `Prado\Util\Cron`

## Overview
TTimeScheduler is a Quartz-style cron expression parser that calculates the next trigger time for a schedule. It supports the six-field format (minute, hour, day-of-month, month, day-of-week, optional year) plus special expressions like `@daily`/`@hourly`/`@yearly` and Unix-timestamp one-offs. Month and day-of-week names are accepted in 8 languages, including in ranges.

## Key Properties/Methods

- `getSchedule()` / `setSchedule($schedule)` — Gets/sets the cron schedule expression. `setSchedule` fully resets parsed state on every call (including the one-off `@<int>` trigger and the memoized field arrays).
- `getNextTriggerTime($priortime = false)` — The next Unix timestamp strictly after `$priortime` (default: now via the `time()` seam; a string is parsed with `strtotime`). Returns `?int` — null when the schedule is unset, a one-off has passed, or no trigger exists through `YEAR_MAX`. Computed in the application default timezone.
- `getNextTriggerTimes(int $count, $priortime = false): int[]` — Up to the next `$count` trigger times in ascending order; stops early when the schedule is exhausted (one-off, or `YEAR_MAX`). **@since 4.4.0.**
- `getDaysInMonth($month, $year)` — Days in a month, leap-aware. **@since 4.4.0**; `days_in_month()` is a deprecated alias.

## Schedule Format

```
minute hour day-of-month month day-of-week [year]
  *     *        *          *        *       *
```

- Fields: minute (0-59), hour (0-23), day-of-month (1-31), month (1-12 or name), day-of-week (0-6 or name), year (1970-2099, optional).
- Either day field may be `*` or `?` to leave it unrestricted. **When both day fields are restricted, a day matches when it matches EITHER** (POSIX/Vixie cron union, as at crontab.guru); when one is `*`/`?`, only the other applies. A date-and-weekday conjunction (true "Friday the 13th") is not expressible in one expression — schedule the superset and filter in the task (standard cron requires the same).
- Shortcuts: `@yearly`, `@annually`, `@monthly`, `@weekly`, `@midnight`, `@noon`, `@daily`, `@hourly`, `@minute` (the alternation is built from the shortcut table, so all documented shortcuts resolve).
- Unix timestamp: `@<timestamp>` for an efficient one-off. `@now` / `@now+N` (N seconds) resolve to a concrete `@<timestamp>` one-off at parse time via the `time()` seam.
- Ranges (`1-5`), steps (`*/5`), lists (`1,3,5`), closest weekday (`15W`), last day (`L`/`L-2`), last weekday (`5L`), nth weekday (`3#2`). Names participate in ranges (`jan-mar`, `mon-fri`).

## Implementation notes

- `getNextTriggerTime` advances a `\DateTimeImmutable` field-by-field (year→month→day→hour→minute), so the `(year, month, day)` tuple cannot desync and impossible schedules (e.g. `31` in 30-day months, `Feb 30`) correctly return null or roll to the right month/year.
- Field lookup arrays for minute/hour/month are memoized between calls; the year field is tested by the `isYearValid()` predicate (no per-call dense array). Regexes use the `/u` (Unicode) modifier so non-ASCII names case-fold correctly.
- A day field is "unrestricted" if **any** of its list elements is `?` or a plain `*` (the unrestricted flag accumulates across the list, so `*,*/5` stays unrestricted). A range whose end is an explicit `0` (e.g. day-of-week `0-0/2`) is treated as a real end, not as "no end" — so it does not silently expand to the field maximum.
- The test suite drives `getNextTriggerTime` against an **independent brute-force oracle** over a wide schedule×priortime matrix (and a fuzzer), in addition to curated cases for the `L`/`W`/`#` operators, names, and shortcuts.

## Name parsing (i18n)

- Month names: English, German, Spanish, French, Italian, Russian, Hindi, Arabic (+ abbreviations). Day-of-week names: the same **except Arabic** (Arabic uses numbered days).
- Names are case-insensitive including non-ASCII (Cyrillic, Hindi, accented) via the `/u` modifier.
- Day-of-week abbreviations must be **at least two letters** — bare single letters are rejected (ambiguous across languages). `do` resolves to **Thursday** (German Donnerstag), not Sunday.
- `L`/`#` day-of-week qualifiers require a **numeric** day (`5L`, `3#2`), not a name.
- `translateMonthOfYear`/`translateDayOfWeek` throw `TInvalidDataValueException` on a non-matching token (a safety net should the field validator and keyword table ever diverge), rather than silently mapping to January/Sunday.

## See Also

- [TCronTask](TCronTask.md)
- [TCronModule](TCronModule.md)
