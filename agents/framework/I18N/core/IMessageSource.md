# I18N/core/IMessageSource

### Directories
[framework](./INDEX.md) / [I18N](./I18N/INDEX.md) / [core](./I18N/core/INDEX.md) / **`IMessageSource`**

**Location:** `framework/I18N/core/IMessageSource.php`
**Namespace:** `Prado\I18N\core`

## Overview
Interface for message translation backends. All message sources (XLIFF, PHP, gettext, Database) implement this interface.

## Methods

### `load($catalogue = 'messages'): bool`

Load translation table for a catalogue. Called internally by `MessageFormat`.

### `read(): array`

Get the loaded translation table. Returns:
```php
[
    'catalogue+variant' => [
        'source string' => 'target string',
        ...
    ],
    ...
]
```

### `save($catalogue = 'messages'): bool`

Save untranslated messages to the source.

### `append($message): void`

Add an untranslated message (for later `save()`).

### `delete($message, $catalogue = 'messages'): bool`

Delete a message from a catalogue.

### `update($text, $target, $comments, $catalogue = 'messages'): bool`

Update a translation.

### `catalogues(): array`

Return list of catalogues with variants.

### `setCulture($culture): void`

Set the locale for this source.

### `getCulture(): string`

Get the current locale.

## See Also

- [MessageSource](./MessageSource.md) - Abstract base implementing this interface
- [MessageSource_XLIFF](./MessageSource_XLIFF.md) - XLIFF backend
- [MessageSource_PHP](./MessageSource_PHP.md) - PHP array backend
- [MessageSource_gettext](./MessageSource_gettext.md) - GNU gettext backend
- [MessageSource_Database](./MessageSource_Database.md) - Database backend
