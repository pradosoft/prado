# Web/UI/WebControls/TDatePicker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [UI](../INDEX.md) / [WebControls](./INDEX.md) / **`TDatePicker`**

## Class Info
**Location:** `framework/Web/UI/WebControls/TDatePicker.php`
**Namespace:** `Prado\Web\UI\WebControls`

## Overview
Date picker control with popup calendar. Extends `TTextBox`, adding a calendar button/icon that opens a popup date selector. The selected date is stored in the text box in a configurable format. Supports both a text input mode and drop-down list mode.

JavaScript: `datepicker/datepicker.js` (published via `TAssetManager`).

Extends `[TTextBox](./TTextBox.md)`.

## Constants

```php
TDatePicker::SCRIPT_PATH = 'datepicker'
```

## Enums

### TDatePickerMode
```php
TDatePickerMode::Basic        // Simple calendar popup (default)
TDatePickerMode::DropDownList // Drop-down selectors for day/month/year
```

### TDatePickerInputMode
```php
TDatePickerInputMode::TextBox     // Text input + calendar button (default)
TDatePickerInputMode::DropDownList // Drop-down lists only (no text input)
```

### TDatePickerPositionMode
```php
TDatePickerPositionMode::Bottom  // Popup opens below input (default)
TDatePickerPositionMode::Top     // Popup opens above input
```

## Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `DateFormat` | string | `dd-MM-yyyy` | Format string for date display and parsing |
| `Mode` | TDatePickerMode | Basic | Calendar style |
| `InputMode` | TDatePickerInputMode | TextBox | Input style |
| `PositionMode` | TDatePickerPositionMode | Bottom | Popup position |
| `ShowCalendar` | bool | true | Show calendar button/icon |
| `Culture` | string | '' | Locale for month/day names (defaults to TGlobalization culture) |
| `CalendarStyle` | string | `default` | CSS theme for the calendar widget |
| `DropDownCssClass` | string | '' | Extra CSS class for drop-down lists in DropDownList mode |
| `ButtonImageUrl` | string | '' | Custom image for calendar trigger button |
| `ButtonText` | string | `...` | Alt text for trigger button |
| `FirstDayOfWeek` | int | 0 | First day of week (0=Sunday, 1=Monday, ...) |
| `FromYear` | int | — | First selectable year |
| `UpToYear` | int | — | Last selectable year |

## Date Format Tokens

| Token | Meaning |
|-------|---------|
| `d` | Day without leading zero |
| `dd` | Day with leading zero |
| `M` | Month without leading zero |
| `MM` | Month with leading zero |
| `MMM` | Abbreviated month name |
| `MMMM` | Full month name |
| `yy` | Two-digit year |
| `yyyy` | Four-digit year |

Example: `'MM/dd/yyyy'` → `03/15/2024`

## Key Methods

```php
$dp->getDate(): ?string        // date as formatted string
$dp->setDate(string $v): void
$dp->getTimeStamp(): ?int      // Unix timestamp of selected date
$dp->setTimeStamp(?int $v): void
$dp->getData(): mixed          // raw text value (inherited from TTextBox)
$dp->getValidationPropertyValue(): string  // for validators
```

## AutoPostBack Restriction

```php
$dp->setAutoPostBack(true);   // throws TNotSupportedException
```

`TDatePicker` does not support `AutoPostBack`. Use `OnTextChanged` client-side handling or submit the form instead.

## Template Usage

```xml
<com:TDatePicker ID="birthDate"
                 DateFormat="MM/dd/yyyy"
                 Mode="Basic"
                 Culture="en_US"
                 FromYear="1900"
                 UpToYear="2030"
                 ShowCalendar="true" />
```

Drop-down mode:

```xml
<com:TDatePicker ID="expiry"
                 Mode="DropDownList"
                 InputMode="DropDownList"
                 DateFormat="MM/yyyy"
                 FromYear="2024"
                 UpToYear="2030" />
```

## Patterns & Gotchas

- **`AutoPostBack` throws** — if you need server-side response on date change, use a Submit button or a hidden field + JavaScript instead.
- **DateFormat case-sensitive** — `M` and `m` are different tokens. `MM` = month; `mm` = minutes (not used in date-only picker).
- **Culture** — if not set, uses `TGlobalization` culture. Month/day names are localized when `Culture` is set.
- **`DropDownList` mode** — renders separate `<select>` elements for day, month, year. `InputMode=DropDownList` hides the text input; `InputMode=TextBox` shows text + drop-downs side by side.
- **Timestamp vs formatted string** — `getTimeStamp()` returns a Unix timestamp; `getDate()` returns the formatted string. Prefer `getTimeStamp()` for database storage.
- **Year range** — without `FromYear`/`UpToYear`, the calendar defaults to ±10 years from today. Set explicit ranges for registration forms.
