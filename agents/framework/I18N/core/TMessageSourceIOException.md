# I18N/core/TMessageSourceIOException

### Directories
[framework](../../INDEX.md) / [I18N](../INDEX.md) / [core](./INDEX.md) / **`TMessageSourceIOException`**

## Class Info
**Location:** `framework/I18N/core/TMessageSourceIOException.php`
**Namespace:** `Prado\I18N\core`

## Overview
Extends `TIOException`. The XLIFF, PHP, and gettext message sources throw it when they cannot write a catalogue:

| Error code | Condition |
|---|---|
| `messagesource_file_unwritable` | `save()`, `update()`, or `delete()` finds a catalogue file that is not writable |
| `messagesource_directory_failed` | `createMessageTemplate()` cannot create the culture directory |
| `messagesource_template_failed` | gettext `createMessageTemplate()` cannot write the `.po` and `.mo` files |

## Example

```php
try {
    $source->save('messages');
} catch (TMessageSourceIOException $e) {
    // Unable to write to translation file
    echo "Error: " . $e->getMessage();
}
```

## See Also

- `TIOException` - Base I/O exception
- [MessageSource](./MessageSource.md) - Sources that may throw this