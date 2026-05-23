# I18N/TGlobalizationAutoDetect

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TGlobalizationAutoDetect`**

## Class Info
**Location:** `framework/I18N/TGlobalizationAutoDetect.php`
**Namespace:** `Prado\I18N`

## Overview
Extends [TGlobalization](./TGlobalization.md) to automatically detect the user's preferred culture from the HTTP `Accept-Language` header.

## Usage

```xml
<modules>
    <module id="globalization" class="Prado\I18N\TGlobalizationAutoDetect"
            DefaultCulture="en_US"
            AvailableLanguages="en, it, fr, de" />
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'i18n' => [
            'class' => 'Prado\I18N\TGlobalizationAutoDetect',
            'properties' => ['DefaultCulture' => 'en_US', 'Charset' => 'UTF-8'],
        ],
    ],
];
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `AvailableLanguages` | string | Comma-separated list of supported languages. If empty, any language is accepted. |

## Detection Behavior

1. Parses `Accept-Language` header (e.g., `en-US,en;q=0.9,de;q=0.8`)
2. Normalizes entries to POSIX form (e.g., `en_US`, `de_DE`) — strips q-values, converts BCP 47 hyphens to underscores
3. Validates each candidate with `getIsValidLocale()` against `ResourceBundle::getLocales('')`
4. Filters against `AvailableLanguages` if specified
5. Sets the first passing match as the current culture via `setCulture()`

## `getIsValidLocale($locale)` (@since 4.3.3)

Checks the locale against `ResourceBundle::getLocales('')`, trying three match forms: direct, POSIX→BCP 47 (underscore→hyphen), and BCP 47→POSIX (hyphen→underscore). This handles ICU version differences where the locale list may use either separator.

## Additional Properties

| Property | Type | Description |
|----------|------|-------------|
| `DetectedLanguage` | string | The language that was auto-detected (read-only) |
| `AvailableLanguages` | string | Comma-separated list of accepted languages; empty means any valid locale is accepted |

## See Also

- [TGlobalization](./TGlobalization.md) - Parent module class
- [CultureInfo](./core/CultureInfo.md) - ICU locale data and `validCulture()` logic