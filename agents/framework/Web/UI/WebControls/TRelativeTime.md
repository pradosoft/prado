# Web/UI/WebControls/TRelativeTime

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TRelativeTime`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TRelativeTime.php`
**Namespace:** `Prado\Web\UI\WebControls`
**Since:** 4.4.0 (issue #777)

## Overview
Live, self-updating relative time inside an HTML5 `<time>` element ("5 minutes ago",
"in 3 hours"). Extends `[TTime](./TTime.md)`, so it accepts the same `DateTime` inputs,
emits a machine-readable `datetime` attribute, and reuses the same localized absolute
formatting. Client JavaScript keeps the visible text current and, when `ClickForDateTime`
is enabled, toggles between the relative text and the absolute date on click.

JavaScript: `controls/relativetime.js` — client class `Prado.WebUI.TRelativeTime`,
registered as the `relativetime` package in `Web/Javascripts/packages.php`.

Extends `[TTime](./TTime.md)`.

## Origin instant
`DateTime` is the origin the relative text counts from:

| Input | Origin |
|---|---|
| `DateTimeInterface` | the instant itself |
| `DateInterval` | now minus the interval |
| unset / unparsable | the current time |

## Internationalization
The visible relative text is composed on the client from localized CLDR duration-unit
patterns supplied by the server:

1. The server reads plural patterns for the control's culture at the `Mode` width through
   `CultureInfo::getUnitPatterns()` and passes them in the client options (`UnitPatterns`),
   along with the culture as a BCP 47 tag.
2. The client selects the plural category with `Intl.PluralRules` and formats the number
   with `Intl.NumberFormat`.
3. Direction ("ago" / "in") is applied from `Intl.RelativeTimeFormat` when available. A
   single unit uses the formatter output directly (it carries inflection, e.g. Russian
   accusative "1 минуту назад"); a multi-unit magnitude is spliced into the leading unit's
   output so the display keeps one localized direction. Without the formatter the plain
   magnitude is shown.

**Initial content is rendered server-side as the relative text** from the same CLDR
`fields` data (`CultureInfo::getRelativeTimePatterns()` + `selectPluralCategory()`), so
the first client render shows no change and a page without JavaScript shows a snapshot
of the render time. The click target and the default `title` tooltip are the localized
absolute date via the inherited `formatTextValue()`; `TextFormat` selects that format and
defaults to `DateTimeFormat`. A developer-set `ToolTip` replaces the default title.

## TRelativeTimeMode Enum

```php
TRelativeTimeMode::Long    // "5 minutes"  (CLDR units)
TRelativeTimeMode::Short   // "5 min"      (CLDR unitsShort)
TRelativeTimeMode::Narrow  // "5m"         (CLDR unitsNarrow)
```

Widths fall back per unit narrow → short → long when a locale omits the requested width.

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `DateTime` | mixed | now | Origin instant (inherited from `TTime`) |
| `Mode` | TRelativeTimeMode | Long | Unit width |
| `SignificantElements` | int | 1 | Number of duration units shown |
| `PartialElement` | bool | true | Show the next smaller unit when the leading unit is close to changing |
| `DisplayZero` | bool | false | Include units with a zero value |
| `Separator` | string | `' '` | Text between units |
| `UseServerTime` | bool | true | Count from the server clock rather than the client clock |
| `ClickForDateTime` | bool | true | Toggle to the absolute date on click |
| `DurationOnly` | bool | false | Bare localized duration (`5 minutes`) without "ago"/"in"; for countdown labels, uptime, column headings that supply the direction |
| `YearsWithMonths` | int | 2 | Partial threshold: show months when years ≤ this |
| `MonthsWithWeeks` | int | 3 | Partial threshold: show weeks when months ≤ this |
| `WeeksWithDays` | int | 2 | Partial threshold: show days when weeks ≤ this |
| `DaysWithHours` | int | 3 | Partial threshold: show hours when days ≤ this |
| `HoursWithMinutes` | int | 3 | Partial threshold: show minutes when hours ≤ this |
| `MinutesWithSeconds` | int | 4 | Partial threshold: show seconds when minutes ≤ this |

## Template Examples

```xml
<!-- "5 minutes ago", updating live, toggling to the date on click -->
<com:TRelativeTime DateTime="2024-06-15 10:30:00" />

<!-- Narrow units, two components, no click toggle -->
<com:TRelativeTime DateTime="1718445000" Mode="Narrow"
    SignificantElements="2" ClickForDateTime="false" />

<!-- Bare duration where the label supplies the direction: "Sale ends in: 3 hours" -->
Sale ends in: <com:TRelativeTime DateTime="2024-06-15 18:00:00" DurationOnly="true" />
```

## Rendering
- `getClientEnhanced()` gates the client script and the auto-generated element `id`: it
  requires an attached page whose client supports JavaScript and an enabled control.
- Without JavaScript the element renders the relative text as a render-time snapshot,
  a valid `datetime` attribute, and the absolute date in `title`; the control is fully
  functional as static markup.
- `getRelativeText()` mirrors the client algorithm (significant elements, partial
  thresholds, `DisplayZero`, `Separator`) so server and client output match.
- The client redraw loop stops on its own when the element leaves the document, so a
  control removed by a callback does not leak a timer.

## Related
- `[TTime](./TTime.md)` — the absolute-time base control.
- `CultureInfo::getUnitPatterns()` / `getRelativeTimePatterns()` / `selectPluralCategory()` —
  server-side sources of the localized patterns and plural rules.
- Tests: `tests/unit/Web/UI/WebControls/TRelativeTimeTest.php`,
  `tests/unit/I18N/core/CultureInfoTest.php`, `tests/js/controls/relativetime.test.js`.
