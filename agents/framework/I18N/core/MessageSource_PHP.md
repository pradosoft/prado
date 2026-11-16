# I18N/core/MessageSource_PHP

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](INDEX.md) / **`MessageSource_PHP`**

**Location:** `framework/I18N/core/MessageSource_PHP.php`
**Namespace:** `Prado\I18N\core`

## Overview
PHP array-based message source. Each catalogue is a `.php` file returning an array structure.

## File Format

```php
<?php
return [
    'info' => [
        'source-language' => 'EN',
        'target-language' => 'de_DE',
        'original' => 'messages',
        'date' => '2024-01-15T10:30:00Z'
    ],
    'trans-unit' => [
        ['source' => 'Hello', 'target' => 'Hallo', 'note' => 'Greeting'],
        ['source' => 'Goodbye', 'target' => 'Auf Wiedersehen'],
    ]
];
```

## Usage

```php
$source = MessageSource::factory('PHP', '/path/to/messages/');
$source->setCulture('de_DE');
$source->setCache(new MessageCache('/tmp'));

$formatter = new MessageFormat($source);
echo $formatter->format('Hello');
```

## Catalogue Structure

Files can be organized by locale:
```
messages/
  messages.php           # default
  messages.de_DE.php     # German (Germany)
  de_DE/
    messages.php         # or in a culture directory
```

## Key Methods

Same as `MessageSource`:
- `load($catalogue)` - Load from PHP file
- `save($catalogue)` - Save untranslated to file, then clear the untranslated list
- `update($text, $target, $comments, $catalogue)` - Update translation; false when no unit's source equals `$text` (strict)
- `delete($message, $catalogue)` - Delete translation; false when no unit's source equals `$message` (strict)

## See Also

- [MessageSource](./MessageSource.md) - Abstract base class
- [MessageSource_XLIFF](./MessageSource_XLIFF.md) - XML-based alternative
