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
<module id="globalization" class="Prado\I18N\TGlobalizationAutoDetect"
        DefaultCulture="en_US"
        AvailableLanguages="en, it, fr, de" />
```

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `AvailableLanguages` | string | Comma-separated list of supported languages. If empty, any language is accepted. |

## Detection Behavior

1. Parses `Accept-Language` header (e.g., `en-US,en;q=0.9,de;q=0.8`)
2. Converts to RFC 4646 format (e.g., `en_US`, `de_DE`)
3. Validates against `AvailableLanguages` if specified
4. Sets first valid match as current culture

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `DetectedLanguage` | string | The language that was detected (read-only) |

## See Also

- `TGlobalization` - Parent module class
- [CultureInfo](./core/CultureInfo.md) - ICU locale data