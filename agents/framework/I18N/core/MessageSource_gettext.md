# I18N / core / MessageSource_gettext

### Directories
[./](../INDEX.md) > [I18N](../INDEX.md) > [core](./INDEX.md) > [MessageSource_gettext](./MessageSource_gettext.md)

**Location:** `framework/I18N/core/MessageSource_gettext.php`
**Namespace:** `Prado\I18N\core`

## Overview

GNU Gettext message source using `.mo` binary files at runtime and `.po` source files for authoring.

## File Structure

```
messages/
  messages.mo            # compiled binary
  messages.po            # source for authoring
  messages.de_DE.mo      # German (Germany)
  messages.de_DE.po      # German source
```

## Usage

```php
$source = MessageSource::factory('gettext', '/path/to/messages/');
$source->setCulture('de_DE');
$source->setCache(new MessageCache('/tmp'));

$formatter = new MessageFormat($source);
echo $formatter->format('Hello');
```

## Key Methods

### `save($catalogue = 'messages'): bool`

Adds untranslated strings to the `.po` file and recompiles to `.mo`.

### `update($text, $target, $comments, $catalogue = 'messages'): bool`

Updates the `.po` file and recompiles.

### `delete($message, $catalogue = 'messages'): bool`

Removes from `.po` and recompiles `.mo`.

## See Also

- [MessageSource](./MessageSource.md) - Abstract base class
- [TGettext_MO](./Gettext/TGettext_MO.md) - Reads/writes `.mo` files
- [TGettext_PO](./Gettext/TGettext_PO.md) - Reads/writes `.po` files
- [TGettext](./Gettext/TGettext.md)::poFile2moFile() - CLI-style compilation