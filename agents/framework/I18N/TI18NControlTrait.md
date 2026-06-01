# I18N/TI18NControlTrait

### Directories
[framework](../INDEX.md) / [I18N](./INDEX.md) / **`TI18NControlTrait`**

## Class Info
**Location:** `framework/I18N/TI18NControlTrait.php`
**Namespace:** `Prado\I18N`
**Since:** `4.3.3`

## Overview
Trait providing the `Culture` and `Charset` properties for I18N-aware controls. Extracted from the old `TI18NControl` base class so that the same logic can be shared by classes that cannot extend `TI18NControl` (e.g., controls that already extend a different base). [TI18NControl](./TI18NControl.md) is now a thin wrapper that `use`s this trait.

The trait requires the host class to have `getViewState()` / `setViewState()` methods (satisfied by any `TControl` subclass) and a `getApplication()` method.

## Dynamic Events

| Event | Default | Description |
|-------|---------|-------------|
| `dyDefaultCharsetValue(string $default)` | `'UTF-8'` | Raised when no charset can be resolved from the application; behaviors can override |
| `dyDefaultCultureValue(string $default)` | `''` | Raised when no culture can be resolved from the application; behaviors can override |

## Properties

### `getCulture(): string`

Resolution order:
1. View state `'Culture'` (control-level override)
2. `$app->getGlobalization()->getCulture()` (application culture)
3. `dyDefaultCultureValue('')` — dynamic event result (behaviors / no-app fallback)

### `setCulture(string $culture): void`

Stores in view state `'Culture'`.

### `getCharset(): string`

Resolution order:
1. View state `'Charset'` (control-level override)
2. `$app->getGlobalization()->getCharset()` — non-empty application charset
3. `$app->getGlobalization()->getDefaultCharset()` — non-empty default charset
4. `dyDefaultCharsetValue('UTF-8')` — dynamic event result (behaviors / no-app fallback)

### `setCharset(string $value): void`

Stores in view state `'Charset'`.

## Helper Methods

### `getCultureInfo($culture = null): CultureInfo`

Returns a `CultureInfo` instance for the given culture (or `$this->getCulture()` if null). Uses `CultureInfo::getCultureInfo()` for caching.

### `convertToCharset(string $text): string`

Converts `$text` from UTF-8 to the resolved `Charset` via `TUtf8Converter::fromUTF8()`.

## Usage

The trait is applied by `TI18NControl` and is intended to be used by any control that needs I18N awareness:

```php
class MyCustomControl extends TWebControl
{
    use TI18NControlTrait;
    // Now has getCulture(), setCulture(), getCharset(), setCharset()
}
```

## See Also

- [TI18NControl](./TI18NControl.md) - Concrete base class using this trait
- [TDateFormat](./TDateFormat.md) - Uses TI18NControl (and thus this trait)
- [TNumberFormat](./TNumberFormat.md) - Uses TI18NControl (and thus this trait)
- [TGlobalization](./TGlobalization.md) - Provides application-level culture/charset
