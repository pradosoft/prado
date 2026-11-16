# I18N/Translation

### Directories
[framework](../INDEX.md) / [I18N](INDEX.md) / **`Translation`**

**Location:** `framework/I18N/Translation.php`
**Namespace:** `Prado\I18N`

## Overview
Static helper class for programmatic translation access outside of templates. Provides a static `MessageFormat` instance per catalogue.

## Key Methods

### `Translation::init($catalogue = 'messages')`

Creates the catalogue's `MessageFormat` once, from the [TGlobalization](./TGlobalization.md) translation configuration. Called by [TTranslate](./TTranslate.md) and `Prado::localize()`.

| Configuration key | Effect |
|---|---|
| `type`, `source`, `filename` | `MessageSource::factory()` arguments; `filename` is optional |
| `cache` | a `MessageCache` in that directory |
| `marker` | surrounds untranslated text, `setUntranslatedPS([$marker, $marker])` |
| `autosave` | when true, `saveMessages()` is attached to `OnEndRequest` once per process |

### `Translation::formatter($catalogue = 'messages')`

Get the `MessageFormat` instance for a catalogue.

```php
use Prado\I18N\Translation;

Translation::init('messages');
$translated = Translation::formatter('messages')->format('Hello {name}', ['{name}' => 'Alice']);
```

### `Translation::saveMessages()`

Saves the untranslated messages of every formatter when `autosave` is true. It runs once per process; later calls do nothing.

## See Also

- [TTranslate](./TTranslate.md) - Template translation control
- [MessageFormat](./core/MessageFormat.md) - Message formatting engine
