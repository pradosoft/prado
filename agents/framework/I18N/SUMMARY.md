# SUMMARY.md

Internationalization and localization covering culture-aware date/number formatting, message translation, plural-choice formatting, and browser-language auto-detection.

## Classes

- **`TGlobalization`** — `TModule` subclass managing culture, charset, and translation; properties: `Culture`, `Charset`, `DefaultCulture`, `DefaultCharset`, `TranslateDefaultCulture`.

- **`TGlobalizationAutoDetect`** — Extends `TGlobalization`; auto-sets `Culture` from HTTP `Accept-Language` header.

- **`TDateFormat`** — Localized date/time formatting and parsing; methods: `format($date, $pattern, $culture)`, `parse($string, $pattern, $culture)`.

- **`TNumberFormat`** — Localized number formatting and parsing for currency, percentage, decimal patterns.

- **`TChoiceFormat`** — Plural/choice selection mapping numeric values to localized strings.

- **`TTranslate`** — Template control (`<com:TTranslate>`) rendering translated text with named parameter substitution.

- **`TTranslateParameter`** — Named parameter `<Key>=<Value>` for use inside `<com:TTranslate>`.

- **`TI18NControl`** — Base class for I18N-aware PRADO controls.

- **`Translation`** — Static helper for programmatic translation access outside templates.
