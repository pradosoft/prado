# I18N/Translation

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / **`Translation`**

**Location:** `framework/I18N/Translation.php`
**Namespace:** `Prado\I18N`

## Overview
Static helper class for programmatic translation access outside of templates. Provides a static `MessageFormat` instance per catalogue.

## Key Methods

### `Translation::init($catalogue = 'messages')`

Initialize the translation system for a catalogue. Called automatically by [TTranslate](./TTranslate.md).

### `Translation::formatter($catalogue = 'messages')`

Get the `MessageFormat` instance for a catalogue.

### `Translation::get($message, $args = [], $catalogue = 'messages')`

Shortcut for translating with substitution.

```php
use Prado\I18N\Translation;

$translated = Translation::get('Hello {name}', ['{name}' => 'Alice'], 'messages');
```

### `Translation::saveMessages()`

Save untranslated messages to the catalogue. Called automatically at end of request if `autosave` is enabled.

## See Also

- [TTranslate](./TTranslate.md) - Template translation control
- [MessageFormat](./core/MessageFormat.md) - Message formatting engine
