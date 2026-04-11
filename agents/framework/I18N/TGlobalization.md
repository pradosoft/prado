# I18N/TGlobalization

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TGlobalization`**

## Class Info
**Location:** `framework/I18N/TGlobalization.php`
**Namespace:** `Prado\I18N`

## Overview
Internationalization and localization system. [TGlobalization](./TGlobalization.md) is the root module; it provides culture selection, charset management, and access to the translation engine. Format classes handle localized dates, numbers, and plural choice strings.

---

## TGlobalization

Register in `application.xml`:

```xml
<module id="globalization" class="Prado\I18N\TGlobalization"
        Culture="en_US" Charset="UTF-8"
        DefaultCulture="en_US" DefaultCharset="UTF-8" />
```

Or with auto-detection from `Accept-Language` header:
```xml
<module id="globalization" class="Prado\I18N\TGlobalizationAutoDetect"
        DefaultCulture="en_US" />
```

### Key Properties

| Property | Description |
|----------|-------------|
| `Culture` | Current locale (RFC 4646 tag, e.g. `'en_US'`, `'zh_CN'`) |
| `Charset` | Current charset (default `'UTF-8'`) |
| `DefaultCulture` | Fallback locale when translation missing |
| `TranslateDefaultCulture` | If `false`, skip translation when culture == DefaultCulture |

### Access

```php
$g = Prado::getGlobalization();
$culture = $g->getCulture();
$charset = $g->getCharset();
```

---

## Translation

### MessageSource Factory

```php
// Never instantiate backends directly; use the factory:
$source = [MessageSource](./core/MessageSource.md)::factory('XLIFF', '/path/to/xliff/dir');
$source = [MessageSource](./core/MessageSource.md)::factory('PHP', '/path/to/php/dir');
$source = [MessageSource](./core/MessageSource.md)::factory('gettext', '/path/to/gettext/dir');
$source = [MessageSource](./core/MessageSource.md)::factory('Database', $connectionObject);
```

### In Templates

```xml
<com:TTranslate Text="Hello, {name}!" Parameters.name="World" />

<!-- Multi-line: -->
<com:TTranslate>
    This is a <b>long</b> block of translated text.
</com:TTranslate>
```

### Programmatic Translation

```php
use Prado\I18N\Translation;

$translated = Translation::get('my_message_key', ['{name}' => 'Alice'], 'messages');
```

---

## TDateFormat

Localized date/time formatting.

```php
$fmt = new TDateFormat('en_US');
echo $fmt->format(time(), 'full');           // "Thursday, January 1, 2026"
echo $fmt->format(time(), 'yyyy-MM-dd');     // "2026-01-01"
echo $fmt->format(time(), 'short');          // "1/1/26"

$ts = $fmt->parse('January 1, 2026', 'long');  // returns timestamp
```

Pattern tokens follow ICU standard: `yyyy`, `MM`, `dd`, `HH`, `mm`, `ss`, `E` (day name), `MMMM` (month name), etc.

---

## TNumberFormat

Localized number formatting.

```php
$fmt = new TNumberFormat('en_US');
echo $fmt->format(1234567.89, 'currency', 'USD');  // "$1,234,567.89"
echo $fmt->format(0.1234, 'percent');               // "12%"
echo $fmt->format(1234567.89, 'decimal');           // "1,234,567.89"
```

---

## TChoiceFormat

Plural/choice string selection.

```php
$fmt = new TChoiceFormat();
$pattern = "0#no items|1#one item|1<{0} items";
echo $fmt->format($pattern, 0);   // "no items"
echo $fmt->format($pattern, 1);   // "one item"
echo $fmt->format($pattern, 5);   // "5 items"
```

Supports complex plural forms for languages with multiple plural categories (e.g., Russian, Arabic).

---

## CultureInfo

ICU-based locale data for 100+ locales.

```php
$ci = new [CultureInfo](./core/CultureInfo.md)('fr_FR');
$ci->getLanguageName();          // "French"
$ci->getCountryName('US');       // "États-Unis"
$ci->getDateTimePatterns();      // ICU date/time format patterns
$ci->getNumberPatterns();        // number format patterns
$ci->formatNumber(1234.5);       // "1 234,5"
$ci->formatCurrency(99.99, 'EUR'); // "99,99 €"
```

---

## Gettext Backend

```php
// .mo files used at runtime:
$source = MessageSource::factory('gettext', '/app/locale');
// .po files used for authoring (translated to .mo):
```

[TGettext_MO](./core/Gettext/TGettext_MO.md) — reads compiled binary `.mo` files (handles both byte orders).
[TGettext_PO](./core/Gettext/TGettext_PO.md) — reads/writes `.po` source files including plural forms.

---

## Conventions

- **Culture tags** — use RFC 4646 format: `en_US`, `zh_CN`, `pt_BR`. Must match an ICU locale exactly.
- **Message catalogues** — organize by domain (`'messages'`, `'validation'`, `'admin'`). Configured per message source in `application.xml`.
- **`{0}`/`{name}` substitution** — positional `{0}` or named `{name}` placeholders in message strings.
- **`TranslateDefaultCulture=false`** — avoids translation round-trip when displaying in the default language.

## Gotchas

- `TGlobalization` must be initialized before any translation call (module init order matters).
- Culture string must exactly match an ICU locale; invalid tags silently fall back to default.
- `TChoiceFormat` plural rules vary significantly by language — always test with boundary values (0, 1, 2, 11, 100).
